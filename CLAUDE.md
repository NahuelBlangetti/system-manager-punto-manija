# CLAUDE.md — Punto Manija (System Manager)

> Este archivo documenta el proyecto **y** funciona como plantilla reutilizable: la base de
> código (Laravel + Filament) se reusa entre clientes distintos, solo cambian el nombre, el
> rubro y los datos de contacto. Ver [Cómo reutilizar esta base para un nuevo cliente](#cómo-reutilizar-esta-base-para-un-nuevo-cliente)
> antes de clonar este proyecto para el próximo.

## Datos del cliente actual

- **Cliente**: Punto Manija
- **Rubro**: Kiosco/despensa multirubro — eje principal en **bebidas** (alcohólicas y sin
  alcohol), más **perfumería** y **productos varios**.
- **Ubicación**: Avenida Fuerza Aérea 3423, Córdoba, Argentina (5010) — Ruta 20.
- **Contacto** (WhatsApp / horario / Instagram): PENDIENTE — completar con datos reales.
  Dirección ya cargada en `.env` / `config/store.php`.

---

## Qué es este proyecto

**Punto Manija** es un ERP + Marketplace para un comercio multirubro (bebidas, perfumería y
productos varios) en Argentina. Combina:

- **Panel admin** (Filament 5.6): gestión interna de productos, ventas, caja, proveedores e
  inventario, con roles Admin/Empleado.
- **Tienda pública** (`/`): marketplace responsive con carrito localStorage y pedidos por
  WhatsApp.

Stack: **Laravel 12 · Filament 5.6 · MySQL · Tailwind 4 · Alpine.js · Vite 7 · PHP 8.2+**

---

## Cómo reutilizar esta base para un nuevo cliente

Esta base de código se clona para cada cliente nuevo (ej.: `system-manager`,
`system-manager-ferreteria`, `system-manager-punto-manija`). Cada copia diverge con el tiempo
(features distintas, integraciones distintas), así que **no asumas que la estructura descripta
acá es idéntica en otra copia** — verificá el código antes de copiar convenciones a ciegas.

### Qué cambia por cliente (específico)
- Nombre/marca del cliente y tagline.
- Rubro/dominio de productos: categorías de ejemplo, copy del hero/about del marketplace,
  descripciones de producto en el seeder.
- Datos de contacto: WhatsApp, dirección, horario, Instagram, mapa.
- Nombre de base de datos, `APP_NAME`, emails semilla de usuarios admin/empleado.
- Branding visual del marketplace (logo/texto, paleta si aplica).

### Qué NO cambia (genérico, parte del stack)
- Modelos y relaciones (`Product`, `Category`, `Sale`, `SaleItem`, `CashRegister`,
  `CashRegisterEntry`, `StockMovement`, `Supplier`, `User` + roles).
- Convenciones: idioma español, moneda ARS (`es-AR`), soft deletes solo en `Product`.
- Estructura de carpetas Filament (Resources/Widgets/Pages/Observers/Enums).
- Comandos de desarrollo (`artisan serve`, `npm run dev`, `migrate:fresh --seed`, etc.).

### Checklist de archivos con datos del cliente anterior (buscar y reemplazar)
Al clonar para un cliente nuevo, grep el nombre del cliente anterior (case-insensitive) y
revisar al menos:
- `config/store.php` — nombre, tagline, hero, about, beneficios.
- `.env` y `.env.example` — `APP_NAME`, `DB_DATABASE`, `STORE_*`.
- `app/Providers/Filament/AdminPanelProvider.php` — `->brandName(...)`.
- `resources/views/marketplace.blade.php` — `<title>`, clases `*-logo*`, variable
  `$storeDisplayName`, footer, claves de `localStorage` (ej. `asistel_cart`).
- `database/seeders/DatabaseSeeder.php` — emails semilla (`admin@cliente.com`, etc.).
- `database/seeders/ProductSeeder.php` — categorías y productos demo (reemplazar por el rubro
  nuevo, ej. bebidas/perfumería en vez de accesorios de celular).
- `app/Console/Commands/ScrapeProductos.php` — si existe, suele ser un scraper atado al sitio
  del cliente anterior. Normalmente **no** se reutiliza: evaluar si se elimina o se reescribe
  para el nuevo cliente/proveedor.
- `README.md` / `MANUAL_USUARIO.md` — si tienen branding o datos del cliente.

### Antes de partir de este archivo en un proyecto nuevo
El código avanza más rápido que la documentación. Si esta versión de `CLAUDE.md` tiene
secciones que la copia anterior no tenía (roles y permisos, proveedores, importación de
productos, etc.), son mejoras reales del stack — incorporalas al nuevo proyecto en vez de
partir de una versión vieja de este archivo. Antes de dar por buena la sección "Estado de
desarrollo actual" de abajo, confirmá contra el código (`app/Filament/Resources`,
`app/Observers`, `app/Models`), porque puede haber seguido evolucionando.

