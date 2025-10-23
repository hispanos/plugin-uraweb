<?php
/**
 * Plugin Name: Uraweb
 * Description: Plugin for Uraweb Invoices
 * Version: 1.0.0
 * Author: Uraweb
 * Author URI: https://solucionesuraweb.com
 * License: GPLv2 or later
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('URAWEB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('URAWEB_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('URAWEB_VERSION', '1.0.0');

// Incluir archivos necesarios
require_once URAWEB_PLUGIN_PATH . 'includes/class-uraweb-admin.php';
require_once URAWEB_PLUGIN_PATH . 'includes/class-uraweb-api.php';

// Inicializar el plugin
function uraweb_init() {
    new Uraweb_Admin();
}
add_action('init', 'uraweb_init');

// Hook de activación
register_activation_hook(__FILE__, 'uraweb_activate');
function uraweb_activate() {
    // Crear opciones por defecto
    add_option('uraweb_api_url', '');
    add_option('uraweb_api_key', '');
}

// Hook de desactivación
register_deactivation_hook(__FILE__, 'uraweb_deactivate');
function uraweb_deactivate() {
    // Limpiar opciones si es necesario
}