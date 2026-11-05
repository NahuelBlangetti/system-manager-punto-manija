<?php

namespace App\Services\Arca;

use App\Models\Comprobante;
use App\Models\Sale;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Emite la Factura C de una venta (Consumidor Final sin identificar) y la guarda en
 * `comprobantes`. Si la venta ya tiene factura autorizada, devuelve esa: es seguro llamarlo
 * de nuevo para reimprimir o tras un error.
 *
 * Lanza ArcaException si ARCA rechaza o no responde; el intento queda registrado y la venta
 * no se toca (cobrar nunca depende de ARCA).
 */
class FacturarVenta
{
    // FECAESolicitar: el número no es el siguiente al último autorizado (otro equipo facturó en el medio).
    private const ERROR_NUMERACION = '10016';

    public function __construct(private readonly ArcaCliente $arca) {}

    public static function disponible(): bool
    {
        return (bool) config('arca.habilitada') && self::faltantes() === [];
    }

    /**
     * Aviso para el POS cuando la facturación está prendida pero faltan datos fiscales.
     * Con ARCA_HABILITADA=false no dice nada: la opción simplemente no aparece.
     */
    public static function mensajeConfiguracion(): ?string
    {
        if (! (bool) config('arca.habilitada')) {
            return null;
        }

        $faltan = self::faltantes();

        if ($faltan === []) {
            return null;
        }

        return 'Facturación electrónica no configurada ('.implode(', ', $faltan).'). La venta se registra igual.';
    }

    /**
     * @return list<string>
     */
    private static function faltantes(): array
    {
        $faltan = [];
        $cuit = preg_replace('/\D/', '', (string) config('arca.cuit')) ?? '';

        if (strlen($cuit) !== 11) {
            $faltan[] = 'CUIT';
        }

        if ((int) config('arca.punto_venta') < 1) {
            $faltan[] = 'punto de venta';
        }

        foreach ([
            'certificado' => (string) config('arca.certificado'),
            'clave privada' => (string) config('arca.clave'),
        ] as $nombre => $relativo) {
            $archivo = storage_path('app/'.ltrim($relativo, '/'));

            if ($relativo === '' || ! is_file($archivo) || ! is_readable($archivo)) {
                $faltan[] = $nombre;
            }
        }

        return $faltan;
    }

    public function handle(Sale $sale, ?int $userId = null): Comprobante
    {
        if ($factura = $sale->factura()->first()) {
            return $factura;
        }

        $this->validar($sale);

        // La numeración es correlativa por punto de venta: un pedido a la vez.
        return Cache::lock('arca:numeracion:'.config('arca.punto_venta'), 120)
            ->block(60, fn () => $sale->factura()->first() ?? $this->emitir($sale, $userId));
    }

    private function validar(Sale $sale): void
    {
        if (! self::disponible()) {
            throw new ArcaException(self::mensajeConfiguracion()
                ?? 'La facturación con ARCA está desactivada (ARCA_HABILITADA).');
        }

        if ($sale->status !== 'completed' || $sale->trashed()) {
            throw new ArcaException('Solo se facturan ventas completadas.');
        }

        if ((float) $sale->total <= 0) {
            throw new ArcaException('No se puede facturar una venta en $ 0.');
        }

        if ((float) $sale->total >= (float) config('arca.limite_consumidor_final')) {
            throw new ArcaException('El importe supera el límite para facturar a Consumidor Final sin identificar. Hacela desde el portal de ARCA con los datos del comprador.');
        }
    }