---

## Estado de desarrollo actual

| Módulo | Estado | Notas |
|---|---|---|
| Productos (CRUD) | ✅ Completo | Soft deletes, imágenes, pricing, stock, proveedor, unidad |
| Categorías (CRUD) | ✅ Completo | Admin-only |
| Proveedores (CRUD) | ✅ Completo | Admin-only, export PDF por proveedor |
| Usuarios y roles | ✅ Completo | Admin/Empleado + permiso granular `can_manage_products` |
| Dashboard Widgets | ✅ Completo | StatsOverview, LatestSales, TopProducts, StockAlerts |
| Marketplace público | ✅ Completo | Dark mode, carrito Alpine.js, WhatsApp checkout — rebranding visual aplicado |
| Autenticación | ✅ Completo | Filament auth |
| Ventas (header + items) | ✅ Completo | Repeater de `SaleItems` en `SaleForm`, página `PointOfSale` para empleados |
| Descuento de stock + StockMovement | ✅ Completo | Se descuenta en `PointOfSale`, se revierte al cancelar/eliminar venta |
| CashRegister (expected/difference) | ✅ Completo | `CashRegister::recalculate()`, notifica si hay diferencia al cerrar caja |
| Importación de productos | ✅ Completo | `ProductImport` + Job async, dedupe por hash de archivo |
| Notificaciones | ✅ Completo | Stock bajo/agotado, diferencia de caja |
| Reportes / exportación | ⚠️ Parcial | Solo PDF (productos, proveedores). Sin Excel/CSV |
| API REST | ❌ No iniciada | `routes/api.php` no existe |
| Tests | ❌ Sin coverage real | Solo stubs default de Laravel en `tests/` |
| Pagos (MercadoPago) | ❌ No implementado | |

**El flujo de venta completo (items, descuento de stock, caja) ya está implementado** — la
deuda técnica principal hoy es rebranding de cliente, tests y reportes.

---

## Modelos y relaciones clave

```
User
 ├── role: UserRole enum (Admin | Empleado), can_manage_products
 └── hasManyThrough → Sales, CashRegisters
Category
 └── hasMany → Product
Supplier
 └── hasMany → Product
Product (softDeletes)
 ├── belongsTo → Category, Supplier
 ├── hasMany → SaleItem
 └── hasMany → StockMovement
Sale (softDeletes)
 ├── belongsTo → User, CashRegister (nullable)
 ├── hasMany → SaleItem
 └── morphMany → StockMovement (reference)
SaleItem
 ├── belongsTo → Sale, Product
 └── snapshot: product_name, unit_price al momento de la venta
CashRegister
 ├── belongsTo → User
 ├── hasMany → Sale, CashRegisterEntry
 └── recalculate(): expected_amount, difference
CashRegisterEntry
 └── belongsTo → CashRegister  (movimientos manuales de caja: ingreso/egreso)
StockMovement
 ├── belongsTo → Product, User
 └── morphTo → reference (Sale u otro)
ProductImport
 └── status: pending | processing | done | error | validated | cancelled
```

