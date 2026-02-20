<?php
namespace IntranetGestoria\Admin;

use IntranetGestoria\File\FileSecurity;
use IntranetGestoria\Trash\TrashManager;
use IntranetGestoria\Utils\SendEmail;


class AdminActions {
    
    public static function handle() {
        // Procesar acciones de roles sin validación de permisos global (cada handler válida)
        if (isset($_POST['promover_trabajador'])) {
            self::handlePromoteToWorker();
            return;
        }

        if (isset($_POST['degradar_trabajador'])) {
            self::handleDemoteToClient();
            return;
        }

        if (isset($_POST['asignar_trabajador'])) {
            self::handleAssignWorker();
            return;
        }

        if (isset($_POST['desvincular_trabajador'])) {
            self::handleUnassignWorker();
            return;
        }

        // Acciones de gestión de archivos requieren permisos
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
    $sub_actual = isset($_POST['dir']) ? sanitize_text_field($_POST['dir']) : '';
    
    if (!isset($_FILES['archivo_gestor'])) return;

    $rutas_string = isset($_POST['rutas_relativas']) ? sanitize_text_field($_POST['rutas_relativas']) : '';
    $rutas_relativas = !empty($rutas_string) ? explode('|', $rutas_string) : [];

    $hay_subida_exitosa = false;

    foreach ($_FILES['archivo_gestor']['name'] as $key => $name) {
        if ($_FILES['archivo_gestor']['error'][$key] !== UPLOAD_ERR_OK) continue;
        
        $info_ruta = (!empty($rutas_relativas[$key])) ? $rutas_relativas[$key] : $name;
        $partes_ruta = explode('/', ltrim($info_ruta, '/'));
        $partes_finales = [];

        foreach ($partes_ruta as $segmento) {
            $segmento_limpio = FileSecurity::sanitizeFilename($segmento);
            $partes_finales[] = '_gs_' . ltrim($segmento_limpio, '_gs_');
        }

        $final_file_name = end($partes_finales);
        array_pop($partes_finales);
        $sub_carpeta_archivo = implode('/', $partes_finales);

        $target_dir = rtrim($base_dir . $ver_cliente . '/' . ltrim($sub_actual, '/'), '/');
        if (!empty($sub_carpeta_archivo)) {
            $target_dir .= '/' . ltrim($sub_carpeta_archivo, '/');
        }

        $dest = $target_dir . '/' . $final_file_name;

        if (!file_exists($target_dir)) {
            wp_mkdir_p($target_dir);
            self::ensureIndexInPath($base_dir . $ver_cliente . '/' . ltrim($sub_actual, '/'), $sub_carpeta_archivo);
        }

        if (move_uploaded_file($_FILES['archivo_gestor']['tmp_name'][$key], $dest)) {
            $hay_subida_exitosa = true;
        }
    }

    // Enviar email al cliente notificando la subida
    if ($hay_subida_exitosa) {
        $partes    = explode('-', $ver_cliente);
        $client_id = intval($partes[1] ?? 0);

        if ($client_id > 0) {
            $current_uid = get_current_user_id();

            if (user_can($current_uid, 'author')) {
                $quien = 'admin';
            } elseif (\ig_is_worker($current_uid)) {
                $quien = 'trabajador';
            } else {
                $quien = 'cliente';
            }

            SendEmail::enviar_notificacion($client_id, $quien);
        }
    }

    $url = home_url('/customer-area/dashboard/?ver_cliente=' . $ver_cliente);
    if (!empty($sub_actual)) $url = add_query_arg('dir', urlencode($sub_actual), $url);

    wp_safe_redirect($url);
    exit;
}

