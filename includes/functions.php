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
