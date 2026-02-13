<?php
namespace IntranetGestoria\Admin;

use IntranetGestoria\File\FileSecurity;
use IntranetGestoria\Trash\TrashManager;

class AdminActions {
    
    public static function handle() {
        if (!current_user_can('edit_published_posts')) return;
        
        $ver_cliente = isset($_GET['ver_cliente']) ? sanitize_text_field($_GET['ver_cliente']) : null;
        if (!$ver_cliente) return;
        
        $manager = new AdminManager($ver_cliente);
        
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
    
    private static function handleUpload($manager, $ver_cliente) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        
        $base_dir = INTRANET_CLIENTS_DIR;
        $sub_actual = isset($_GET['dir']) ? sanitize_text_field($_GET['dir']) : '';
        $paths = isset($_POST['rutas_relativas']) ? explode('|', $_POST['rutas_relativas']) : [];
        
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
            
            $dest = rtrim($target_dir, '/') . '/_gs_' . $file_name;
            if (file_exists($dest)) $dest = rtrim($target_dir, '/') . '/_gs_' . time() . '-' . $file_name;
            
            move_uploaded_file($_FILES['archivo_gestor']['tmp_name'][$key], $dest);
        }
        
        wp_safe_redirect($_SERVER['REQUEST_URI']);
        exit;
    }
    
    private static function handleDelete($manager, $ver_cliente) {
        $ruta_relativa = sanitize_text_field($_POST['ruta_archivo']);
        $file_path = realpath(INTRANET_CLIENTS_DIR . $ruta_relativa);
        
        if ($file_path && strpos($file_path, realpath(INTRANET_CLIENTS_DIR)) === 0) {
            $manager->deleteFile(str_replace(INTRANET_CLIENTS_DIR . $ver_cliente . '/', '', $ruta_relativa));
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
