<?php
/**
 * Plugin Name: Intranet Gestoría - Refactorizado
 * Description: Sistema de gestión de documentos y auditoría de borrados
 * Version: 2.0.0
 * Author: Williams
 * Text Domain: intranet-gestoria
 */

if (!defined('ABSPATH')) exit;

// Definir constantes de rutas
define('INTRANET_GESTORIA_PATH', plugin_dir_path(__FILE__));
define('INTRANET_GESTORIA_URL', plugin_dir_url(__FILE__));

// Cargar autoloader
require_once INTRANET_GESTORIA_PATH . 'includes/autoload.php';
require_once INTRANET_GESTORIA_PATH . 'includes/constants.php';
require_once INTRANET_GESTORIA_PATH . 'includes/functions.php';



// Inicializar plugin
add_action('plugins_loaded', 'intranet_gestoria_init');
function intranet_gestoria_init() {
    

     // Acción para procesar las descargas seguras
   add_action('wp_ajax_ig_descarga', 'intranet_gestoria_procesar_descarga');
    add_action('wp_ajax_nopriv_ig_descarga', 'intranet_gestoria_procesar_descarga');

    // Registrar el rol de trabajador
    add_role(
        IG_ROLE_WORKER,
        'Trabajador',
        array(
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
        )
    );

    // Registrar shortcodes
    add_shortcode('area_cliente_simple', function() {
        return IntranetGestoria\Client\ClientUI::render();
    });

    add_shortcode('admin_ver_simple', function() {
        return IntranetGestoria\Admin\AdminUI::render();
    });

    add_shortcode('area_trabajador', function() {
        return IntranetGestoria\Worker\WorkerUI::render();
    });

    // Procesar acciones POST
    // Cambia esto para 
    // IntranetGestoria\Client\ClientActions::handle();
    // IntranetGestoria\Admin\AdminActions::handle();
    // IntranetGestoria\Worker\WorkerActions::handle();

    // Por esto, FUERA de intranet_gestoria_init():
    //El hook wp se ejecuta cuando WordPress ya está completamente cargado, todos los plugins activos y WP Mail SMTP listo para enviar. Es el momento correcto para procesar formularios y enviar emails.
    add_action('wp', function() {
        IntranetGestoria\Client\ClientActions::handle();
        IntranetGestoria\Admin\AdminActions::handle();
        IntranetGestoria\Worker\WorkerActions::handle();
    });
    // Menu y UI
    add_filter('show_admin_bar', 'intranet_gestoria_hide_admin_bar');
    add_action('admin_menu', 'intranet_gestoria_custom_menu');
    add_filter('wp_nav_menu_items', 'intranet_gestoria_menu_items', 10, 2);
}

function intranet_gestoria_hide_admin_bar($show) {
    if (current_user_can('author') || ig_is_worker(get_current_user_id())) return false;
    return $show;
}

function intranet_gestoria_custom_menu() {
    if (current_user_can('author')) {
        global $menu;
        add_menu_page(
            'Área Clientes',
            '🏠 Área Clientes',
            'read',
            'volver_web',
            'intranet_gestoria_redirect',
            'dashicons-external',
            1
        );
        $permitidos = array('volver_web', 'users.php', 'profile.php');
        foreach ($menu as $key => $item) {
            if (!in_array($item[2], $permitidos)) {
                unset($menu[$key]);
            }
        }
    }

    if (ig_is_worker(get_current_user_id())) {
        global $menu;
        add_menu_page(
            'Mis Clientes',
            '👥 Mis Clientes',
            'read',
            'trabajador_clientes',
            'intranet_gestoria_redirect_worker',
            'dashicons-groups',
            1
        );
        $permitidos = array('trabajador_clientes', 'profile.php');
        foreach ($menu as $key => $item) {
            if (!in_array($item[2], $permitidos)) {
                unset($menu[$key]);
            }
        }
    }
}

