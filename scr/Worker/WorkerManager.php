<?php
namespace IntranetGestoria\Worker;

use IntranetGestoria\Trash\TrashManager;
use IntranetGestoria\Trash\TrashLogger;
use IntranetGestoria\File\FileSecurity;

class WorkerManager {

    private $worker_id;
    private $ver_cliente;
    private $client_base_path;

    public function __construct($worker_id, $ver_cliente = null) {
        $this->worker_id = $worker_id;
        $this->ver_cliente = $ver_cliente;

        if ($ver_cliente) {
            $partes = explode('-', $ver_cliente);
            $user_id = $partes[1] ?? 0;
            $user = get_userdata($user_id);
            if ($user) {
                $this->client_base_path = intranet_get_client_base_path($user_id, $user->display_name);
            }
        }
    }

    /**
     * Verifica si el trabajador tiene acceso al cliente especificado
     * @param int $client_id ID del cliente
     * @return bool True si tiene acceso, false si no
     */
    public function hasAccessToClient($client_id) {
        // Obtener TODOS los trabajadores asignados al cliente
        $assigned_workers = ig_get_workers_by_client($client_id);

      if (empty($assigned_workers)) {
            return false;
        }

        // Comprobar si el trabajador actual está en la lista
        foreach ($assigned_workers as $worker) {
            if ($worker->ID === $this->worker_id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtiene todos los clientes asignados a este trabajador
     * @return WP_User[] Array de clientes
     */
    public function getAssignedClients() {
        return ig_get_clients_by_worker($this->worker_id);
    }

    /**
     * Obtiene información del cliente actual
     * @return WP_User|null
     */
    public function getClientInfo() {
        if (!$this->ver_cliente) {
            return null;
        }

        $partes = explode('-', $this->ver_cliente);
        $user_id = $partes[1] ?? 0;
        $client = get_userdata($user_id);

        // Verificar que el trabajador tiene acceso a este cliente
        if ($client && !$this->hasAccessToClient($client->ID)) {
            return null;
        }

        return $client;
    }

    /**
     * Obtiene los archivos del cliente
     * @param string $sub_dir Subdirectorio dentro del cliente
     * @return array Lista de archivos
     */
    public function getFiles($sub_dir = '') {
        if (!$this->client_base_path) {
            return [];
        }

        $full_path = $this->client_base_path . ltrim($sub_dir, '/');

        if (!is_dir($full_path)) return [];

        $items = array_diff(scandir($full_path), ['.', '..', 'index.php', INTRANET_TRASH_FOLDER]);

        $result = [];
        foreach ($items as $item) {
            $item_path = $full_path . '/' . $item;
            $result[] = [
                'name' => $item,
                'path' => $item_path,
                'rel_path' => ltrim($sub_dir, '/') ? rtrim($sub_dir, '/') . '/' . $item : $item,
                'is_dir' => is_dir($item_path),
                'date' => filemtime($item_path),
                'author' => $this->getAuthor($item),
            ];
        }
        // Ordenar por fecha (más reciente primero)
        usort($result, function($a, $b) {
            return $b['date'] - $a['date'];
        });
        
        return $result;
    }
        /**
         * Elimina un archivo o carpeta (lo mueve a la papelera)
         * @param string $file_rel_path Ruta relativa del archivo
         * @return array Resultado de la operación
         */
        public function deleteFile($file_rel_path) {
            $file_path = realpath($this->client_base_path . '/' . $file_rel_path);
            
            if (!$file_path || !FileSecurity::validatePath($file_path, $this->client_base_path)) {
                return ['success' => false, 'message' => 'Ruta inválida'];
            }
            
            $filename = basename($file_path);
            $author = $this->getAuthor($filename);
            
            // Si es archivo de sistema, NO se puede borrar
            if ($author === 'Sistema') {
                return ['success' => false, 'message' => 'Este elemento es del sistema y no se puede borrar.'];
            }
            
            $user = wp_get_current_user();
            $username = $user->display_name;
            $base_dir = INTRANET_CLIENTS_DIR;
            $ver_cliente = $this->ver_cliente;
            
            if (is_dir($file_path)) {
                // RECURSIVIDAD: Entramos en la carpeta para "desmontarla"
                $files = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($file_path, \RecursiveDirectoryIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::CHILD_FIRST
                );

                foreach ($files as $fileinfo) {
                    $item_path = $fileinfo->getRealPath();
                    
                    if ($fileinfo->isFile()) {
                        // --- PROTECCIÓN INDEX.PHP ---
                        if ($fileinfo->getFilename() === 'index.php') {
                            @unlink($item_path); 
                            continue;
                        }

                        // --- ARCHIVOS NORMALES ---
                        // Calculamos la ruta relativa para el log
                        $rel_log = str_replace(realpath($base_dir . $ver_cliente) . DIRECTORY_SEPARATOR, '', $item_path);
                        $rel_log = str_replace('\\', '/', $rel_log);

                        // Enviamos al TrashManager (mueve a papelera + escribe en log)
                        TrashManager::moveToTrash($base_dir . $ver_cliente . '/', $item_path, $username);
                    } else {
                        // Es una subcarpeta vacía, la borramos
                        @rmdir($item_path);
                    }
                }
                
                // Registramos la eliminación de la carpeta contenedora en el log
                \IntranetGestoria\Trash\TrashLogger::registerDeletion($base_dir . $ver_cliente . '/', $file_rel_path, 'CARPETA', $username);
                @rmdir($file_path); // Borramos la carpeta principal
                
                return ['success' => true, 'message' => 'Carpeta movida a papelera'];
                
            } else {
                // Es un archivo suelto
                TrashManager::moveToTrash($base_dir . $ver_cliente . '/', $file_path, $username);
                return ['success' => true, 'message' => 'Archivo movido a papelera'];
            }
        }

    /**
     * Obtiene los items de la papelera del cliente
     * @return array Lista de items en papelera
     */
    public function getTrashItems() {
        if (!$this->client_base_path) {
            return [];
        }
        return TrashManager::getTrashItems($this->client_base_path);
    }

    /**
     * Obtiene el log de la papelera
     * @return string|null Contenido del log
     */
    public function getTrashLog() {
        if (!$this->client_base_path) {
            return null;
        }
        return TrashLogger::getLog($this->client_base_path);
    }

    /**
     * Elimina permanentemente un archivo de la papelera
     * @param string $filename Nombre del archivo
     * @return bool True si se eliminó correctamente
     */
    public function deleteFromTrash($filename) {
        if (!$this->client_base_path) {
            return false;
        }
        return TrashManager::permanentlyDelete($this->client_base_path, $filename);
    }

    /**
     * Elimina un directorio recursivamente
     * @param string $path Ruta del directorio
     * @return bool True si se eliminó correctamente
     */
    private function deleteDirectory($path) {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $method = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            @$method($fileinfo->getRealPath());
        }
        return @rmdir($path);
    }

    /**
 * Determina el autor de un archivo
 * @param string $filename Nombre del archivo
 * @return string Autor del archivo (Sistema, Trabajador o Cliente)
 */
private function getAuthor($filename) {

    if (strpos($filename, '_gs_') === 0) {
        return 'Trabajador';
    }

    if (substr($filename, -4) === '_sys') {
        return 'Sistema';
    }

    return 'Cliente';
}

}
