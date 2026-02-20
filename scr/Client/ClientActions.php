<?php
namespace IntranetGestoria\Client;

use IntranetGestoria\File\FileSecurity;
use IntranetGestoria\Utils\SendEmail;

class ClientActions {
    
    public static function handle() {
        if (!is_user_logged_in()) return;
        
        $user = wp_get_current_user();
        $manager = new ClientManager($user);
        
        // Subida de archivos
        if (isset($_FILES['archivo_cliente'])) {
            self::handleUpload($manager);
        }
        
        // Borrado de archivos
        if (isset($_POST['borrar_archivo_cliente'])) {
            self::handleDelete($manager);
        }
    }
    
    private static function handleUpload($manager) {
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    
    $user = wp_get_current_user(); // El cliente logueado
    $base_path = $manager->getBasePath();
    $sub_actual = isset($_GET['dir']) ? sanitize_text_field($_GET['dir']) : '';
    $full_path = rtrim($base_path . '/' . ltrim($sub_actual, '/'), '/');
    $paths = isset($_POST['rutas_relativas']) ? explode('|', $_POST['rutas_relativas']) : [];
    
    $hay_archivos = false; // Nueva bandera

    foreach ($_FILES['archivo_cliente']['name'] as $key => $name) {
        if ($_FILES['archivo_cliente']['error'][$key] !== UPLOAD_ERR_OK) continue;
        
        $file_name = FileSecurity::sanitizeFilename($name);
        if (!FileSecurity::validateExtension($file_name)) continue;
        
        $extra_path = '';
        if (!empty($paths[$key]) && strpos($paths[$key], '/') !== false) {
            $extra_path = '/' . dirname(str_replace(['\\', '..'], ['/', ''], $paths[$key]));
        }
        
        $target_dir = rtrim($full_path . $extra_path, '/');
        if (!file_exists($target_dir)) wp_mkdir_p($target_dir);
        
        if (move_uploaded_file($_FILES['archivo_cliente']['tmp_name'][$key], $target_dir . '/' . $file_name)) {
            $hay_archivos = true;
        }
    }

    // --- LÓGICA DE NOTIFICACIÓN CORREGIDA ---
    if ($hay_archivos) {
        $path_utils = dirname(__DIR__) . '/Utils/SendEmail.php';
        if (file_exists($path_utils)) {
            require_once $path_utils;
            // Usamos $user->ID directamente y marcamos como 'cliente'
            \IntranetGestoria\Utils\SendEmail::enviar_notificacion($user->ID, 'cliente');
        }
    }

    wp_safe_redirect($_SERVER['REQUEST_URI']);
    exit;
}
    
    private static function handleDelete($manager) {
        $ruta_relativa = sanitize_text_field($_POST['ruta_archivo']);
        $result = $manager->deleteFile($ruta_relativa);
        
        wp_safe_redirect($_SERVER['REQUEST_URI']);
        exit;
    }
}
