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
     * Obtener facturas desde la API
     */
    public function get_invoices($start_date, $end_date) {
        if (empty($this->api_url) || empty($this->api_key)) {
            return new WP_Error('no_config', 'La configuración del API no está completa. Ve a Configuración para configurar la URL y API Key.');
        }
        
        // Construir URL con parámetros
        $url = $this->api_url . '?start_date=' . urlencode($start_date) . '&end_date=' . urlencode($end_date);
        
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
    
    /**
     * Obtener detalles de una factura específica
     */
    public function get_invoice_details($sale_id) {
        if (empty($this->api_url) || empty($this->api_key)) {
            return new WP_Error('no_config', 'La configuración del API no está completa.');
        }
        
        // Construir URL para obtener detalles específicos
        $url = $this->api_url . '/' . urlencode($sale_id);
        
        $headers = array(
            'x-api-key: ' . $this->api_key,
            'Content-Type: application/json'
        );
        
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
        
        if ($curl_error) {
            return new WP_Error('curl_error', 'Error de conexión: ' . $curl_error);
        }
        
        if ($http_code !== 200) {
            return new WP_Error('http_error', 'Error HTTP ' . $http_code . ': ' . $response);
        }
        
        $data = json_decode($response);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', 'Error al decodificar la respuesta JSON: ' . json_last_error_msg());
        }
        
        return $data;
    }
    
    /**
     * Verificar la conexión con el API
     */
    public function test_connection() {
        if (empty($this->api_url) || empty($this->api_key)) {
            return new WP_Error('no_config', 'La configuración del API no está completa.');
        }
        
        // Hacer una petición de prueba con fechas recientes
        $end_date = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-7 days'));
        
        return $this->get_invoices($start_date, $end_date);
    }
    
    /**
     * Formatear datos de factura para mostrar
     */
    public function format_invoice_data($invoice) {
        $formatted = array(
            'id' => $invoice->sale_id,
            'fecha' => $invoice->sale_time,
            'total' => number_format(floatval($invoice->total), 2),
            'subtotal' => number_format(floatval($invoice->subtotal), 2),
            'impuestos' => number_format(floatval($invoice->tax), 2),
            'cliente' => $invoice->customer_id,
            'empleado' => $invoice->employee_id,
            'estado' => $invoice->deleted ? 'Eliminada' : 'Activa',
            'pagos' => count($invoice->payments),
            'items' => count($invoice->cart_items)
        );
        
        return $formatted;
    }
    
    /**
     * Obtener estadísticas de las facturas
     */
    public function get_invoice_stats($invoices) {
        if (empty($invoices)) {
            return array(
                'total_facturas' => 0,
                'total_ventas' => 0,
                'promedio_venta' => 0,
                'facturas_activas' => 0,
                'facturas_eliminadas' => 0
            );
        }
        
        $total_ventas = 0;
        $facturas_activas = 0;
        $facturas_eliminadas = 0;
        
        foreach ($invoices as $invoice) {
            $total_ventas += floatval($invoice->total);
            
            if ($invoice->deleted) {
                $facturas_eliminadas++;
            } else {
                $facturas_activas++;
            }
        }
        
        return array(
            'total_facturas' => count($invoices),
            'total_ventas' => number_format($total_ventas, 2),
            'promedio_venta' => number_format($total_ventas / count($invoices), 2),
            'facturas_activas' => $facturas_activas,
            'facturas_eliminadas' => $facturas_eliminadas
        );
    }
}