    private function emitir(Sale $sale, ?int $userId): Comprobante
    {
        $puntoVenta = (int) config('arca.punto_venta');
        $ultimo = $this->arca->ultimoAutorizado($puntoVenta, Comprobante::FACTURA_C);

        if ($recuperada = $this->recuperarSinRespuesta($sale, $userId, $puntoVenta, $ultimo)) {
            return $recuperada;
        }

        $datos = [
            'sale_id' => $sale->id,
            'user_id' => $userId,
            'tipo' => Comprobante::FACTURA_C,
            'punto_venta' => $puntoVenta,
            'fecha' => now()->startOfDay(),
            'importe_total' => round((float) $sale->total, 2),
            'doc_tipo' => Comprobante::DOC_SIN_IDENTIFICAR,
            'doc_nro' => 0,
            'condicion_iva_receptor' => Comprobante::CONSUMIDOR_FINAL,
        ];

        for ($intento = 1; ; $intento++) {
            $numero = $ultimo + 1;

            try {
                $respuesta = $this->arca->solicitarCae([
                    'punto_venta' => $puntoVenta,
                    'tipo' => Comprobante::FACTURA_C,
                    'numero' => $numero,
                    'concepto' => (int) config('arca.concepto', 1),
                    'fecha' => $datos['fecha']->format('Ymd'),
                    'importe_total' => $datos['importe_total'],
                    'doc_tipo' => $datos['doc_tipo'],
                    'doc_nro' => $datos['doc_nro'],
                    'condicion_iva_receptor' => $datos['condicion_iva_receptor'],
                ]);
            } catch (ArcaException $e) {
                // Sin respuesta: ARCA pudo haberla autorizado igual. Se guarda el número intentado
                // para comprobarlo en el próximo intento antes de pedir otro número.
                Comprobante::create($datos + [
                    'estado' => Comprobante::ERROR,
                    'mensaje' => $e->getMessage(),
                    'respuesta' => ['numero_intentado' => $e->sinRespuesta ? $numero : null],
                ]);

                throw $e;
            }

            if ($respuesta['resultado'] === 'A' && $respuesta['cae']) {
                return Comprobante::create($datos + [
                    'numero' => $numero,
                    'cae' => $respuesta['cae'],
                    'cae_vencimiento' => Carbon::createFromFormat('Ymd', $respuesta['cae_vencimiento'])->startOfDay(),
                    'estado' => Comprobante::AUTORIZADO,
                    'mensaje' => implode(' | ', $respuesta['mensajes']) ?: null,
                    'respuesta' => ['xml' => $respuesta['xml']],
                ]);
            }

            $codigos = array_column($respuesta['errores'], 'code');

            if ($intento === 1 && in_array(self::ERROR_NUMERACION, $codigos, true)) {
                $ultimo = $this->arca->ultimoAutorizado($puntoVenta, Comprobante::FACTURA_C);

                continue;
            }

            $mensaje = collect($respuesta['errores'])->map(fn ($e) => "{$e['code']} - {$e['msg']}")
                ->merge($respuesta['mensajes'])
                ->implode(' | ') ?: 'ARCA rechazó la factura sin detallar el motivo.';

            Comprobante::create($datos + [
                'estado' => Comprobante::RECHAZADO,
                'mensaje' => $mensaje,
                'respuesta' => ['xml' => $respuesta['xml']],
            ]);

            throw new ArcaException('ARCA rechazó la factura: '.$mensaje);
        }
    }

    /**
     * Si un intento anterior de esta venta se quedó sin respuesta y ese número figura emitido
     * en ARCA con el mismo importe, la factura existe: se registra en vez de emitir otra.
     */
    private function recuperarSinRespuesta(Sale $sale, ?int $userId, int $puntoVenta, int $ultimo): ?Comprobante
    {
        $pendientes = $sale->comprobantes()
            ->where('estado', Comprobante::ERROR)
            ->latest('id')
            ->get()
            ->filter(fn (Comprobante $c) => ($c->respuesta['numero_intentado'] ?? null) !== null);

        foreach ($pendientes as $intento) {
            $numero = (int) $intento->respuesta['numero_intentado'];

            if ($numero > $ultimo || Comprobante::query()->where('punto_venta', $puntoVenta)->where('tipo', Comprobante::FACTURA_C)->where('numero', $numero)->exists()) {
                continue;
            }

            $emitido = $this->arca->consultar($puntoVenta, Comprobante::FACTURA_C, $numero);

            if (! $emitido || abs($emitido['importe_total'] - (float) $sale->total) > 0.009) {
                continue;
            }

            return Comprobante::create([
                'sale_id' => $sale->id,
                'user_id' => $userId,
                'tipo' => Comprobante::FACTURA_C,
                'punto_venta' => $puntoVenta,
                'numero' => $numero,
                'fecha' => Carbon::createFromFormat('Ymd', $emitido['fecha'])->startOfDay(),
                'importe_total' => $emitido['importe_total'],
                'doc_tipo' => Comprobante::DOC_SIN_IDENTIFICAR,
                'doc_nro' => 0,
                'condicion_iva_receptor' => Comprobante::CONSUMIDOR_FINAL,
                'cae' => $emitido['cae'],
                'cae_vencimiento' => Carbon::createFromFormat('Ymd', $emitido['cae_vencimiento'])->startOfDay(),
                'estado' => Comprobante::AUTORIZADO,
                'mensaje' => 'Recuperada: ARCA la había autorizado aunque no llegó la respuesta.',
            ]);
        }

        return null;
    }
}
