# Manual de uso — Punto Manija Admin

**Para:** Equipo de la tienda  
**Versión:** Junio 2026  
**Nivel:** Sin experiencia previa requerida

---

## ¿Qué es este sistema?

Punto Manija Admin es el sistema interno de la tienda. Desde acá podés:

- Registrar ventas en el momento con el lector de código de barras
- Ver y controlar el stock de productos
- Abrir y cerrar la caja registradora
- Revisar el historial de ventas
- Ver el catálogo público como lo ven los clientes

Todo funciona desde el navegador (Chrome, Edge, Firefox). No hay nada que instalar.

---

## Cómo entrar al sistema

1. Abrí el navegador
2. Escribí la dirección del sistema en la barra de arriba (tu administrador te la va a dar)
3. Agregá `/admin` al final. Ejemplo: `http://mitienda.com/admin`
4. Ingresá con tu usuario y contraseña

> **Si olvidás la contraseña**, avisale al administrador del sistema.

---

## La pantalla principal (Dashboard)

Cuando entrás, lo primero que ves es el **Panel de inicio**. Tiene cuatro cuadros informativos:

| Cuadro | Qué muestra |
|--------|-------------|
| **Ventas del mes** | Cuánto se vendió en total este mes |
| **Ventas de hoy** | Total del día |
| **Productos activos** | Cuántos productos están disponibles |
| **Stock bajo** | Productos que quedaron con poca cantidad |

Más abajo aparecen:
- Las **últimas ventas** registradas
- Los **productos más vendidos**
- Alertas de productos con **stock crítico** (menos de 5 unidades)

---

## El menú lateral

A la izquierda de la pantalla está el menú principal. Tiene estas secciones:

```
Dashboard          ← Pantalla de inicio
─────────────────
Operaciones
  Nueva Venta      ← Para registrar ventas en el mostrador
  Ventas           ← Historial de todas las ventas
  Caja             ← Cajas registradoras abiertas/cerradas
─────────────────
Catálogo
  Productos        ← Lista de todos los productos
  Categorías       ← Grupos de productos (auriculares, cargadores, etc.)
  Proveedores      ← Datos de tus proveedores
  Cargar Productos ← Importar catálogo desde PDF o Excel
─────────────────
Administración     ← Solo visible para Administradores
  Usuarios         ← Altas de usuarios, roles y permisos
─────────────────
Manual de uso      ← Este mismo manual, dentro del sistema
```

---

## PARTE 1 — Hacer una venta en el mostrador

Esta es la operación más frecuente. Seguí estos pasos en orden.

---

### Paso 0 — Abrir la caja (una sola vez al empezar el turno)

Antes de hacer cualquier venta, tenés que abrir la caja del día.

1. En el menú, hacé clic en **Caja**
2. Hacé clic en el botón **Nuevo** (arriba a la derecha)
3. Completá los campos:
   - **Monto de apertura**: el dinero en efectivo con el que empezás el turno. Si ya existió una caja anterior, el sistema **pre-completa este campo con el monto del último cierre** y te avisa con un cartel azul. Podés dejarlo así o cambiarlo.
   - **Fecha y hora de apertura**: se llena sola con la hora actual
4. Hacé clic en **Guardar**

> ⚠️ **Importante**: Solo puede haber una caja abierta a la vez. Si ya hay una abierta, el sistema no te va a dejar abrir otra.

---

### Paso 1 — Ir a "Nueva Venta"

En el menú lateral, hacé clic en **Nueva Venta** (dentro de "Operaciones").

Si no hay ninguna caja abierta, vas a ver un aviso en rojo arriba de la pantalla. En ese caso, primero andá a abrir la caja (ver Paso 0).

---

### Paso 2 — Agregar productos al carrito

Tenés dos formas de agregar productos:

**Opción A — Con el lector de código de barras (más rápido)**
1. Hacé clic en el campo que dice "Apuntá el scanner aquí y escaneá..."
2. Apuntá el lector al código de barras del producto
3. El producto se agrega solo al carrito

**Opción B — Buscando por nombre o código**
1. Escribí el nombre del producto en el campo "Buscar manualmente"
2. A medida que escribís, aparecen los productos que coinciden
3. Hacé clic en el producto que querés agregar

**En el carrito podés:**
- Aumentar la cantidad con el botón **+**
- Bajar la cantidad con el botón **−** (si llegás a 0, se elimina)
- Eliminar un producto con la **X** de la derecha