function intranet_gestoria_redirect() {
    wp_redirect(home_url('/customer-area/dashboard/'));
    exit;
}

function intranet_gestoria_redirect_worker() {
    wp_redirect(home_url('/area-trabajador/'));
    exit;
}

function intranet_gestoria_menu_items($items, $args) {
    if ($args->theme_location == 'primary-menu' && is_user_logged_in()) {
        $es_gestoria = current_user_can('author');
        $es_trabajador = ig_is_worker(get_current_user_id());

        // Determinar título y URL
        if ($es_gestoria) {
            $titulo = 'ADMIN';
            $url = home_url('/dashboard/');
        } elseif ($es_trabajador) {
            $titulo = 'TRABAJADOR';
            $url = home_url('/area-trabajador/');
        } else {
            $titulo = 'MI CUENTA';
            $url = home_url('/customer-area/dashboard/');
        }

        $nuevo_item = '<li class="menu-item menu-item-has-children intranet-icon">
            <a href="' . esc_url($url) . '" style="font-weight:bold; color:#003B77;">
                <i class="dashicons dashicons-admin-generic" style="margin-right:5px;"></i>' . $titulo . '
            </a>
            <ul class="sub-menu">';

        if ($es_gestoria) {
            $nuevo_item .= '<li class="menu-item"><a href="' . home_url('/dashboard/') . '">📊 Panel Gestión</a></li>';
            $nuevo_item .= '<li class="menu-item"><a href="' . admin_url('users.php?role=subscriber') . '">👥 Lista Clientes</a></li>';
            $nuevo_item .= '<li class="menu-item"><a href="' . admin_url('users.php?role=' . IG_ROLE_WORKER) . '">👷 Lista Trabajadores</a></li>';
        } elseif ($es_trabajador) {
            $nuevo_item .= '<li class="menu-item"><a href="' . home_url('/area-trabajador/') . '">👥 Mis Clientes</a></li>';
        } else {
            $nuevo_item .= '<li class="menu-item"><a href="' . home_url('/my-files/') . '">📁 Mis Archivos</a></li>';
        }

        $nuevo_item .= '<li class="menu-item"><a href="' . wp_logout_url(home_url()) . '" style="color:#cc0000 !important;">🚪 Cerrar Sesión</a></li>';
        $nuevo_item .= '</ul></li>';

        $items .= $nuevo_item;
    }
    return $items;
}

// function intranet_gestoria_procesar_descarga() {
//     if (!is_user_logged_in()) wp_die("Inicia sesión.");

//     $archivo = isset($_GET['archivo']) ? $_GET['archivo'] : '';
//     if (empty($archivo)) wp_die("Falta archivo.");

//     $upload_dir = wp_upload_dir();
//     $base_dir = $upload_dir['basedir'] . '/clientes/';
//     $full_path = realpath($base_dir . $archivo);

//     if ($full_path === false || strpos($full_path, realpath($base_dir)) !== 0) {
//         wp_die("Acceso no válido.");
//     }

//     $user = wp_get_current_user();
//     $partes = explode('/', $archivo);
//     $carpeta_cliente = $partes[0]; 
//     $partes_carpeta = explode('-', $carpeta_cliente);
//     $id_propietario = isset($partes_carpeta[1]) ? intval($partes_carpeta[1]) : 0;

//     $es_gestor = current_user_can('author') || ig_is_worker($user->ID);
//     $es_dueno = ($user->ID == $id_propietario);

//     if (file_exists($full_path) && ($es_gestor || $es_dueno)) {
//         // --- LIMPIEZA CRÍTICA PARA EVITAR EL ERROR DE "NO PERMITIDO" ---
//         if (ob_get_level()) ob_end_clean(); 

//         $mime = wp_check_filetype($full_path)['type'] ?: 'application/octet-stream';
        
