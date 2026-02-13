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
    
    // Registrar shortcodes
    add_shortcode('area_cliente_simple', function() {
        return IntranetGestoria\Client\ClientUI::render();
    });
    
    add_shortcode('admin_ver_simple', function() {
        return IntranetGestoria\Admin\AdminUI::render();
    });
    
    // Procesar acciones POST
    IntranetGestoria\Client\ClientActions::handle();
    IntranetGestoria\Admin\AdminActions::handle();
    
    // Menu y UI
    add_filter('show_admin_bar', 'intranet_gestoria_hide_admin_bar');
    add_action('admin_menu', 'intranet_gestoria_custom_menu');
    add_filter('wp_nav_menu_items', 'intranet_gestoria_menu_items', 10, 2);
}

function intranet_gestoria_hide_admin_bar($show) {
    if (current_user_can('author')) return false;
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
}

function intranet_gestoria_redirect() {
    wp_redirect(home_url('/customer-area/dashboard/'));
    exit;
}

function intranet_gestoria_menu_items($items, $args) {
    if ($args->theme_location == 'primary-menu' && is_user_logged_in()) {
        $es_gestoria = current_user_can('author');
        $titulo = $es_gestoria ? 'ADMIN' : 'MI CUENTA';
        $url = $es_gestoria ? home_url('/dashboard/') : home_url('/customer-area/dashboard/');
        
        $nuevo_item = '<li class="menu-item menu-item-has-children intranet-icon">
            <a href="' . esc_url($url) . '" style="font-weight:bold; color:#003B77;">
                <i class="dashicons dashicons-admin-generic" style="margin-right:5px;"></i>' . $titulo . '
            </a>
            <ul class="sub-menu">';
        
        if ($es_gestoria) {
            $nuevo_item .= '<li class="menu-item"><a href="' . home_url('/dashboard/') . '">📊 Panel Gestión</a></li>';
            $nuevo_item .= '<li class="menu-item"><a href="' . admin_url('users.php') . '">👥 Lista Clientes</a></li>';
        } else {
            $nuevo_item .= '<li class="menu-item"><a href="' . home_url('/my-files/') . '">📁 Mis Archivos</a></li>';
        }
        
        $nuevo_item .= '<li class="menu-item"><a href="' . wp_logout_url(home_url()) . '" style="color:#cc0000 !important;">🚪 Cerrar Sesión</a></li>';
        $nuevo_item .= '</ul></li>';
        
        $items .= $nuevo_item;
    }
    return $items;
}

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