> ⚠️ Si un producto no tiene stock, el sistema no te va a dejar agregarlo y te avisa.

Cuando terminaste de agregar todo, hacé clic en **Continuar →**

---

### Paso 3 — Elegir el medio de pago

Aparecen tres opciones:

| Botón | Cuándo usarlo |
|-------|---------------|
| 💵 **Efectivo** | El cliente paga con billetes |
| 📱 **Transferencia** | El cliente transfiere por Mercado Pago u otro banco |
| 💳 **Tarjeta** | El cliente paga con tarjeta de débito o crédito |

Tocá el que corresponda (se pone oscuro cuando está seleccionado).

**Descuento (opcional):** Si le hacés un descuento al cliente, escribí el monto en el campo "Descuento". Se descuenta del total automáticamente.

**Notas (opcional):** Podés escribir algo como "cliente mayorista" o "reserva para retirar".

Hacé clic en **Continuar →**

---

### Paso 4 — Confirmar la venta

Aparece un resumen con todos los productos, el total y el medio de pago. Revisalo.

Si todo está bien, hacé clic en **✓ Confirmar venta**.

Cuando aparece la pantalla verde con "¡Venta registrada!" y el número de venta (ejemplo: `V-000004`), la venta quedó guardada. El sistema descuenta el stock automáticamente.

Para hacer otra venta, hacé clic en **Nueva venta**.

---

### Paso 5 — Cerrar la caja (al terminar el turno)

1. En el menú, hacé clic en **Caja**
2. Hacé clic en el lápiz ✏️ de la caja que está abierta
3. Completá:
   - **Estado**: cambialo a "Cerrada"
   - **Dinero contado**: contá los billetes en efectivo que tenés y escribí el total
   - **Fecha y hora de cierre**: se llena sola
4. Hacé clic en **Guardar**

El sistema calcula automáticamente:
- **Monto esperado**: lo que debería haber según las ventas en efectivo del turno
- **Diferencia**: si sobra o falta dinero respecto a lo esperado (verde = bien, rojo = falta, amarillo = sobra)

> ⚠️ Una vez cerrada, la caja **no se puede reabrir**. Si necesitás seguir trabajando, abrí una caja nueva.

---

## PARTE 2 — Gestión de Productos

### Ver todos los productos

En el menú, hacé clic en **Productos**. Aparece la lista con:
- Nombre del producto
- Categoría
- Precio de venta
- Stock disponible
- Si está activo o no

Podés buscar un producto escribiendo su nombre en el campo de búsqueda arriba.

---

### Agregar un producto nuevo

1. En la lista de productos, hacé clic en **Nuevo** (arriba a la derecha)
2. Completá los campos:

| Campo | Qué poner |
|-------|-----------|
| **Nombre** | El nombre del producto tal como lo vas a mostrar |
| **Categoría** | El grupo al que pertenece |
| **SKU** | Un código interno (opcional, podés inventarlo) |
| **Código de barras** | El código del producto para el lector |
| **Precio de costo** | Lo que te costó a vos |
| **Precio de venta** | Lo que le cobrás al cliente |
| **Stock** | Cuántas unidades tenés |
| **Stock mínimo** | A partir de qué cantidad el sistema te avisa que queda poco |
| **Activo** | Marcado = se muestra en el catálogo y se puede vender |

3. Hacé clic en **Guardar**

---

### Editar un producto

1. En la lista de productos, hacé clic en el lápiz ✏️ del producto que querés cambiar
2. Modificá lo que necesitás
3. Hacé clic en **Guardar**

---

### Desactivar un producto (en lugar de borrarlo)

Si un producto ya no se vende pero no querés perder el historial, **desactivalo** en lugar de eliminarlo:

1. Entrá a editar el producto
2. Desactivá el botón **Activo**
3. Guardá

El producto deja de aparecer en el catálogo y en el POS, pero las ventas históricas siguen intactas.

---

## PARTE 3 — Importar productos desde PDF o Excel del proveedor

Si tu proveedor te manda un catálogo o lista de precios en **PDF o Excel** (XLSX/XLS), podés importar los productos automáticamente sin tener que cargarlos uno por uno.

