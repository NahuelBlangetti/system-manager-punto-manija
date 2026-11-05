<?php

namespace App\Services\Tickets;

use App\Models\Comprobante;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/**
 * Ticket de la Factura C autorizada por ARCA, para la misma impresora térmica
 * de 58 mm que el ticket de venta: emisor, número, detalle, CAE y el QR de la RG 4892.
 *
 * El QR va como imagen raster (GS v 0). El comando QR nativo (GS ( k) sale rayado
 * en el clon OCPP-58H, igual que los códigos de barras nativos de las etiquetas.
 * EscPosPrint envuelve el ticket en base64 porque el bitmap tiene bytes > 127.
 */
class FacturaTicketEscPosBuilder extends SaleTicketEscPosBuilder
{
    /** Puntos por línea en papel de 58 mm. */
    private const DOTS = 384;

    /** Puntos por módulo del QR: con la URL de ARCA (~v13, 69 módulos) queda en ~280 puntos. */
    private const QR_MAX_MODULE_DOTS = 4;

    public function buildFactura(Comprobante $factura): string
    {
        $sale = $factura->sale->loadMissing('items.product');
        $emisor = config('arca.emisor');

        $ticket = self::ESC.'@';
        $ticket .= self::ESC.'!'.chr(self::BOLD_TALL);
        $ticket .= $this->centered((string) config('store.catalog.site_name', 'Punto Manija'));
        $ticket .= self::ESC.'!'.chr(0);

        if (filled($emisor['razon_social'] ?? null)) {
            $ticket .= $this->centered((string) $emisor['razon_social']);
        }

        if (filled($emisor['domicilio'] ?? null)) {
            $ticket .= $this->centered((string) $emisor['domicilio']);
        }

        $ticket .= $this->centered('CUIT: '.$this->cuit((string) config('arca.cuit')));

        if (filled($emisor['ingresos_brutos'] ?? null)) {
            $ticket .= $this->centered('IIBB: '.$emisor['ingresos_brutos']);
        }

        if (filled($emisor['inicio_actividades'] ?? null)) {
            $ticket .= $this->centered('Inicio act.: '.$emisor['inicio_actividades']);
        }

        if (filled($emisor['condicion_iva'] ?? null)) {
            $ticket .= $this->centered((string) $emisor['condicion_iva']);
        }

        $ticket .= $this->separator();
        $ticket .= self::ESC.'!'.chr(self::BOLD_TALL);
        $ticket .= $this->centered('FACTURA C');
        $ticket .= self::ESC.'!'.chr(0);
        $ticket .= $this->centered('Cod. 011');
        $ticket .= 'Nro: '.$factura->numeroCompleto()."\n";
        $ticket .= 'Fecha: '.$factura->fecha->format('d/m/Y')."\n";
        $ticket .= $this->separator();
        $ticket .= "A: Consumidor Final\n";
        $ticket .= $this->separator();

        foreach ($sale->items as $item) {
            $ticket .= $this->itemLine($item);
        }

        $ticket .= $this->separator();

        if ((float) $sale->discount > 0 || (float) $sale->promo_discount > 0) {
            $ticket .= $this->totalLine('Subtotal', (float) $sale->subtotal);

            if ((float) $sale->discount > 0) {
                $ticket .= $this->totalLine('Descuento', (float) $sale->discount);
            }

            if ((float) $sale->promo_discount > 0) {
                $ticket .= $this->totalLine('Promocion', (float) $sale->promo_discount);
            }
        }

        $ticket .= self::ESC.'!'.chr(self::BOLD);
        $ticket .= $this->totalLine('TOTAL', (float) $factura->importe_total);
        $ticket .= self::ESC.'!'.chr(0);
        $ticket .= $this->sanitize('Medio de pago: '.$this->paymentLabel((string) $sale->payment_method))."\n";

        if (str_contains((string) $sale->notes, 'Posnet MP')) {
            $ticket .= "Cobro: posnet Mercado Pago\n";
        }

        $ticket .= $this->separator();
        $ticket .= 'CAE: '.$factura->cae."\n";
        $ticket .= 'Vto. CAE: '.$factura->cae_vencimiento->format('d/m/Y')."\n\n";
        $ticket .= $this->qrRaster($factura->urlQr());
        $ticket .= $this->centered('Comprobante autorizado por ARCA');

        if (config('arca.entorno') !== 'produccion') {
            $ticket .= $this->centered('HOMOLOGACION - SIN VALIDEZ FISCAL');
        }

        $ticket .= "\n";
        $ticket .= $this->centered('Gracias por su compra!');
        $ticket .= "\n\n\n";
        $ticket .= self::GS.'V'.chr(0);

        return $ticket;
    }

    private function cuit(string $cuit): string
    {
        $cuit = preg_replace('/\D/', '', $cuit) ?? '';

        return strlen($cuit) === 11
            ? substr($cuit, 0, 2).'-'.substr($cuit, 2, 8).'-'.substr($cuit, 10)
            : $cuit;
    }

    /** QR como imagen raster centrada (GS v 0: 1 bit por punto, filas de bytes). */
    private function qrRaster(string $data): string
    {
        $qr = new QRCode(new QROptions(['eccLevel' => EccLevel::M, 'addQuietzone' => false]));
        $matrix = $qr->addByteSegment($data)->getQRMatrix();
        $modules = $matrix->size();
        $moduleDots = max(1, min(self::QR_MAX_MODULE_DOTS, intdiv(self::DOTS, $modules)));
        $size = $modules * $moduleDots;
        $offset = intdiv(self::DOTS - $size, 2);
        $bytesPerRow = intdiv(self::DOTS, 8);

        $rows = '';
        for ($y = 0; $y < $size; $y++) {
            $bits = array_fill(0, self::DOTS, 0);

            for ($x = 0; $x < $size; $x++) {
                if ($matrix->check(intdiv($x, $moduleDots), intdiv($y, $moduleDots))) {
                    $bits[$offset + $x] = 1;
                }
            }

            for ($b = 0; $b < $bytesPerRow; $b++) {
                $byte = 0;
                for ($i = 0; $i < 8; $i++) {
                    $byte = ($byte << 1) | $bits[$b * 8 + $i];
                }
                $rows .= chr($byte);
            }
        }

        return self::GS.'v0'.chr(0)
            .chr($bytesPerRow % 256).chr(intdiv($bytesPerRow, 256))
            .chr($size % 256).chr(intdiv($size, 256))
            .$rows."\n";
    }
}
