<?php

namespace App\Console\Commands;

use App\Models\Comprobante;
use App\Services\Arca\ArcaCliente;
use App\Services\Arca\ArcaException;
use Illuminate\Console\Command;

/**
 * Chequeo de solo lectura contra ARCA: servidores, acceso con el certificado, puntos de venta
 * y última Factura C. No emite comprobantes ni pide CAE.
 */
class ArcaEstado extends Command
{
    protected $signature = 'arca:estado';

    protected $description = 'Verifica la conexión con ARCA (solo lectura, no factura)';

    public function handle(ArcaCliente $arca): int
    {
        $puntoVenta = (int) config('arca.punto_venta');
        $this->line('Entorno: '.config('arca.entorno').' · CUIT '.config('arca.cuit').' · punto de venta '.$puntoVenta);

        try {
            $servidores = $arca->dummy();
            $this->line("Servidores: app {$servidores['app']}, base {$servidores['db']}, acceso {$servidores['auth']}");

            $arca->credenciales();
            $this->info('Acceso con el certificado: ok');

            $this->table(
                ['Punto de venta', 'Tipo de emisión', 'Bloqueado', 'Baja'],
                collect($arca->puntosDeVenta())->map(fn ($p) => [$p['numero'], $p['tipo'], $p['bloqueado'], $p['baja'] ?? '-'])->all(),
            );

            $ultimo = $arca->ultimoAutorizado($puntoVenta, Comprobante::FACTURA_C);
            $this->info("Última Factura C del punto de venta {$puntoVenta}: {$ultimo} (la próxima será la ".($ultimo + 1).')');
        } catch (ArcaException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
