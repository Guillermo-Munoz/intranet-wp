<?php
namespace IntranetGestoria\Client;

use IntranetGestoria\Trash\TrashManager;
use IntranetGestoria\File\FileSecurity;

class ClientManager {
    
    private $user;
    private $base_path;
    private $id_carpeta;
    
    public function __construct($user) {
        $this->user = $user;
        $this->id_carpeta = intranet_get_client_folder($user->ID, $user->display_name);
        $this->base_path = intranet_get_client_base_path($user->ID, $user->display_name);
        intranet_ensure_directory($this->base_path);
        intranet_ensure_year_directory($this->base_path);
    }
    
    public function getIdCarpeta() {
        return $this->id_carpeta;
    }
    
    public function getBasePath() {
        return $this->base_path;
    }
    
    public function getFiles($sub_dir = '') {
        $full_path = $this->base_path . ltrim($sub_dir, '/');
        
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
        return $result;
    }
    
    public function deleteFile($file_rel_path) {
        $file_path = realpath($this->base_path . '/' . $file_rel_path);
        
        // Validar ruta
        if (!$file_path || !FileSecurity::validatePath($file_path, $this->base_path)) {
            return ['success' => false, 'message' => 'Ruta inválida'];
        }
        
        // No borrar carpeta de año
        if (basename($file_path) === INTRANET_YEAR) {
            return ['success' => false, 'message' => 'No puedes borrar esta carpeta'];
        }
        
        // No borrar archivos de gestoría
        if (strpos(basename($file_path), '_gs_') === 0) {
            return ['success' => false, 'message' => 'Este archivo está protegido'];
        }
        
        // Mover a papelera
        try {
            TrashManager::moveToTrash($this->base_path, $file_path, $this->user->display_name);
            return ['success' => true, 'message' => 'Archivo movido a papelera'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al borrar'];
        }
    }
    
    private function getAuthor($filename) {
        if (strpos($filename, '_gs_') === 0) {
            return 'Gestoría';
        } elseif ($filename === INTRANET_YEAR) {
            return 'Sistema';
        } else {
            return 'Cliente';
        }
    }
}