> Esta sección solo la ven los **Administradores** y los **Empleados con permiso de gestión de productos** (ver [Usuarios y roles](#parte-8--usuarios-y-roles-solo-administradores)).

### Cómo importar un archivo

1. En el menú, hacé clic en **Cargar Productos** (dentro de "Catálogo")
2. Hacé clic en "Elegir archivo" y seleccioná el PDF o Excel del proveedor (máximo 25 MB)
3. **Proveedor (opcional)**: si el nombre del archivo coincide con un proveedor ya cargado, el sistema lo detecta solo y te avisa. También podés elegirlo manualmente del desplegable, o crear uno nuevo sin salir de esta pantalla
4. Hacé clic en **Procesar con IA**
5. Esperá entre 10 y 30 segundos mientras el sistema lee el archivo
6. Aparece una tabla con todos los productos que encontró. Para cada uno podés:
   - **Marcar o desmarcar** la casilla para incluirlo o no
   - **Editar el nombre** si quedó mal escrito
   - **Asignar una categoría** del menú desplegable
   - **Confirmar o corregir el precio** de venta que detectó
   - **Agregar el precio de costo** (no siempre está en el archivo)
   - **Poner el stock inicial** que tenés en este momento
7. Hacé clic en **Crear productos seleccionados**

Los productos quedan guardados y ya podés verlos en la lista de Productos.

> ⚠️ **Archivo ya procesado**: si subís un archivo idéntico a uno que ya procesaste antes, el sistema te avisa con un cartel amarillo. Podés cancelar y elegir otro, o procesarlo de nuevo de todas formas.

> ⚠️ **Qué PDFs funcionan**: Solo funciona con PDFs que tengan texto seleccionable (podés copiar el texto con el mouse). Los PDFs que son fotos escaneadas sin texto no son compatibles.

> ⚠️ **La IA puede equivocarse**: Siempre revisá los precios y nombres antes de guardar. La IA hace su mejor esfuerzo pero puede cometer errores, especialmente con formatos de precio poco comunes.

---

## PARTE 4 — Gestión de Categorías

Las categorías agrupan los productos (ejemplo: Auriculares, Cargadores, Parlantes).

### Agregar una categoría

1. En el menú, hacé clic en **Categorías**
2. Hacé clic en **Nuevo**
3. Escribí el nombre (el slug/enlace se genera solo)
4. Hacé clic en **Guardar**

---

## PARTE 5 — Historial de Ventas

En el menú, hacé clic en **Ventas**. Aparece la lista de todas las ventas registradas.

Podés filtrar por:
- **Fecha**: ventas de hoy, esta semana, este mes
- **Estado**: Completadas o Canceladas
- **Medio de pago**: Efectivo, Transferencia, Tarjeta

---

### Cancelar una venta

Si una venta se hizo por error y querés cancelarla:

1. Hacé clic en el lápiz ✏️ de esa venta
2. Cambiá el **Estado** de "Completada" a "Cancelada"
3. Hacé clic en **Guardar**

El sistema **repone el stock automáticamente** de todos los productos de esa venta.

> ⚠️ Una vez que una venta está en estado "Completada", no podés cambiar los productos, el precio ni el medio de pago. Solo podés cancelarla o agregarle notas.

---

### Eliminar una venta

Solo hacelo si la venta fue un error grave. Al eliminar:
- El stock se repone automáticamente
- La venta desaparece del historial

Para eliminar, entrá a la venta y hacé clic en el botón rojo **Eliminar**.

---

## PARTE 6 — La tienda pública (lo que ven los clientes)

La tienda pública es el catálogo que ven tus clientes desde su celular o computadora. Para verla, entrá a la dirección principal del sistema (sin el `/admin`).

Los clientes pueden:
- Ver todos los productos activos con su precio
- Filtrar por categoría
- Buscar por nombre
- Agregar productos a un carrito
- Enviar el pedido por WhatsApp con un resumen automático

**El catálogo se actualiza solo**: cuando actualizás el precio o el stock de un producto desde el panel, los clientes lo ven de inmediato.

---

## PARTE 7 — Proveedores

En el menú, hacé clic en **Proveedores** (dentro de "Catálogo"). Acá administrás los datos de las empresas o personas que te venden mercadería.

### Agregar un proveedor

1. Hacé clic en **Nuevo**
2. Completá los campos:

| Campo | Qué poner |
|-------|-----------|
| **Nombre** | Razón social o nombre del proveedor |
| **CUIT** | Opcional |
| **Persona de contacto** | Opcional |
| **Teléfono** | Opcional |
| **Email** | Opcional |
| **Condición de pago** | Contado, 15/30/60 días o Consignación |
| **Activo** | Marcado = aparece como opción al importar productos |
| **Notas** | Cualquier observación útil (horarios de entrega, mínimos de compra, etc.) |

3. Hacé clic en **Guardar**

> 💡 Si cargás los proveedores antes de importar un catálogo, el sistema puede **detectar automáticamente** a qué proveedor pertenece un archivo según su nombre (ver [Importar productos](#parte-3--importar-productos-desde-pdf-o-excel-del-proveedor)).

---

## PARTE 8 — Usuarios y roles (solo Administradores)

Esta sección solo la ven los usuarios con rol **Administrador**. Permite dar de alta al resto del equipo y controlar qué puede hacer cada uno.

### Roles disponibles

| Rol | Qué puede hacer |
|-----|------------------|
| **Administrador** | Acceso total: ventas, caja, productos, proveedores, importación, usuarios |
| **Empleado** | Puede hacer ventas, ver productos y manejar la caja. **No** puede gestionar productos ni importar catálogos a menos que un administrador le dé ese permiso |

### Dar permiso a un empleado para gestionar productos

Por defecto, un Empleado no ve las secciones de Productos ni Cargar Productos. Para habilitárselo:

1. En el menú, hacé clic en **Usuarios** (dentro de "Administración")
2. Hacé clic en el lápiz ✏️ del empleado
3. Activá el toggle **Puede gestionar productos**
4. Hacé clic en **Guardar**

### Crear un usuario nuevo

1. En **Usuarios**, hacé clic en **Nuevo**
2. Completá nombre, email, rol y contraseña
3. Si el rol es "Empleado" y querés que pueda gestionar productos, activá el toggle correspondiente
4. Hacé clic en **Guardar**

> ⚠️ Solo otro Administrador puede crear usuarios o cambiar roles. Si necesitás dar de alta a alguien y no tenés ese rol, pedíselo al administrador del sistema.

---

## Preguntas frecuentes

**¿Qué pasa si escaneo un código y no encuentra el producto?**
El sistema te avisa "Código no encontrado". Esto puede pasar si el producto no tiene el código de barras cargado. Buscalo manualmente por nombre o pedile al administrador que cargue el código.

**¿Puedo hacer una venta sin caja abierta?**
No. El sistema bloquea las ventas en efectivo si no hay caja abierta. Para transferencias y tarjetas podés proceder sin caja, pero es mejor tenerla siempre abierta.

**¿El stock se actualiza solo cuando vendo?**
Sí. Cada vez que confirmás una venta desde el POS, el stock de cada producto se descuenta automáticamente.

**¿Puedo ver el detalle de una venta ya hecha?**
Sí. Entrá a **Ventas**, buscá la venta por fecha o número, y hacé clic en el lápiz ✏️.

**¿Qué significa la diferencia en el cierre de caja?**
- **Verde (▲ sobra dinero)**: tenés más efectivo del esperado
- **Rojo (▼ falta dinero)**: tenés menos efectivo del esperado
- **Verde sin flecha**: el arqueo cierra exacto

**¿Cómo sé qué productos están por agotarse?**
En el Dashboard, el cuadro de **Stock bajo** y la sección **Alertas de stock** te avisan qué productos tienen pocas unidades. También podés ver la columna "Stock" en la lista de productos.

**¿Qué diferencia hay entre Administrador y Empleado?**
El Administrador tiene acceso a todo, incluida la gestión de usuarios. El Empleado solo puede vender y manejar la caja, salvo que un administrador le habilite el permiso de gestión de productos. Ver [Usuarios y roles](#parte-8--usuarios-y-roles-solo-administradores).

**¿Por qué no veo la opción "Productos" o "Cargar Productos" en el menú?**
Tu usuario es Empleado y no tiene el permiso de gestión de productos activado. Pedíle a un administrador que lo habilite desde **Usuarios**.

---

## Glosario rápido

| Término | Qué significa |
|---------|--------------|
| **POS** | Point of Sale — la pantalla de "Nueva Venta" |
| **SKU** | Código interno del producto (lo ponés vos) |
| **Código de barras** | El código que lee el scanner |
| **Stock** | Cantidad de unidades disponibles |
| **Arqueo** | Control del dinero en caja al cierre del turno |
| **Soft delete** | El producto o venta se "oculta" pero no se borra definitivamente |
| **Dashboard** | Pantalla de inicio con el resumen del negocio |
| **Proveedor** | Empresa o persona a la que le comprás mercadería |
| **Rol** | Nivel de acceso de un usuario: Administrador o Empleado |

---


*¿Encontraste un error o algo no funciona como se describe? Avisale al administrador del sistema.*
