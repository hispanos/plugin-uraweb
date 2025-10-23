# Plugin Uraweb para WordPress

## Descripción

Este plugin permite consultar facturas desde una API externa y mostrarlas en el dashboard de WordPress. Está diseñado específicamente para integrar con el sistema de facturación de Uraweb.

## Características

- **Menú del Dashboard**: Nuevo menú "Facturas" en el admin de WordPress
- **Configuración**: Página de configuración para URL del API y API Key
- **Consulta de Facturas**: Interfaz para buscar facturas por rango de fechas
- **Estadísticas**: Muestra estadísticas de las facturas encontradas
- **Detalles Completos**: Modal con información detallada de cada factura
- **Diseño Responsivo**: Interfaz adaptada para diferentes tamaños de pantalla

## Instalación

1. Sube la carpeta `uraweb` al directorio `/wp-content/plugins/`
2. Activa el plugin desde el panel de administración de WordPress
3. Ve a "Facturas" > "Configuración" para configurar la URL del API y API Key

## Configuración

### URL del API
Ejemplo: `https://ventas.evircol.com/index.php/api/v1/sales`

### API Key
Tu clave de API para autenticación con el servicio.

## Uso

1. **Configurar el Plugin**:
   - Ve a "Facturas" > "Configuración"
   - Ingresa la URL del API y tu API Key
   - Guarda la configuración

2. **Consultar Facturas**:
   - Ve a "Facturas" en el menú principal
   - Selecciona el rango de fechas
   - Haz clic en "Buscar Facturas"

3. **Ver Detalles**:
   - Haz clic en "Ver Detalles" en cualquier factura
   - Se abrirá un modal con información completa

## Estructura de Datos

El plugin maneja la siguiente estructura de datos de las facturas:

```typescript
interface Sale {
    sale_id: string;
    sale_time: string;
    location_id: string;
    points_used: number;
    points_gained: number;
    employee_id: string;
    deleted: boolean;
    register_id: string;
    mode: 'sale' | string;
    customer_id: string;
    show_comment_on_receipt: boolean;
    selected_tier_id: string | null;
    sold_by_employee_id: string;
    discount_reason: string;
    excluded_taxes: any[];
    has_delivery: boolean;
    delivery: DeliveryDetails;
    paid_store_account_ids: any[];
    suspended: '0' | string;
    subtotal: string;
    tax: string;
    total: string;
    profit: string;
    payments: Payment[];
    cart_items: CartItem[];
}
```

## Archivos del Plugin

- `index.php` - Archivo principal del plugin
- `includes/class-uraweb-admin.php` - Manejo del admin y menús
- `includes/class-uraweb-api.php` - Comunicación con la API
- `assets/uraweb-admin.css` - Estilos del plugin

## Requisitos

- WordPress 5.0 o superior
- PHP 7.4 o superior
- Acceso a la API de Uraweb
- API Key válida

## Soporte

Para soporte técnico, contacta a [solucionesuraweb.com](https://solucionesuraweb.com)

## Licencia

GPLv2 o posterior