### Campos notables
- `Sale.sale_number`: auto-generado formato `V-000001`
- `Product.image_url`: accessor que retorna URL pública
- `Category.slug`: auto-generado desde `name` en boot
- `StockMovement.type`: enum `in|out|adjustment`
- `Sale.payment_method`: enum `cash|transfer|card`
- `CashRegister.status`: enum `open|closed`
- `User.role`: `App\Enums\UserRole` (`Admin`, `Empleado`)

---

## Estructura de archivos relevante

```
app/
  Enums/
    UserRole.php
  Filament/
    Resources/
      Products/, Categories/, Sales/, CashRegisters/, Suppliers/, Users/
    Pages/
      PointOfSale.php        # POS para empleados: venta + descuento de stock
      CargarProductos.php    # carga/importación de productos
    Widgets/
      StatsOverview.php, LatestSales.php, TopProducts.php, StockAlerts.php
    Support/
      ProductImagePath.php, ProductPdfExport.php
  Observers/
    ProductObserver.php      # notificaciones de stock bajo/agotado
  Jobs/
    ProcessImportFile.php
  Models/
    Product.php, Category.php, Supplier.php, Sale.php, SaleItem.php
    CashRegister.php, CashRegisterEntry.php, StockMovement.php
    ProductImport.php, User.php
  Http/Controllers/
    MarketplaceController.php  # index() → vista pública
  Console/Commands/
    ScrapeProductos.php       # scraper específico del cliente anterior — revisar antes de reusar
database/
  migrations/
  seeders/
    DatabaseSeeder.php        # usuarios admin/empleado de ejemplo
    ProductSeeder.php         # categorías + productos demo — reemplazar por rubro del cliente
resources/views/
  marketplace.blade.php       # Tienda pública, Alpine.js — tiene branding hardcodeado
routes/
  web.php                    # Solo: GET / → MarketplaceController@index
config/
  store.php                  # name, whatsapp, address, instagram, schedule, copy del marketplace
```

---

## Configuración de entorno

```
APP_NAME=PuntoManija
APP_ENV=local / production
DB_CONNECTION=mysql
DB_DATABASE=punto-manija
SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
STORE_WHATSAPP=<pendiente>
STORE_ADDRESS=<pendiente>
STORE_MAPS_URL=<pendiente>
STORE_HOURS_WEEKDAY=<pendiente>
STORE_HOURS_SATURDAY=<pendiente>
STORE_HOURS_SUNDAY=<pendiente>
STORE_INSTAGRAM=<pendiente>
```

Panel admin accesible en: `/admin`

---

## Próximas tareas prioritarias (backlog técnico)

1. **[IMPORTANTE]** Tests PHPUnit para flujo de ventas, descuento de stock y cierre de caja
   (hoy sin coverage real).
3. Exportación de reportes en Excel/CSV (hoy solo PDF de productos/proveedores).
4. API REST para consumir desde app móvil o integraciones.
5. Integración de pagos (MercadoPago).
6. Decidir destino de `ScrapeProductos` (eliminar o adaptar a un proveedor de Punto Manija).

---

## Convenciones del proyecto

- **Idioma**: Todo en español (UI, validaciones, labels, seeders)
- **Moneda**: ARS, formato `es-AR`
- **Zona horaria**: UTC (sin configurar a Argentina — pendiente)
- **Imágenes**: disco `public`, carpeta `products/`
- **Soft deletes**: Solo en `Product` y `Sale`
- **Admin path**: `/admin` (Filament default)
- **Marketplace**: ruta raíz `/`
- **Roles**: `Admin` (acceso total) vs `Empleado` (acotado, con permiso opcional
  `can_manage_products`)

---

## Comandos útiles

```bash
# Levantar servidor local
php artisan serve

# Compilar assets
npm run dev
npm run build

# Seed de base de datos
php artisan migrate:fresh --seed

# Tinker
php artisan tinker

# Formateo de código
./vendor/bin/pint
```
