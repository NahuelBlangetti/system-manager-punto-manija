# Posnet Mercado Pago Point (N950)

El POS (`/admin/nueva-venta`) envía el total al posnet, el cliente paga
(tarjeta, contactless o QR) y la venta queda registrada sola con medio de pago
**Posnet MP**. Usa la Orders API de Mercado Pago en modo PDV.

## Puesta en marcha (una sola vez)

1. **Vincular el N950 a la cuenta** de Mercado Pago del local desde la app
   (Tu negocio → Point). Tiene que tener internet (WiFi o chip).
2. **Crear una aplicación** en <https://www.mercadopago.com.ar/developers/panel/app>
   (producto: *Pagos presenciales / Point*) con la misma cuenta.
3. Copiar el **Access Token de producción** al `.env`:
   ```
   MERCADOPAGO_ACCESS_TOKEN=APP_USR-...
   ```
   y correr `php artisan migrate && php artisan config:clear`.
4. En el panel: **Administración → Posnet Mercado Pago → Buscar posnets**.
   - **Activar modo PDV** en el N950 y reiniciarlo.
   - **Usar este** para dejarlo como posnet del POS.
5. (Opcional, recomendado si el sistema está publicado en internet) En la
   aplicación de MP → *Webhooks*: URL `https://<dominio>/webhooks/mercadopago`,
   evento **Order (Mercado Pago)**. Copiar la clave secreta a
   `MERCADOPAGO_WEBHOOK_SECRET`. Sin webhook igual funciona: el POS consulta el
   estado cada 2 segundos; el webhook cubre el caso de que se cierre el
   navegador mientras el cliente paga.

## Cómo se cobra

1. Cargar productos → método **Posnet MP** → **Cobrar en posnet**.
2. El POS queda en "Esperando pago en el posnet" y el N950 muestra el monto.
3. Aprobado → se registra la venta (stock, caja, ticket). Rechazado, vencido
   (5 min por defecto) o cancelado → no se registra nada y el carrito queda
   para reintentar u otro medio.
4. **Cancelar cobro** funciona mientras el posnet no lo tomó; si el cliente ya
   lo está viendo, se cancela desde el posnet.

## Caja

Las ventas Posnet MP se muestran aparte al cerrar la caja y **no** suman al
efectivo esperado (el dinero se acredita en la cuenta de Mercado Pago).
El detalle de cada cobro (tipo de tarjeta, últimos 4, orden MP) queda en las
notas de la venta y en la tabla `point_payments`, visible en la página
*Posnet Mercado Pago*.

## Variables

| Variable | Default | Uso |
|---|---|---|
| `MERCADOPAGO_ACCESS_TOKEN` | — | Obligatoria |
| `MERCADOPAGO_WEBHOOK_SECRET` | — | Valida la firma `x-signature` |
| `MERCADOPAGO_POINT_TERMINAL_ID` | — | Alternativa a elegirlo en el panel |
| `MERCADOPAGO_POINT_EXPIRATION` | `PT5M` | Vencimiento del cobro en el posnet |
| `MERCADOPAGO_POINT_PRINT` | `no_ticket` | `seller_ticket` / `full_ticket` para que imprima el N950 |

## Pendiente / límites

- Anular una venta Posnet MP en el sistema **no** devuelve el dinero: la
  devolución se hace desde Mercado Pago.
- Si se corta internet justo al enviar el cobro, el POS avisa; si el posnet
  llegó a mostrarlo, cancelarlo desde el posnet antes de reintentar.
