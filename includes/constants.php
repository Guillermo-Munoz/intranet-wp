<?php
if (!defined('ABSPATH')) exit;

$upload_dir = wp_upload_dir();

// Paths
define('INTRANET_UPLOAD_BASE', $upload_dir['basedir']);
define('INTRANET_CLIENTS_DIR', INTRANET_UPLOAD_BASE . '/clientes/');
define('INTRANET_UPLOAD_URL', $upload_dir['baseurl']);

// Folders
define('INTRANET_TRASH_FOLDER', '_gs_papelera');
define('INTRANET_YEAR', date('Y'));

// Extensions permitidas
define('INTRANET_ALLOWED_EXT', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif', 'zip', 'rar', 'txt', 'csv']);

// Security
define('INTRANET_MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB

// UI
define('INTRANET_ITEMS_PER_PAGE', 50);

// Roles
define('IG_ROLE_WORKER', 'trabajador');
define('IG_META_ASSIGNED_WORKER', '_ig_assigned_worker_id');
