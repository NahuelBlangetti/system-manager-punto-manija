<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Comprobante electrónico pedido a ARCA para una venta. Se guardan también los intentos
 * rechazados o con error, para saber qué pasó; solo los "autorizado" tienen número y CAE.
 */
class Comprobante extends Model
{
    public const FACTURA_C = 11;

    public const DOC_SIN_IDENTIFICAR = 99;

    public const CONSUMIDOR_FINAL = 5;

    public const AUTORIZADO = 'autorizado';

    public const RECHAZADO = 'rechazado';

    public const ERROR = 'error';

    protected $fillable = [
        'sale_id',
        'user_id',
        'tipo',
        'punto_venta',
        'numero',
        'fecha',
        'importe_total',
        'doc_tipo',
        'doc_nro',
        'condicion_iva_receptor',
        'cae',
        'cae_vencimiento',
        'estado',
        'mensaje',
        'respuesta',
    ];

    protected $casts = [
        'fecha' => 'date',
        'cae_vencimiento' => 'date',
        'importe_total' => 'decimal:2',
        'respuesta' => 'array',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function autorizado(): bool
    {
        return $this->estado === self::AUTORIZADO;
    }

    /** Ej.: 00003-00000001 */
    public function numeroCompleto(): string
    {
        return sprintf('%05d-%08d', $this->punto_venta, (int) $this->numero);
    }

    /**
     * URL del QR obligatorio en el comprobante impreso (RG 4892). $relleno agrega espacios al
     * final del JSON (sigue siendo válido) para ajustar el largo de la URL.
     */
    public function urlQr(int $relleno = 0): string
    {
        $datos = [
            'ver' => 1,
            'fecha' => $this->fecha->format('Y-m-d'),
            'cuit' => (int) config('arca.cuit'),
            'ptoVta' => (int) $this->punto_venta,
            'tipoCmp' => (int) $this->tipo,
            'nroCmp' => (int) $this->numero,
            'importe' => round((float) $this->importe_total, 2),
            'moneda' => 'PES',
            'ctz' => 1,
            'tipoDocRec' => (int) $this->doc_tipo,
            'nroDocRec' => (int) $this->doc_nro,
            'tipoCodAut' => 'E',
            'codAut' => (int) $this->cae,
        ];

        return 'https://www.afip.gob.ar/fe/qr/?p='.base64_encode(json_encode($datos).str_repeat(' ', $relleno));
    }
}
