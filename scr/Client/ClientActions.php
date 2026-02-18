<?php
namespace IntranetGestoria\Client;

use IntranetGestoria\File\FileSecurity;

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
        
        $base_path = $manager->getBasePath();
        $sub_actual = isset($_GET['dir']) ? sanitize_text_field($_GET['dir']) : '';
        $full_path = rtrim($base_path . '/' . ltrim($sub_actual, '/'), '/');
        $paths = isset($_POST['rutas_relativas']) ? explode('|', $_POST['rutas_relativas']) : [];
        
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
            
            move_uploaded_file($_FILES['archivo_cliente']['tmp_name'][$key], $target_dir . '/' . $file_name);
        }

        // Enviar notificaciones por email
        $current_user = wp_get_current_user();

        // Email al administrador
        $admin_email = get_option('admin_email');
        $subject = 'Nuevos documentos subidos por cliente';
        $message = "El cliente {$current_user->display_name} ha subido nuevos documentos a su expediente.\n\n";
        $message .= "Puedes revisarlos desde el panel de administración.\n\n";
        $message .= "Saludos,\nSistema de Gestoría";

        wp_mail($admin_email, $subject, $message);

        // Email al trabajador asignado (si tiene)
        $worker = ig_get_worker_by_client($current_user->ID);
        if ($worker && $worker->user_email) {
            $worker_message = "Hola {$worker->display_name},\n\n";
            $worker_message .= "Tu cliente asignado {$current_user->display_name} ha subido nuevos documentos a su expediente.\n\n";
            $worker_message .= "Puedes revisarlos desde tu panel de trabajador.\n\n";
            $worker_message .= "Saludos,\nSistema de Gestoría";

            wp_mail($worker->user_email, $subject, $worker_message);
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
