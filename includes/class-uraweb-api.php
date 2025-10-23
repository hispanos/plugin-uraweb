<?php
/**
 * Clase para manejar las llamadas a la API de Uraweb
 */

if (!defined('ABSPATH')) {
    exit;
}

class Uraweb_API {
    
    private $api_url;
    private $api_key;
    
    public function __construct() {
        $this->api_url = get_option('uraweb_api_url', '');
        $this->api_key = get_option('uraweb_api_key', '');
    }
    
    /**
     * Verificar la conexión con el API (método genérico)
     */
    public function test_connection() {
        if (empty($this->api_url) || empty($this->api_key)) {
            return new WP_Error('no_config', 'La configuración del API no está completa.');
        }
        
        // Hacer una petición de prueba con el endpoint de ventas
        $sales = new Uraweb_Sales();
        return $sales->test_connection();
    }
}
