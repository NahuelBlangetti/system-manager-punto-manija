# AGENTS.md — Punto Manija (System Manager)

> Este archivo documenta el proyecto **y** funciona como plantilla reutilizable:
> la base Laravel + Filament se clona por cliente. Ver
> [Cómo reutilizar esta base para un nuevo cliente](#cómo-reutilizar-esta-base-para-un-nuevo-cliente).
>
> Hermanos: `system-manager` (Asistel) y `system-manager-eleven-bikes`.
> Cada copia divergió: **no asumas** que este mapa aplica al otro repo.

## Datos del cliente actual

- **Cliente**: Punto Manija
- **Rubro**: Kiosco/despensa multirubro — eje en **bebidas** (alcohólicas y
  sin alcohol), **combos**, **perfumes árabes** y productos varios.
- **Ubicación**: Avenida Fuerza Aérea 3423, Córdoba, Argentina (5010) — Ruta 20.
- **Contacto**: WhatsApp / Instagram pendientes en `.env`. Dirección y
  coordenadas ya están en `config/store.php`.
- **Tagline**: *Córdoba · Ruta 20 · Abierto cuando la noche lo pide*

---

## Qué es este proyecto

ERP + Marketplace para un kiosco nocturno. Combina:

- **Panel admin** (Filament 5.6): productos, ventas POS, caja, proveedores,
  inventario, promociones, zonas rojas, tarifas de envío, pedidos web.
  Roles Admin / Empleado / **Delivery**.
- **Tienda pública** (`/`): marketplace responsive, carrito localStorage,
  cotización de envío por distancia, checkout que persiste `WebOrder` y
  confirma por WhatsApp.

Stack: **Laravel 12.58 · Filament 5.6.2 · MySQL · Tailwind 4 · Alpine.js · Vite 7 · PHP 8.2+**
Extras: dompdf, phpspreadsheet. Impresión ESC/POS vía Print Agent local.

---

## Cómo reutilizar esta base para un nuevo cliente

Esta base se clona por cliente (`system-manager`, `system-manager-eleven-bikes`,
etc.). Cada copia diverge (features distintas), así que **verificá el código**
antes de copiar convenciones.

### Qué cambia por cliente (específico)
- Nombre/marca, tagline, copy del hero/about, paleta/logo.
- Rubro: categorías del seeder y `config/store.php` → `catalog.category_order`.
- Contacto: WhatsApp, dirección, horario, Instagram, mapa, lat/lng.
- `APP_NAME`, `DB_DATABASE`, emails semilla, `brandName(...)`.
- Claves de `localStorage` del carrito.

### Qué NO cambia (genérico del stack)
- Núcleo: `Product`, `Category`, `Sale`, `SaleItem`, `CashRegister`,
  `CashRegisterEntry`, `StockMovement`, `Supplier`, `User` + roles,
  `ComboItem`, `ProductImport`, `WebOrder`.
- Convenciones: español, ARS (`es-AR`), timezone Córdoba.
- Carpetas Filament (Resources / Widgets / Pages / Observers / Enums).

### Features que SÍ son de este cliente (no asumirlas en el clon)
- Promociones (`Promotion`, % y 2x1).
- Zonas rojas (`RedZone` + map picker de polígonos).
- Envíos por distancia (`ShippingPriceCalculator`, `ConfiguracionEnvios`).
- Rol `Delivery`.
- Descuento por cantidad en producto (`discount_min_qty` / `discount_type`).

### Checklist al clonar
Grep el nombre del cliente anterior y revisar:
- `config/store.php` — nombre, tagline, hero, about, categorías, shipping.
- `.env` / `.env.example` — `APP_NAME`, `DB_DATABASE`, `STORE_*`, `SHIPPING_*`.
- `app/Providers/Filament/AdminPanelProvider.php` — `->brandName(...)`.
- `resources/views/marketplace.blade.php` — title, logo, `$storeDisplayName`,
  claves `localStorage` (`punto_manija_cart`).
- `database/seeders/DatabaseSeeder.php` y `ProductSeeder.php`.
- `app/Console/Commands/ScrapeProductos.php` — scraper del cliente anterior;
  normalmente **no** se reutiliza.
- `README.md` / `MANUAL_USUARIO.md` si existen.

El código avanza más rápido que este archivo. Antes de dar por buena la
sección de estado, confirmá contra `app/Filament/Resources`, `app/Models` y
`app/Services`.

---

## Estado de desarrollo actual

| Módulo | Estado | Notas |
|---|---|---|
| Productos (CRUD) | ✅ Completo | Soft deletes, slug, imágenes, pricing, stock, proveedor, unidad, descuento por cantidad |
| Categorías (CRUD) | ✅ Completo | Admin-only, auto-slug |
| Proveedores (CRUD) | ✅ Completo | Admin-only, export PDF |
| Combos | ✅ Completo | `is_combo` + `ComboItem` + `ComboStockService` + `ComboSeeder` |
| Promociones | ✅ Completo | Por categoría, días/horario, tipos `%` y `2x1` (`PromotionResource`) |
| Zonas rojas | ✅ Completo | Polígonos geo, `containsPoint()`, map picker Filament |
| Envíos | ✅ Completo | Fórmula base + $/km, max distancia; override en `settings` vía `ConfiguracionEnvios` |
| Usuarios y roles | ✅ Completo | Admin / Empleado / **Delivery** + `can_manage_products` |
| Dashboard Widgets | ✅ Completo | StatsOverview, LatestSales, SalesTrend, TopProducts, StockAlerts, PendingWebOrders |
| Marketplace público | ✅ Completo | Home, detalle, categoría, SEO (`sitemap`/`robots`), cotización de envío |
| Pedidos web | ✅ Completo | Checkout crea `WebOrder`, valida stock, notifica staff; Delivery ve pedidos |
| Autenticación | ✅ Completo | Filament auth |
| Ventas POS | ✅ Completo | `CrearVenta` (flujo real). No existe `PointOfSale.php` |
| Descuento de stock | ✅ Completo | `ComboStockService` desde `CrearVenta` |
| CashRegister | ✅ Completo | `recalculate()`, diferencia al cerrar |
| Importación masiva | ✅ Completo | Excel/CSV/PDF/multi-hoja, `CargarProductos`/`ValidarImport`, job + `ExpireStaleProductImports` |
| Impresión ESC/POS | ✅ Completo | Tickets de venta y etiquetas (`PRINT_AGENT_TICKET_PRINTER`) |
| Notificaciones | ✅ Completo | Stock bajo/agotado, pedido web, diferencia de caja |
| Reportes / exportación | ⚠️ Parcial | PDF de catálogo. Sin Excel ni reportes de ventas/caja |
| Tests | ⚠️ Parcial | Import, caja, zonas rojas, SEO. Falta POS, promociones, envíos, combos |
| API REST | ❌ No iniciada | `routes/api.php` no existe |
| Pagos (MercadoPago) | ❌ No implementado | |

⚠️ **Bypass de stock**: `SaleResource::CreateSale` no usa `ComboStockService`.
El flujo correcto es `CrearVenta`.

---

## Modelos y relaciones clave

```
User
 ├── role: UserRole (Admin | Empleado | Delivery), can_manage_products
 └── hasMany → Sales, CashRegisters
Category
 └── hasMany → Product, Promotion
Supplier
 └── hasMany → Product
Product (softDeletes, ObservedBy ProductObserver)
 ├── belongsTo → Category, Supplier
 ├── hasMany → SaleItem, StockMovement, WebOrderItem, ComboItem
 └── is_combo, discount_min_qty, discount_type, discount_value
ComboItem
 └── combo ↔ component
Promotion
 ├── belongsTo → Category (nullable = todas)
 └── type: percentage | two_for_one; days_of_week + horario
RedZone
 └── polygon (array lat/lng), containsPoint()
Setting
 └── key/value — overrides de tarifas de envío
Sale (softDeletes)
 ├── belongsTo → User, CashRegister
 ├── hasMany → SaleItem
 └── morphMany → StockMovement
CashRegister / CashRegisterEntry
StockMovement
WebOrder (ObservedBy WebOrderObserver) / WebOrderItem
ProductImport
 └── status: pending|processing|done|error|validated|cancelled
```

### Campos notables
- `Sale.sale_number`: `V-000001`
- `Product.slug` / `Category.slug`: auto
- `ProductDiscountType`: `%` o monto fijo (descuento por cantidad)
- `PromotionType`: `percentage` | `two_for_one`
- `StockMovement.type`: `in|out|adjustment`
- `Sale.payment_method`: `cash|transfer|card`
- `CashRegister.status`: `open|closed`
- Envío default: base 800 + 300/km, max 15 km, redondeo 50 (override admin)

---

## Servicios y lógica destacada

- `app/Services/Stock/ComboStockService.php` — stock + movimientos, consciente de combos.
- `app/Services/Shipping/ShippingPriceCalculator.php` + `ShippingSettings.php`
- `app/Services/Tickets/SaleTicketEscPosBuilder.php` + `Labels/ProductLabelEscPosBuilder.php`
- `app/Filament/Pages/CrearVenta.php` — POS real.
- `app/Filament/Pages/ConfiguracionEnvios.php` — tarifas en tabla `settings`.
- `app/Filament/Support/PolygonMapPicker.php` — polígonos de zonas rojas.
- `app/Support/EscPosPrint.php`, `MarketplaceSeo.php`
- `app/Jobs/ProcessImportFile.php`
- `app/Console/Commands/ExpireStaleProductImports.php`
- `app/Console/Commands/ScrapeProductos.php` — scraper legacy; no reutilizar a ciegas.

---

## Estructura de archivos relevante

```
app/
  Enums/
    UserRole.php, PromotionType.php, ProductDiscountType.php
  Filament/
    Resources/
      Products/, Categories/, Suppliers/, Sales/, CashRegisters/
      Users/, WebOrders/, Promotions/, RedZones/
    Pages/
      CrearVenta.php, CargarProductos.php, ValidarImport.php
      ConfiguracionEnvios.php, Dashboard.php
    Widgets/   # 6, incluye SalesTrend y PendingWebOrders
    Support/   # PDF, mapa polígonos, import helpers
  Services/
    Stock/, Shipping/, Tickets/, Labels/
  Observers/ProductObserver.php, WebOrderObserver.php
  Jobs/ProcessImportFile.php
  Http/Controllers/
    MarketplaceController.php, MarketplaceOrderController.php
    MarketplaceDiscoveryController.php
  Support/EscPosPrint.php, MarketplaceSeo.php
database/
  migrations/   # 31
  seeders/
    DatabaseSeeder.php   # admin / empleado / delivery @puntomanija.com
    ProductSeeder.php, ComboSeeder.php
resources/views/
  marketplace.blade.php
  filament/forms/components/polygon-map-picker.blade.php
routes/web.php
config/store.php
```

Rutas públicas: `/`, `/producto/{slug}`, `/categoria/{category:slug}`,
`/sitemap.xml`, `/robots.txt`, `POST /marketplace/orders` (throttle 10/min).

---

## Configuración de entorno

```
APP_NAME=PuntoManija
APP_TIMEZONE=America/Argentina/Cordoba
DB_CONNECTION=mysql
DB_DATABASE=punto-manija
QUEUE_CONNECTION=database
CACHE_STORE=database

STORE_WHATSAPP=
STORE_ADDRESS="Avenida Fuerza Aérea 3423, Córdoba"
STORE_MAPS_URL=
STORE_LAT=
STORE_LNG=
STORE_HOURS_WEEKDAY="9:00 - 18:00"
STORE_HOURS_SATURDAY="9:00 - 13:00"
STORE_HOURS_SUNDAY=Cerrado
STORE_INSTAGRAM=

GOOGLE_MAPS_API_KEY=
SHIPPING_BASE_PRICE=800
SHIPPING_PRICE_PER_KM=300
SHIPPING_MAX_DISTANCE_KM=15
SHIPPING_ROUNDING_STEP=50

PRINT_AGENT_TICKET_PRINTER="POS-80C"
OPENAI_API_KEY=
DISCORD_WEBHOOK_URL=
```

Panel: `/admin`. Correr `php artisan queue:work` para la importación.

---

## Tests existentes

Feature: `CashRegisterFlowTest`, `RedZoneFlowTest`, `MarketplacePhaseOneSeoTest`,
`ExpireStaleProductImportsTest`, `ProcessImportFile*` (empty, PDF, multi-sheet,
SKU), `ValidarImport*` (upsert, updates-first, duplicate SKU).

Unit: `MarketplaceSeoTest`.

Faltan: POS/`CrearVenta`, promociones, cotización de envío, combos, rol Delivery.

---

## Próximas tareas prioritarias

1. **[IMPORTANTE]** Cerrar bypass de stock en `SaleResource::CreateSale`.
2. Tests de POS, promociones, envíos y combos.
3. Completar WhatsApp / Instagram / horarios reales.
4. Reportes de ventas/caja y export Excel/CSV.
5. API REST (Sanctum).
6. Integración de pagos (MercadoPago).
7. Decidir destino de `ScrapeProductos`.
8. Migrar RBAC a Spatie / Policies.

---

## Convenciones

- **Idioma**: español
- **Moneda**: ARS, `es-AR`
- **Zona horaria**: America/Argentina/Cordoba
- **Imágenes**: disco `public`, carpeta `products/`
- **Soft deletes**: `Product`, `Sale`
- **Admin path**: `/admin`
- **Marketplace**: `/`
- **localStorage**: `punto_manija_cart` / `punto_manija_customer`
- **Roles**: Admin total; Empleado acotado; Delivery ve pedidos web

---

## Comandos útiles

```bash
php artisan serve
npm run dev
npm run build
php artisan migrate:fresh --seed
php artisan queue:work
php artisan test
./vendor/bin/pint
```
