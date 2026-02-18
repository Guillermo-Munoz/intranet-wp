<?php
if (!defined('ABSPATH')) exit;

function intranet_get_client_folder($user_id, $display_name) {
    return 'cliente-' . $user_id . '-' . sanitize_title($display_name);
}

function intranet_get_client_base_path($user_id, $display_name) {
    return INTRANET_CLIENTS_DIR . intranet_get_client_folder($user_id, $display_name) . '/';
}

function intranet_ensure_directory($path) {
    if (!file_exists($path)) {
        wp_mkdir_p($path);
        file_put_contents($path . 'index.php', '<?php // Privado');
    }
    return $path;
}

function intranet_ensure_trash_directory($base_path) {
    $trash_path = $base_path . INTRANET_TRASH_FOLDER . '/';
    if (!file_exists($trash_path)) {
        wp_mkdir_p($trash_path);
        file_put_contents($trash_path . 'index.php', '<?php // Privado - Papelera');
    }
    return $trash_path;
}

function intranet_ensure_year_directory($base_path) {
    $year_path = $base_path . INTRANET_YEAR . '/';
    if (!file_exists($year_path)) {
        wp_mkdir_p($year_path);
    }
    return $year_path;
}

function intranet_validate_extension($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, INTRANET_ALLOWED_EXT);
}

function intranet_validate_path($path, $base_dir) {
    $real_path = realpath($path);
    $real_base = realpath($base_dir);
    
    if (!$real_path || !$real_base) return false;
    return strpos($real_path, $real_base) === 0;
}

function intranet_is_in_trash($path) {
    return strpos($path, INTRANET_TRASH_FOLDER) !== false;
}

/**
 * Obtiene todos los usuarios con rol trabajador
 * @return WP_User[] Array de objetos WP_User
 */
function ig_get_all_workers() {
    $args = array(
        'role' => IG_ROLE_WORKER,
        'orderby' => 'display_name',
        'order' => 'ASC'
    );

    $user_query = new WP_User_Query($args);
    return $user_query->get_results();
}

/**
 * Obtiene todos los clientes asignados a un trabajador específico
 * @param int $worker_id ID del trabajador
 * @return WP_User[] Array de objetos WP_User
 */
function ig_get_clients_by_worker($worker_id) {
    $args = array(
        'meta_key' => IG_META_ASSIGNED_WORKER,
        'meta_value' => $worker_id,
        'orderby' => 'display_name',
        'order' => 'ASC'
    );

    $user_query = new WP_User_Query($args);
    return $user_query->get_results();
}

/**
 * Obtiene el trabajador asignado a un cliente específico
 * @param int $client_id ID del cliente
 * @return WP_User|null Objeto WP_User del trabajador o null si no tiene asignado
 */
function ig_get_worker_by_client($client_id) {
    $worker_id = get_user_meta($client_id, IG_META_ASSIGNED_WORKER, true);

    if (!$worker_id) {
        return null;
    }

    $worker = get_userdata($worker_id);

    // Verificar que el usuario existe y es trabajador
    if ($worker && in_array(IG_ROLE_WORKER, $worker->roles)) {
        return $worker;
    }

    return null;
}

/**
 * Verifica si un usuario es trabajador
 * @param int $user_id ID del usuario
 * @return bool True si es trabajador, false si no
 */
function ig_is_worker($user_id) {
    $user = get_userdata($user_id);

    if (!$user) {
        return false;
    }

    return in_array(IG_ROLE_WORKER, $user->roles);
}

/**
 * Obtiene todos los clientes sin trabajador asignado
 * @return WP_User[] Array de objetos WP_User
 */
function ig_get_unassigned_clients() {
    $args = array(
        'role__in' => array('subscriber', 'author', 'customer'),
        'meta_query' => array(
            'relation' => 'OR',
            array(
                'key' => IG_META_ASSIGNED_WORKER,
                'compare' => 'NOT EXISTS'
            ),
            array(
                'key' => IG_META_ASSIGNED_WORKER,
                'value' => '',
                'compare' => '='
            )
        ),
        'orderby' => 'display_name',
        'order' => 'ASC'
    );

    $user_query = new WP_User_Query($args);
    $results = $user_query->get_results();

    // Filtrar administradores y trabajadores
    return array_filter($results, function($user) {
        return !in_array('administrator', $user->roles) && !in_array(IG_ROLE_WORKER, $user->roles);
    });
}
