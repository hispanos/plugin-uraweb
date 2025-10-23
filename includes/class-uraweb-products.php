<?php
/**
 * Clase para manejar los productos de Uraweb
 */

if (!defined('ABSPATH')) {
    exit;
}

class Uraweb_Products {
    
    private $api_url;
    private $api_key;
    
    public function __construct() {
        $this->api_url = get_option('uraweb_api_url', '');
        $this->api_key = get_option('uraweb_api_key', '');
    }
    
    /**
     * Obtener productos desde la API
     */
    public function get_products($search_term = '', $category = '', $manufacturer = '') {
        if (empty($this->api_url) || empty($this->api_key)) {
            return new WP_Error('no_config', 'La configuración del API no está completa. Ve a Configuración para configurar la URL y API Key.');
        }
        
        // Construir URL para productos (concatenar /items al final)
        $url = rtrim($this->api_url, '/') . '/items';
        
        // Agregar parámetros de búsqueda si existen
        $params = array();
        if (!empty($search_term)) {
            $params['search'] = $search_term;
        }
        if (!empty($category)) {
            $params['category'] = $category;
        }
        if (!empty($manufacturer)) {
            $params['manufacturer'] = $manufacturer;
        }
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        return $this->make_api_request($url);
    }
    
    /**
     * Obtener detalles de un producto específico
     */
    public function get_product_details($item_id) {
        if (empty($this->api_url) || empty($this->api_key)) {
            return new WP_Error('no_config', 'La configuración del API no está completa.');
        }
        
        // Construir URL para obtener detalles específicos
        $url = rtrim($this->api_url, '/') . '/items/' . urlencode($item_id);
        
        return $this->make_api_request($url);
    }
    
    /**
     * Verificar la conexión con el API
     */
    public function test_connection() {
        if (empty($this->api_url) || empty($this->api_key)) {
            return new WP_Error('no_config', 'La configuración del API no está completa.');
        }
        
        // Hacer una petición de prueba
        return $this->get_products();
    }
    
    /**
     * Formatear datos de producto para mostrar
     */
    public function format_product_data($product) {
        $formatted = array(
            'id' => $product->item_id,
            'nombre' => $product->name,
            'numero' => $product->item_number,
            'categoria' => $product->category,
            'fabricante' => $product->manufacturer,
            'precio' => number_format(floatval($product->unit_price), 2),
            'costo' => number_format(floatval($product->cost_price), 2),
            'tipo' => $product->is_service ? 'Servicio' : 'Producto',
            'descripcion' => $product->description,
            'stock_total' => $this->calculate_total_stock($product)
        );
        
        return $formatted;
    }
    
    /**
     * Calcular el stock total de un producto
     */
    public function calculate_total_stock($product) {
        $total_stock = 0;
        if (!empty($product->locations)) {
            foreach ($product->locations as $location) {
                $total_stock += intval($location->quantity);
            }
        }
        return $total_stock;
    }
    
    /**
     * Obtener estadísticas de los productos
     */
    public function get_product_stats($products) {
        if (empty($products)) {
            return array(
                'total_products' => 0,
                'active_products' => 0,
                'services' => 0,
                'with_inventory' => 0
            );
        }
        
        $active_products = 0;
        $services = 0;
        $with_inventory = 0;
        
        foreach ($products as $product) {
            if (!$product->is_service) {
                $active_products++;
                
                // Verificar si tiene inventario
                if (!empty($product->locations)) {
                    $has_stock = false;
                    foreach ($product->locations as $location) {
                        if (intval($location->quantity) > 0) {
                            $has_stock = true;
                            break;
                        }
                    }
                    if ($has_stock) {
                        $with_inventory++;
                    }
                }
            } else {
                $services++;
            }
        }
        
        return array(
            'total_products' => count($products),
            'active_products' => $active_products,
            'services' => $services,
            'with_inventory' => $with_inventory
        );
    }
    
    /**
     * Obtener categorías únicas de los productos
     */
    public function get_categories($products) {
        $categories = array();
        if (!empty($products)) {
            foreach ($products as $product) {
                if (!empty($product->category) && !in_array($product->category, $categories)) {
                    $categories[] = $product->category;
                }
            }
        }
        sort($categories);
        return $categories;
    }
    
    /**
     * Obtener fabricantes únicos de los productos
     */
    public function get_manufacturers($products) {
        $manufacturers = array();
        if (!empty($products)) {
            foreach ($products as $product) {
                if (!empty($product->manufacturer) && !in_array($product->manufacturer, $manufacturers)) {
                    $manufacturers[] = $product->manufacturer;
                }
            }
        }
        sort($manufacturers);
        return $manufacturers;
    }
    
    /**
     * Realizar petición a la API
     */
    private function make_api_request($url) {
        // Configurar headers
        $headers = array(
            'x-api-key: ' . $this->api_key,
            'Content-Type: application/json'
        );
        
        // Realizar petición cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        // Verificar errores de cURL
        if ($curl_error) {
            return new WP_Error('curl_error', 'Error de conexión: ' . $curl_error);
        }
        
        // Verificar código de respuesta HTTP
        if ($http_code !== 200) {
            return new WP_Error('http_error', 'Error HTTP ' . $http_code . ': ' . $response);
        }
        
        // Decodificar respuesta JSON
        $data = json_decode($response);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', 'Error al decodificar la respuesta JSON: ' . json_last_error_msg());
        }
        
        // Verificar si la respuesta es un array
        if (!is_array($data)) {
            return new WP_Error('invalid_response', 'La respuesta del API no es un array válido.');
        }
        
        return $data;
    }
}