//         header('Content-Description: File Transfer');
//         header('Content-Type: ' . $mime);
//         header('Content-Disposition: attachment; filename="' . basename($full_path) . '"'); // attachment fuerza la descarga
//         header('Content-Transfer-Encoding: binary');
//         header('Content-Length: ' . filesize($full_path));
//         header('Cache-Control: must-revalidate');
//         header('Pragma: public');
        
//         readfile($full_path);
//         exit;
//     }
//     wp_die("No tienes permiso.");
// }
function intranet_gestoria_procesar_descarga() {
    if (!is_user_logged_in()) wp_die("Inicia sesión.");

    $archivo = isset($_GET['archivo']) ? $_GET['archivo'] : '';
    if (empty($archivo)) wp_die("Falta archivo.");
    
    // Limpiar el archivo: quitar slash inicial si existe
    $archivo = ltrim($archivo, '/');

    $upload_dir = wp_upload_dir();
    $base_dir = $upload_dir['basedir'] . '/clientes/';
    
    // Construir ruta sin doble slash
    $ruta_completa = $base_dir . $archivo;
    
    // Normalizar la ruta (reemplazar // por /)
    $ruta_completa = str_replace('//', '/', $ruta_completa);
    
    $full_path = realpath($ruta_completa);

    if ($full_path === false || strpos($full_path, realpath($base_dir)) !== 0) {
        wp_die("Acceso no válido. El archivo no existe en la ruta esperada.");
    }

    $user = wp_get_current_user();
    $partes = explode('/', $archivo);
    $carpeta_cliente = $partes[0]; 
    
    // OJO: el $archivo empieza con "2026" no con "cliente-XX"
    // Por eso el ID propietario da 0
    
    // Verificar si es una carpeta de cliente o no
    if (strpos($carpeta_cliente, 'cliente-') === 0) {
        $partes_carpeta = explode('-', $carpeta_cliente);
        $id_propietario = isset($partes_carpeta[1]) ? intval($partes_carpeta[1]) : 0;
    } else {
        // Es una carpeta de año (2026) - permitir acceso solo a gestores
        $id_propietario = 0;
    }

    $es_gestor = current_user_can('author') || ig_is_worker($user->ID);
    $es_dueno = ($user->ID == $id_propietario);

    // Si es carpeta de año, solo gestores pueden acceder
    if (strpos($carpeta_cliente, 'cliente-') !== 0 && !$es_gestor) {
        wp_die("No tienes permiso para acceder a archivos del sistema.");
    }

    if (file_exists($full_path) && ($es_gestor || $es_dueno)) {
        if (ob_get_level()) ob_end_clean(); 

        $mime = wp_check_filetype($full_path)['type'] ?: 'application/octet-stream';
        
        $nombre_archivo = basename($full_path);
        $nombre_limpio = str_replace('_gs_', '', $nombre_archivo);
        
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mime);
        
        if (isset($_GET['view']) && $_GET['view'] == 1) {
            header('Content-Disposition: inline; filename="' . $nombre_limpio . '"');
        } else {
            header('Content-Disposition: attachment; filename="' . $nombre_limpio . '"');
        }
        
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . filesize($full_path));
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        
        readfile($full_path);
        exit;
    }
    wp_die("No tienes permiso.");
}
/**
 * OCULTAR BARRA DE ADMINISTRACIÓN (TANTO EN ADMIN COMO EN WEB)
 * Mantiene el hueco superior y usa tu lógica de $es_gestor.
 */
function ig_quitar_barra() {
    $user = wp_get_current_user();
    // Usamos tu comprobación exacta de permisos
    $es_gestor = current_user_can('author') || ig_is_worker($user->ID);
    
    if ($es_gestor) {
        echo '<style type="text/css">
            /* Oculta la barra físicamente pero mantiene el espacio */
            #wpadminbar, .nojq #wpadminbar { 
                display: none !important; 
            }
        </style>';
    }
}

