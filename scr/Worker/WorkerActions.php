<?php
namespace IntranetGestoria\Worker;

use IntranetGestoria\File\FileSecurity;
use IntranetGestoria\Trash\TrashManager;
use IntranetGestoria\Utils\SendEmail;
class WorkerActions {

    public static function handle() {
        // Verificar que el usuario actual es un trabajador
        if (!ig_is_worker(get_current_user_id())) {
            return;
        }

        $ver_cliente = isset($_GET['ver_cliente']) ? sanitize_text_field($_GET['ver_cliente']) : null;
        if (!$ver_cliente) return;

        $manager = new WorkerManager(get_current_user_id(), $ver_cliente);

        // Verificar acceso al cliente
        $partes = explode('-', $ver_cliente);
        $client_id = $partes[1] ?? 0;
        if (!$manager->hasAccessToClient($client_id)) {
            wp_die('No tienes permiso para acceder a este cliente.');
            return;
        }

        // Subida de archivos
        if (isset($_FILES['archivo_gestor'])) {
            self::handleUpload($manager, $ver_cliente);
        }

        // Borrado de documentos activos
        if (isset($_POST['borrar_archivo'])) {
            self::handleDelete($manager, $ver_cliente);
        }

        // Borrado permanente de papelera
        if (isset($_POST['borrar_archivo_papelera'])) {
            self::handleTrashDelete($ver_cliente);
        }
    }

    // private static function handleUpload($manager, $ver_cliente) {
    //     require_once(ABSPATH . 'wp-admin/includes/file.php');

    //     $base_dir = INTRANET_CLIENTS_DIR;
    //     $sub_actual = isset($_GET['dir']) ? sanitize_text_field($_GET['dir']) : '';
    //     $paths = isset($_POST['rutas_relativas']) ? explode('|', $_POST['rutas_relativas']) : [];

    //     foreach ($_FILES['archivo_gestor']['name'] as $key => $name) {
    //         if ($_FILES['archivo_gestor']['error'][$key] !== UPLOAD_ERR_OK) continue;

    //         $file_name = FileSecurity::sanitizeFilename($name);
    //         if (!FileSecurity::validateExtension($file_name)) continue;

    //         $extra_path = '';
    //         if (!empty($paths[$key]) && strpos($paths[$key], '/') !== false) {
    //             $partes_ruta = explode('/', dirname($paths[$key]));
    //             $partes_protegidas = array_map(function($folder) {
    //                 return (strpos($folder, '_gs_') === 0) ? $folder : '_gs_' . $folder;
    //             }, $partes_ruta);
    //             $extra_path = '/' . implode('/', $partes_protegidas);
    //         }

    //         $target_dir = rtrim($base_dir . $ver_cliente . '/' . ltrim($sub_actual, '/'), '/') . $extra_path;

    //         if (!file_exists($target_dir)) {
    //             wp_mkdir_p($target_dir);
    //             file_put_contents($target_dir . '/index.php', '<?php // Privado');
    //         }

    //         // El archivo lleva prefijo _gs_ porque lo sube el Trabajador
    //         $final_name = (strpos($file_name, '_gs_') === 0) ? $file_name : '_gs_' . $file_name;
    //         $dest = rtrim($target_dir, '/') . '/' . $final_name;

    //         // Evitar sobreescribir si ya existe
    //         if (file_exists($dest)) {
    //             $dest = rtrim($target_dir, '/') . '/' . time() . '-' . $final_name;
    //         }

    //         move_uploaded_file($_FILES['archivo_gestor']['tmp_name'][$key], $dest);
    //     }

    // wp_safe_redirect($_SERVER['REQUEST_URI']);
    //     exit;
    // }

     private static function handleUpload($manager, $ver_cliente) {
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    
    $base_dir    = INTRANET_CLIENTS_DIR;
    $sub_actual  = isset($_GET['dir']) ? sanitize_text_field($_GET['dir']) : '';
    $paths       = isset($_POST['rutas_relativas']) ? explode('|', $_POST['rutas_relativas']) : [];
    
    $archivos_subidos = [];

    foreach ($_FILES['archivo_gestor']['name'] as $key => $name) {
        if ($_FILES['archivo_gestor']['error'][$key] !== UPLOAD_ERR_OK) continue;

        $file_name = FileSecurity::sanitizeFilename($name);
        if (!FileSecurity::validateExtension($file_name)) continue;

        $extra_path = '';
        if (!empty($paths[$key]) && strpos($paths[$key], '/') !== false) {
            $partes_ruta = explode('/', dirname($paths[$key]));
            $partes_protegidas = array_map(function($folder) {
                return (strpos($folder, '_gs_') === 0) ? $folder : '_gs_' . $folder;
            }, $partes_ruta);
            $extra_path = '/' . implode('/', $partes_protegidas);
        }

        $target_dir = rtrim($base_dir . $ver_cliente . '/' . ltrim($sub_actual, '/'), '/') . $extra_path;

        if (!file_exists($target_dir)) {
            wp_mkdir_p($target_dir);
            file_put_contents($target_dir . '/index.php', '<?php // Privado');
        }

        $final_name = (strpos($file_name, '_gs_') === 0) ? $file_name : '_gs_' . $file_name;
        $dest = rtrim($target_dir, '/') . '/' . $final_name;

        if (file_exists($dest)) {
            $dest = rtrim($target_dir, '/') . '/' . time() . '-' . $final_name;
        }

        if (move_uploaded_file($_FILES['archivo_gestor']['tmp_name'][$key], $dest)) {
            $archivos_subidos[] = $dest;
        }
    }

       
    // Enviar email al cliente notificando la subida
    if (!empty($archivos_subidos)) {
        $partes    = explode('-', $ver_cliente);
        $client_id = intval($partes[1] ?? 0);

        if ($client_id > 0) {
            $current_uid = get_current_user_id();

            if (user_can($current_uid, 'administrator')) {
                $quien = 'admin';
            } elseif (\ig_is_worker($current_uid)) {
                $quien = 'trabajador';
            } else {
                $quien = 'cliente';
            }

            SendEmail::enviar_notificacion($client_id, $quien);
        }
    }

    wp_safe_redirect($_SERVER['REQUEST_URI']);
    exit;
}

    private static function handleDelete($manager, $ver_cliente) {
        $ruta_relativa = sanitize_text_field($_POST['ruta_archivo']);
        
        // Extraer la ruta relativa dentro del cliente
        $partes = explode('/', $ruta_relativa);
        $cliente_folder = $partes[0];
        $ruta_interna = implode('/', array_slice($partes, 1));
        
        $result = $manager->deleteFile($ruta_interna);
        
        // Opcional: guardar mensaje para mostrar al usuario
        if (!$result['success']) {
            set_transient('worker_delete_error', $result['message'], 30);
        } else {
            set_transient('worker_delete_success', $result['message'], 30);
        }
        
        wp_safe_redirect($_SERVER['REQUEST_URI']);
        exit;
    }

    private static function handleTrashDelete($ver_cliente) {
        $ruta_papelera = sanitize_text_field($_POST['ruta_archivo_papelera']);
        $file_path = realpath(INTRANET_CLIENTS_DIR . $ruta_papelera);

        if ($file_path && strpos($file_path, realpath(INTRANET_CLIENTS_DIR)) === 0 && FileSecurity::isInTrash($file_path)) {
            $filename = basename($file_path);
            TrashManager::permanentlyDelete(
                INTRANET_CLIENTS_DIR . $ver_cliente . '/',
                $filename
            );
        }

        wp_safe_redirect($_SERVER['REQUEST_URI']);
        exit;
    }
}
