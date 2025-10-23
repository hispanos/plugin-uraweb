# Plugin Uraweb para WordPress

## Descripción

Este plugin permite consultar facturas y productos desde una API externa y mostrarlas en el dashboard de WordPress. Está diseñado específicamente para integrar con el sistema de facturación de Uraweb.

## Características

- **Menú del Dashboard**: Nuevo menú "Facturas" en el admin de WordPress
- **Configuración**: Página de configuración para URL del API y API Key
- **Consulta de Facturas**: Interfaz para buscar facturas por rango de fechas
- **Consulta de Productos**: Interfaz para buscar productos con filtros avanzados
- **Estadísticas**: Muestra estadísticas de facturas y productos encontrados
- **Detalles Completos**: Modales con información detallada de facturas y productos
- **Inventario**: Visualización del stock por ubicación para productos
- **Diseño Responsivo**: Interfaz adaptada para diferentes tamaños de pantalla

## Instalación

1. Sube la carpeta `uraweb` al directorio `/wp-content/plugins/`
2. Activa el plugin desde el panel de administración de WordPress
3. Ve a "Facturas" > "Configuración" para configurar la URL del API y API Key

## Configuración

### URL del API
Ejemplo: `https://ventas.evircol.com/index.php/api/v1`

**Nota**: El plugin automáticamente concatena `/sales` para facturas y `/items` para productos.

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

3. **Consultar Productos**:
   - Ve a "Facturas" > "Productos"
   - Usa los filtros de búsqueda (nombre, categoría, fabricante)
   - Haz clic en "Buscar Productos"

4. **Ver Detalles**:
   - Haz clic en "Ver Detalles" en cualquier factura o producto
   - Se abrirá un modal con información completa

## Estructura de Datos

El plugin maneja las siguientes estructuras de datos:

### Facturas

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

### Productos

```typescript
interface Item {
    item_id: number;
    name: string;
    item_number: string | null;
    product_id: string | null;
    size: string;
    category: string;
    category_id: number;
    manufacturer: string;
    manufacturer_id: number | null;
    cost_price: string;
    unit_price: string;
    description: string;
    long_description: string;
    is_service: boolean;
    is_serialized: boolean;
    is_ebt_item: boolean;
    is_ecommerce: boolean;
    tax_included: boolean;
    tags: string[];
    locations: {
        [locationId: string]: LocationDetails;
    };
}

interface LocationDetails {
    quantity: number;
    location: string;
    unit_price: string;
    cost_price: string;
    promo_price: string;
    start_date: string | null;
    end_date: string | null;
    reorder_level: string;
    replenish_level: string;
    override_default_tax: boolean;
    tax_class_id: number | null;
}
```

## Archivos del Plugin

- `index.php` - Archivo principal del plugin
- `includes/class-uraweb-admin.php` - Manejo del admin y menús
- `includes/class-uraweb-api.php` - Clase base para comunicación con la API
- `includes/class-uraweb-sales.php` - Manejo específico de ventas/facturas
- `includes/class-uraweb-products.php` - Manejo específico de productos
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