    /**
     * Asegura que cada subcarpeta nueva creada tenga su archivo index.php
     */
    private static function ensureIndexInPath($base_path, $relative_path) {
        if (empty($relative_path)) return;
        $segments = explode('/', trim($relative_path, '/'));
        $current = rtrim($base_path, '/');
        foreach ($segments as $segment) {
            if (empty($segment)) continue;
            $current .= '/' . $segment;
            if (is_dir($current) && !file_exists($current . '/index.php')) {
                file_put_contents($current . '/index.php', '<?php // Silencio');
            }
        }
    }

    
        private static function handleDelete($manager, $ver_cliente) {
        $ruta_relativa = sanitize_text_field($_POST['ruta_archivo']);
        $base_dir = INTRANET_CLIENTS_DIR;
        $full_path = realpath($base_dir . $ruta_relativa);
        
        if ($full_path && strpos($full_path, realpath($base_dir)) === 0) {
            $user = wp_get_current_user();
            $username = $user->display_name;

            if (is_dir($full_path)) {
                // RECURSIVIDAD: Entramos en la carpeta para "desmontarla"
                $files = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($full_path, \RecursiveDirectoryIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::CHILD_FIRST
                );

                foreach ($files as $fileinfo) {
                    $item_path = $fileinfo->getRealPath();
                    
                    if ($fileinfo->isFile()) {
                        // --- PROTECCIÓN INDEX.PHP ---
                        // Si el archivo es un index.php, lo borramos del mapa directamente
                        if ($fileinfo->getFilename() === 'index.php') {
                            @unlink($item_path); 
                            continue; // Saltamos al siguiente archivo sin hacer log ni moverlo
                        }

                        // --- ARCHIVOS NORMALES ---
                        // Calculamos la ruta relativa para que el log quede bonito (ej: carpeta/documento.pdf)
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
                \IntranetGestoria\Trash\TrashLogger::registerDeletion($base_dir . $ver_cliente . '/', $ruta_relativa, 'CARPETA', $username);
                @rmdir($full_path); // Borramos la carpeta principal

            } else {
                // Es un archivo suelto seleccionado directamente
                TrashManager::moveToTrash($base_dir . $ver_cliente . '/', $full_path, $username);
            }
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

    private static function handlePromoteToWorker() {
        if (!current_user_can('edit_published_posts')) wp_die('Acceso denegado.');
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        if (!$user_id) wp_die('Usuario no válido.');

        $user = get_userdata($user_id);
        if (!$user) wp_die('Usuario no encontrado.');

        if (in_array('administrator', $user->roles)) {
            wp_die('No se puede convertir a un administrador en trabajador.');
        }

        $user->set_role(IG_ROLE_WORKER);
        delete_user_meta($user_id, IG_META_ASSIGNED_WORKER);

        wp_safe_redirect(add_query_arg('msg', 'promoted', home_url('/customer-area/dashboard/')));
        exit;
    }

    private static function handleDemoteToClient() {
        if (!current_user_can('edit_published_posts')) wp_die('Acceso denegado.');
        
        $worker_id = isset($_POST['worker_id']) ? intval($_POST['worker_id']) : 0;
        if (!$worker_id) wp_die('Trabajador no válido.');

        $user = get_userdata($worker_id);
        if (!$user) wp_die('Usuario no encontrado.');

        if (in_array('administrator', $user->roles)) {
            wp_die('No se puede degradar a un administrador.');
        }

        // 1. Cambio de rol a nivel WP
        $user->set_role('subscriber');

        // 2. Limpieza forzada de base de datos
        global $wpdb;
        $cap_key = $wpdb->get_blog_prefix() . 'capabilities';
        update_user_meta($worker_id, $cap_key, array('subscriber' => true));
        update_user_meta($worker_id, $wpdb->get_blog_prefix() . 'user_level', 0);

        // 3. Limpiar restos de metadatos antiguos
        $all_meta = get_user_meta($worker_id);
        foreach ($all_meta as $meta_key => $meta_values) {
            foreach ($meta_values as $meta_value) {
                $serialized = is_scalar($meta_value) ? $meta_value : maybe_serialize($meta_value);
                if (strpos($serialized, 'IG_ROLE_CLIENT') !== false || strpos($serialized, IG_ROLE_WORKER) !== false) {
                    delete_user_meta($worker_id, $meta_key);
                }
            }
        }

        // 4. Desvincular clientes
        $assigned_clients = ig_get_clients_by_worker($worker_id);
        foreach ($assigned_clients as $client) {
            delete_user_meta($client->ID, IG_META_ASSIGNED_WORKER);
        }

        wp_safe_redirect(add_query_arg('msg', 'demoted', home_url('/customer-area/dashboard/')));
        exit;
    }

    private static function handleAssignWorker() {
        if (!current_user_can('edit_published_posts')) wp_die('Acceso denegado.');
        
        $client_id = isset($_POST['client_id']) ? intval($_POST['client_id']) : 0;
        $worker_id = isset($_POST['worker_id']) ? intval($_POST['worker_id']) : 0;

        if (!$client_id || !$worker_id) wp_die('Datos no válidos.');

        if (!ig_is_worker($worker_id)) wp_die('El trabajador especificado no es válido.');

        update_user_meta($client_id, IG_META_ASSIGNED_WORKER, $worker_id);

        wp_safe_redirect(add_query_arg('msg', 'assigned', home_url('/customer-area/dashboard/')));
        exit;
    }

    private static function handleUnassignWorker() {
        if (!current_user_can('edit_published_posts')) wp_die('Acceso denegado.');
        
        $client_id = isset($_POST['client_id']) ? intval($_POST['client_id']) : 0;
        if (!$client_id) wp_die('Cliente no válido.');

        delete_user_meta($client_id, IG_META_ASSIGNED_WORKER);

        wp_safe_redirect(add_query_arg('msg', 'unassigned', home_url('/customer-area/dashboard/')));
        exit;
    }
}
