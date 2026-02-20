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
 */
function ig_get_all_workers() {
    $user_query = new WP_User_Query([
        'role'    => IG_ROLE_WORKER,
        'orderby' => 'display_name',
        'order'   => 'ASC'
    ]);
    return $user_query->get_results();
}

/**
 * Obtiene todos los clientes registrados (rol subscriber)
 */
function ig_get_all_clients() {
    $user_query = new WP_User_Query([
        'role'    => 'subscriber',
        'orderby' => 'display_name',
        'order'   => 'ASC',
        'fields'  => 'all'
    ]);
    return $user_query->get_results();
}

/**
 * Obtiene todos los clientes asignados a un trabajador específico
 */
function ig_get_clients_by_worker($worker_id) {
    $user_query = new WP_User_Query([
        'meta_key'   => IG_META_ASSIGNED_WORKER,
        'meta_value' => $worker_id,
        'orderby'    => 'display_name',
        'order'      => 'ASC'
    ]);
    return $user_query->get_results();
}

/**
 * Obtiene TODOS los trabajadores asignados a un cliente (soporta múltiples)
 * @param int $client_id
 * @return WP_User[] Array de trabajadores
 */
function ig_get_workers_by_client($client_id) {
    $worker_ids = get_user_meta($client_id, IG_META_ASSIGNED_WORKER);
    if (empty($worker_ids)) return [];

    $workers = [];
    foreach ($worker_ids as $worker_id) {
        $worker = get_userdata(intval($worker_id));
        if ($worker) {
            $workers[] = $worker;
        }
    }
    return $workers;
}

/**
 * Mantiene compatibilidad con código anterior — devuelve el primer trabajador
 * @param int $client_id
 * @return WP_User|null
 */
function ig_get_worker_by_client($client_id) {
    $workers = ig_get_workers_by_client($client_id);
    return !empty($workers) ? $workers[0] : null;
}

/**
 * Asigna un trabajador a un cliente (permite múltiples, evita duplicados)
 */
function ig_assign_worker_to_client($client_id, $worker_id) {
    $existing = get_user_meta($client_id, IG_META_ASSIGNED_WORKER);
    if (!in_array($worker_id, $existing)) {
        add_user_meta($client_id, IG_META_ASSIGNED_WORKER, $worker_id);
    }
}

/**
 * Desvincula un trabajador concreto de un cliente
 */
function ig_unassign_worker_from_client($client_id, $worker_id) {
    delete_user_meta($client_id, IG_META_ASSIGNED_WORKER, $worker_id);
}

/**
 * Obtiene todos los usuarios con rol trabajador sin filtrar
 */
function ig_get_all_workers_unfiltered() {
    $user_query = new WP_User_Query([
        'role'    => IG_ROLE_WORKER,
        'orderby' => 'display_name',
        'order'   => 'ASC'
    ]);
    return $user_query->get_results();
}

/**
 * Verifica si un usuario es trabajador
 */
function ig_is_worker($user_id) {
    $user = get_userdata($user_id);
    if (!$user) return false;
    return in_array(IG_ROLE_WORKER, $user->roles);
}

/**
 * Obtiene todos los clientes sin ningún trabajador asignado
 */
function ig_get_unassigned_clients() {
    $args = [
        'role__in'   => ['subscriber', 'author', 'customer'],
        'meta_query' => [
            'relation' => 'OR',
            [
                'key'     => IG_META_ASSIGNED_WORKER,
                'compare' => 'NOT EXISTS'
            ],
            [
                'key'     => IG_META_ASSIGNED_WORKER,
                'value'   => '',
                'compare' => '='
            ]
        ],
        'orderby' => 'display_name',
        'order'   => 'ASC'
    ];

    $user_query = new WP_User_Query($args);
    $results    = $user_query->get_results();

    return array_filter($results, function($user) {
        return !in_array('administrator', $user->roles) && !in_array(IG_ROLE_WORKER, $user->roles);
    });
}