// /**
//  * Concede al rol "author" (gestoría) capacidad para acceder
//  * al listado de usuarios del panel de WordPress.
//  */
// add_filter('user_has_cap', function($caps, $cap_requested, $args, $user) {
//     if (!in_array('author', $user->roles)) return $caps;
    
//     if (in_array($cap_requested[0], ['list_users', 'edit_users'])) {
//         $caps[$cap_requested[0]] = true;
//     }
//     return $caps;
// }, 10, 4);

// /**
//  * Restringe qué usuarios puede ver el "author" en users.php.
//  * Solo se muestran clientes (subscriber) y trabajadores, 
//  * nunca administradores u otros roles internos.
//  */
// add_action('pre_user_query', function($query) {
//     if (!current_user_can('author') || current_user_can('administrator')) return;
    
//     global $wpdb;
    
//     $query->query_where .= " AND ID IN (
//         SELECT user_id FROM {$wpdb->usermeta}
//         WHERE meta_key = '{$wpdb->prefix}capabilities'
//         AND (meta_value LIKE '%subscriber%' OR meta_value LIKE '%" . IG_ROLE_WORKER . "%')
//     )";
// });

// Los ganchos que inyectan el CSS en la cabecera
add_action('admin_head', 'ig_quitar_barra', 999);
add_action('wp_head', 'ig_quitar_barra', 999);


// Estilos y scripts
add_action('wp_head', function() {
    echo '<style>
        .cliente-container { max-width: 1000px; margin: 20px auto; font-family: sans-serif; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); background: #fff; border: 1px solid #ddd; }
        .cliente-header { background: #003B77; color: #fff; padding: 20px; display: flex; justify-content: space-between; align-items: center; }
        .breadcrumb-cl { padding: 12px 20px; background: #f8f9fa; border-bottom: 1px solid #eee; font-size: 14px; }
        .drop-zone-cl { margin: 20px; padding: 30px; border: 2px dashed #003B77; border-radius: 8px; text-align: center; background: #fcfcfc; cursor: pointer; transition: 0.3s; }
        .drop-zone-cl:hover { background: #f0f7ff; }
        .search-cl { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 15px; }
        .cl-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .cl-table th { background: #f4f7f9; color: #333; text-align: left; padding: 12px; border-bottom: 2px solid #dee2e6; font-size: 13px; text-transform: uppercase; }
        .cl-table td { padding: 12px; border-bottom: 1px solid #eee; vertical-align: middle; font-size: 14px; }
        .badge-autor { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; }
        .badge-cliente { background: #e3f2fd; color: #1976d2; }
        .badge-gestor { background: #fff3e0; color: #e65100; }
        .badge-sistema { background: #f5f5f5; color: #616161; }
        .badge-papelera { background: #ffebee; color: #c62828; }
        #loading-cl, #loading-gs { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.9); z-index: 99999; flex-direction: column; justify-content: center; align-items: center; }
        .spinner-cl, .spinner-gs { width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #003B77; border-radius: 50%; animation: spin 1s linear infinite; margin-bottom: 15px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .audit-tab-buttons { margin: 15px 0; display: flex; gap: 10px; }
        .audit-tab-btn { padding: 8px 16px; border: 1px solid #ddd; background: #f5f5f5; cursor: pointer; border-radius: 5px; transition: 0.3s; font-size: 14px; }
        .audit-tab-btn.active { background: #003B77; color: white; border-color: #003B77; }
        .audit-tab-btn:hover { background: #e8f1f7; }
        details { cursor: pointer; border: 1px solid #ffc107; border-radius: 5px; padding: 12px; background: #fff9e6; margin: 15px 0; }
        summary { font-weight: bold; color: #cc8800; user-select: none; }
        summary::-webkit-details-marker { margin-right: 8px; }
        details div { margin-top: 12px; padding-top: 12px; border-top: 1px solid #ffc107; font-family: monospace; font-size: 12px; color: #333; max-height: 300px; overflow-y: auto; }
    </style>';
});
