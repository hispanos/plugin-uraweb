<?php
/**
 * Clase principal para el administrador del plugin Uraweb
 */

if (!defined('ABSPATH')) {
    exit;
}

class Uraweb_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_uraweb_get_invoices', array($this, 'ajax_get_invoices'));
    }
    
    /**
     * Agregar menú al dashboard de WordPress
     */
    public function add_admin_menu() {
        // Menú principal
        add_menu_page(
            'Facturas Uraweb',
            'Facturas',
            'manage_options',
            'uraweb-invoices',
            array($this, 'invoices_page'),
            'dashicons-cart',
            30
        );
        
        // Submenú de configuración
        add_submenu_page(
            'uraweb-invoices',
            'Configuración',
            'Configuración',
            'manage_options',
            'uraweb-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Página principal de facturas
     */
    public function invoices_page() {
        ?>
        <div class="wrap">
            <h1>Facturas Uraweb</h1>
            
            <div class="uraweb-filters">
                <h3>Filtros de Búsqueda</h3>
                <form method="post" id="uraweb-filter-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="start_date">Fecha de inicio:</label>
                            </th>
                            <td>
                                <input type="date" id="start_date" name="start_date" 
                                       value="<?php echo date('Y-m-01'); ?>" required>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="end_date">Fecha de fin:</label>
                            </th>
                            <td>
                                <input type="date" id="end_date" name="end_date" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" name="filter_invoices" class="button-primary uraweb-btn" value="Buscar Facturas">
                    </p>
                </form>
            </div>
            
            <div id="uraweb-loading" class="uraweb-loading" style="display: none;">
                Cargando facturas...
            </div>
            
            <div id="uraweb-results"></div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#uraweb-filter-form').on('submit', function(e) {
                e.preventDefault();
                
                var startDate = $('#start_date').val();
                var endDate = $('#end_date').val();
                
                if (!startDate || !endDate) {
                    alert('Por favor, selecciona ambas fechas.');
                    return;
                }
                
                $('#uraweb-loading').show();
                $('#uraweb-results').empty();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'uraweb_get_invoices',
                        start_date: startDate,
                        end_date: endDate,
                        nonce: '<?php echo wp_create_nonce('uraweb_nonce'); ?>'
                    },
                    success: function(response) {
                        $('#uraweb-loading').hide();
                        $('#uraweb-results').html(response);
                    },
                    error: function() {
                        $('#uraweb-loading').hide();
                        $('#uraweb-results').html('<p style="color: red;">Error al cargar las facturas.</p>');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Página de configuración
     */
    public function settings_page() {
        // Procesar formulario de configuración
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['uraweb_settings_nonce'], 'uraweb_settings')) {
            update_option('uraweb_api_url', sanitize_text_field($_POST['api_url']));
            update_option('uraweb_api_key', sanitize_text_field($_POST['api_key']));
            echo '<div class="notice notice-success"><p>Configuración guardada correctamente.</p></div>';
        }
        
        $api_url = get_option('uraweb_api_url', '');
        $api_key = get_option('uraweb_api_key', '');
        ?>
        <div class="wrap">
            <h1>Configuración de Uraweb</h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('uraweb_settings', 'uraweb_settings_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="api_url">URL del API:</label>
                        </th>
                        <td>
                            <input type="url" id="api_url" name="api_url" 
                                   value="<?php echo esc_attr($api_url); ?>" 
                                   class="regular-text" required>
                            <p class="description">URL base del API de facturas (ej: https://ventas.evircol.com/index.php/api/v1/sales)</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="api_key">API Key:</label>
                        </th>
                        <td>
                            <input type="text" id="api_key" name="api_key" 
                                   value="<?php echo esc_attr($api_key); ?>" 
                                   class="regular-text" required>
                            <p class="description">Clave de API para autenticación</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('Guardar Configuración'); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * AJAX handler para obtener facturas
     */
    public function ajax_get_invoices() {
        // Verificar nonce
        if (!wp_verify_nonce($_POST['nonce'], 'uraweb_nonce')) {
            wp_die('Error de seguridad');
        }
        
        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_die('Sin permisos');
        }
        
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);
        
        $api = new Uraweb_API();
        $invoices = $api->get_invoices($start_date, $end_date);
        
        if (is_wp_error($invoices)) {
            echo '<p style="color: red;">Error: ' . $invoices->get_error_message() . '</p>';
        } else {
            $this->display_invoices($invoices);
        }
        
        wp_die();
    }
    
    /**
     * Mostrar las facturas en una tabla
     */
    private function display_invoices($invoices) {
        if (empty($invoices)) {
            echo '<div class="uraweb-no-results">No se encontraron facturas para el período seleccionado.</div>';
            return;
        }
        
        $api = new Uraweb_API();
        $stats = $api->get_invoice_stats($invoices);
        
        ?>
        <div class="uraweb-stats">
            <div class="uraweb-stat-card">
                <h4>Total Facturas</h4>
                <div class="stat-value"><?php echo $stats['total_facturas']; ?></div>
            </div>
            <div class="uraweb-stat-card">
                <h4>Total Ventas</h4>
                <div class="stat-value">$<?php echo $stats['total_ventas']; ?></div>
            </div>
            <div class="uraweb-stat-card">
                <h4>Promedio por Venta</h4>
                <div class="stat-value">$<?php echo $stats['promedio_venta']; ?></div>
            </div>
            <div class="uraweb-stat-card">
                <h4>Facturas Activas</h4>
                <div class="stat-value"><?php echo $stats['facturas_activas']; ?></div>
            </div>
        </div>
        
        <div class="uraweb-table">
            <table>
                <thead>
                    <tr>
                        <th>ID Venta</th>
                        <th>Fecha</th>
                        <th>Total</th>
                        <th>Cliente</th>
                        <th>Empleado</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoices as $invoice): ?>
                    <tr>
                        <td><strong><?php echo esc_html($invoice->sale_id); ?></strong></td>
                        <td><?php echo esc_html($invoice->sale_time); ?></td>
                        <td><strong>$<?php echo number_format(floatval($invoice->total), 2); ?></strong></td>
                        <td><?php echo esc_html($invoice->customer_id); ?></td>
                        <td><?php echo esc_html($invoice->employee_id); ?></td>
                        <td>
                            <?php if ($invoice->deleted): ?>
                                <span class="uraweb-status-deleted">Eliminada</span>
                            <?php else: ?>
                                <span class="uraweb-status-active">Activa</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="uraweb-btn" onclick="urawebViewDetails('<?php echo esc_js($invoice->sale_id); ?>')">
                                Ver Detalles
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div id="uraweb-modal" class="uraweb-modal">
            <div class="uraweb-modal-content">
                <h3>Detalles de la Factura</h3>
                <div id="uraweb-modal-content"></div>
                <button class="uraweb-btn uraweb-btn-secondary" onclick="document.getElementById('uraweb-modal').style.display='none'">Cerrar</button>
            </div>
        </div>
        
        <script>
        function urawebViewDetails(saleId) {
            // Buscar la factura en los datos actuales
            var invoice = null;
            <?php foreach ($invoices as $invoice): ?>
            if ('<?php echo $invoice->sale_id; ?>' === saleId) {
                invoice = <?php echo json_encode($invoice); ?>;
            }
            <?php endforeach; ?>
            
            if (invoice) {
                var content = '<div class="uraweb-details">';
                content += '<h4>Información General</h4>';
                content += '<table>';
                content += '<tr><th>ID Venta:</th><td>' + invoice.sale_id + '</td></tr>';
                content += '<tr><th>Fecha:</th><td>' + invoice.sale_time + '</td></tr>';
                content += '<tr><th>Total:</th><td>$' + parseFloat(invoice.total).toFixed(2) + '</td></tr>';
                content += '<tr><th>Subtotal:</th><td>$' + parseFloat(invoice.subtotal).toFixed(2) + '</td></tr>';
                content += '<tr><th>Impuestos:</th><td>$' + parseFloat(invoice.tax).toFixed(2) + '</td></tr>';
                content += '<tr><th>Cliente:</th><td>' + invoice.customer_id + '</td></tr>';
                content += '<tr><th>Empleado:</th><td>' + invoice.employee_id + '</td></tr>';
                content += '<tr><th>Estado:</th><td>' + (invoice.deleted ? 'Eliminada' : 'Activa') + '</td></tr>';
                content += '</table>';
                
                if (invoice.payments && invoice.payments.length > 0) {
                    content += '<h4>Pagos (' + invoice.payments.length + ')</h4>';
                    content += '<table>';
                    content += '<tr><th>Tipo</th><th>Monto</th><th>Fecha</th></tr>';
                    invoice.payments.forEach(function(payment) {
                        content += '<tr>';
                        content += '<td>' + payment.payment_type + '</td>';
                        content += '<td>$' + parseFloat(payment.payment_amount).toFixed(2) + '</td>';
                        content += '<td>' + payment.payment_date + '</td>';
                        content += '</tr>';
                    });
                    content += '</table>';
                }
                
                if (invoice.cart_items && invoice.cart_items.length > 0) {
                    content += '<h4>Items (' + invoice.cart_items.length + ')</h4>';
                    content += '<table>';
                    content += '<tr><th>Descripción</th><th>Cantidad</th><th>Precio Unit.</th><th>Total</th></tr>';
                    invoice.cart_items.forEach(function(item) {
                        content += '<tr>';
                        content += '<td>' + item.description + '</td>';
                        content += '<td>' + item.quantity + '</td>';
                        content += '<td>$' + parseFloat(item.unit_price).toFixed(2) + '</td>';
                        content += '<td>$' + (parseFloat(item.unit_price) * item.quantity).toFixed(2) + '</td>';
                        content += '</tr>';
                    });
                    content += '</table>';
                }
                
                content += '</div>';
                document.getElementById('uraweb-modal-content').innerHTML = content;
            } else {
                document.getElementById('uraweb-modal-content').innerHTML = '<p>No se encontraron detalles para esta factura.</p>';
            }
            
            document.getElementById('uraweb-modal').style.display = 'block';
        }
        </script>
        <?php
    }
    
    /**
     * Encolar scripts y estilos del admin
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'uraweb') !== false) {
            wp_enqueue_script('jquery');
            wp_enqueue_style('uraweb-admin-css', URAWEB_PLUGIN_URL . 'assets/uraweb-admin.css', array(), URAWEB_VERSION);
        }
    }
}
