<?php

namespace App\Services\Arca;

use DOMDocument;
use DOMXPath;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Cliente de los web services de ARCA: WSAA (acceso) y WSFEv1 (factura electrónica).
 * SOAP armado a mano sobre HTTP, sin la extensión soap de PHP.
 *
 * El ticket de acceso dura ~12 h y ARCA no entrega otro mientras esté vigente, así que se
 * guarda en cache. Ojo: si otra instalación (ej. local y producción) usa el mismo certificado,
 * la segunda no puede autenticarse hasta que venza el ticket de la primera.
 */
class ArcaCliente
{
    private const NS_WSFE = 'http://ar.gov.afip.dif.FEV1/';

    // --- WSFE ----------------------------------------------------------------------------

    public function ultimoAutorizado(int $puntoVenta, int $tipo): int
    {
        $xpath = $this->wsfe('FECompUltimoAutorizado', "<ar:PtoVta>{$puntoVenta}</ar:PtoVta><ar:CbteTipo>{$tipo}</ar:CbteTipo>");
        $this->lanzarErrores($xpath);

        return (int) $this->valor($xpath, 'CbteNro');
    }

    /**
     * Datos de un comprobante ya emitido, o null si no existe.
     *
     * @return array{numero: int, fecha: string, importe_total: float, cae: string, cae_vencimiento: string}|null
     */
    public function consultar(int $puntoVenta, int $tipo, int $numero): ?array
    {
        $xpath = $this->wsfe('FECompConsultar', "<ar:FeCompConsReq><ar:CbteTipo>{$tipo}</ar:CbteTipo><ar:CbteNro>{$numero}</ar:CbteNro><ar:PtoVta>{$puntoVenta}</ar:PtoVta></ar:FeCompConsReq>");

        if ($this->valor($xpath, 'CodAutorizacion') === null) {
            // 602 = no existe ese comprobante. Cualquier otro error (acceso, parámetros) se informa.
            if (collect($this->errores($xpath))->contains(fn ($e) => $e['code'] !== '602')) {
                $this->lanzarErrores($xpath);
            }

            return null;
        }

        return [
            'numero' => (int) $this->valor($xpath, 'CbteDesde'),
            'fecha' => (string) $this->valor($xpath, 'CbteFch'),
            'importe_total' => (float) $this->valor($xpath, 'ImpTotal'),
            'cae' => (string) $this->valor($xpath, 'CodAutorizacion'),
            'cae_vencimiento' => (string) $this->valor($xpath, 'FchVto'),
        ];
    }

    /**
     * Pide el CAE de un comprobante sin IVA discriminado (Factura C).
     *
     * @param  array{punto_venta: int, tipo: int, numero: int, concepto: int, fecha: string, importe_total: float, doc_tipo: int, doc_nro: int, condicion_iva_receptor: int}  $c
     * @return array{resultado: string, cae: ?string, cae_vencimiento: ?string, mensajes: list<string>, errores: list<array{code: string, msg: string}>, xml: string}
     */
    public function solicitarCae(array $c): array
    {
        $importe = number_format($c['importe_total'], 2, '.', '');
        $fechasServicio = $c['concepto'] === 1 ? '' :
            "<ar:FchServDesde>{$c['fecha']}</ar:FchServDesde><ar:FchServHasta>{$c['fecha']}</ar:FchServHasta><ar:FchVtoPago>{$c['fecha']}</ar:FchVtoPago>";

        $cuerpo = <<<XML
<ar:FeCAEReq>
  <ar:FeCabReq><ar:CantReg>1</ar:CantReg><ar:PtoVta>{$c['punto_venta']}</ar:PtoVta><ar:CbteTipo>{$c['tipo']}</ar:CbteTipo></ar:FeCabReq>
  <ar:FeDetReq>
    <ar:FECAEDetRequest>
      <ar:Concepto>{$c['concepto']}</ar:Concepto>
      <ar:DocTipo>{$c['doc_tipo']}</ar:DocTipo>
      <ar:DocNro>{$c['doc_nro']}</ar:DocNro>
      <ar:CbteDesde>{$c['numero']}</ar:CbteDesde>
      <ar:CbteHasta>{$c['numero']}</ar:CbteHasta>
      <ar:CbteFch>{$c['fecha']}</ar:CbteFch>
      <ar:ImpTotal>{$importe}</ar:ImpTotal>
      <ar:ImpTotConc>0</ar:ImpTotConc>
      <ar:ImpNeto>{$importe}</ar:ImpNeto>
      <ar:ImpOpEx>0</ar:ImpOpEx>
      <ar:ImpTrib>0</ar:ImpTrib>
      <ar:ImpIVA>0</ar:ImpIVA>
      {$fechasServicio}
      <ar:MonId>PES</ar:MonId>
      <ar:MonCotiz>1</ar:MonCotiz>
      <ar:CondicionIVAReceptorId>{$c['condicion_iva_receptor']}</ar:CondicionIVAReceptorId>
    </ar:FECAEDetRequest>
  </ar:FeDetReq>
</ar:FeCAEReq>
XML;

        $xpath = $this->wsfe('FECAESolicitar', $cuerpo);

        $mensajes = [];
        foreach ($xpath->query('//*[local-name()="Obs"]') as $obs) {
            $mensajes[] = trim($this->valor($xpath, 'Code', $obs).' - '.$this->valor($xpath, 'Msg', $obs));
        }

        return [
            'resultado' => $this->valor($xpath, 'Resultado', $xpath->query('//*[local-name()="FECAEDetResponse"]')->item(0)) ?? 'R',
            'cae' => $this->valor($xpath, 'CAE') ?: null,
            'cae_vencimiento' => $this->valor($xpath, 'CAEFchVto') ?: null,
            'mensajes' => $mensajes,
            'errores' => $this->errores($xpath),
            'xml' => $xpath->document->saveXML(),
        ];
    }

    /**
     * @return list<array{numero: int, tipo: string, bloqueado: string, baja: ?string}>
     */
    public function puntosDeVenta(): array
    {
        $xpath = $this->wsfe('FEParamGetPtosVenta', '');
        $this->lanzarErrores($xpath);

        $puntos = [];
        foreach ($xpath->query('//*[local-name()="PtoVenta"]') as $nodo) {
            $puntos[] = [
                'numero' => (int) $this->valor($xpath, 'Nro', $nodo),
                'tipo' => (string) $this->valor($xpath, 'EmisionTipo', $nodo),
                'bloqueado' => (string) $this->valor($xpath, 'Bloqueado', $nodo),
                'baja' => ($baja = $this->valor($xpath, 'FchBaja', $nodo)) && $baja !== 'NULL' ? $baja : null,
            ];
        }

        return $puntos;
    }

    /** Estado de los servidores de ARCA (no requiere autenticación). */
    public function dummy(): array
    {
        $xpath = $this->soap($this->url('wsfe'), self::NS_WSFE.'FEDummy', $this->sobre('<ar:FEDummy/>'));

        return [
            'app' => $this->valor($xpath, 'AppServer'),
            'db' => $this->valor($xpath, 'DbServer'),
            'auth' => $this->valor($xpath, 'AuthServer'),
        ];
    }

    private function wsfe(string $metodo, string $cuerpo): DOMXPath
    {
        ['token' => $token, 'sign' => $sign] = $this->credenciales();
        $cuit = config('arca.cuit');

        $sobre = $this->sobre(
            "<ar:{$metodo}><ar:Auth><ar:Token>{$token}</ar:Token><ar:Sign>{$sign}</ar:Sign><ar:Cuit>{$cuit}</ar:Cuit></ar:Auth>{$cuerpo}</ar:{$metodo}>"
        );

        return $this->soap($this->url('wsfe'), self::NS_WSFE.$metodo, $sobre);
    }

    private function sobre(string $cuerpo): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ar="'.self::NS_WSFE.'">'
            .'<soapenv:Header/><soapenv:Body>'.$cuerpo.'</soapenv:Body></soapenv:Envelope>';
    }

    // --- WSAA ----------------------------------------------------------------------------

    /**
     * @return array{token: string, sign: string}
     */
    public function credenciales(): array
    {
        $clave = 'arca:ta:'.config('arca.entorno').':'.config('arca.cuit');

        if ($ta = Cache::get($clave)) {
            return $ta;
        }

        return Cache::lock($clave.':lock', 60)->block(30, function () use ($clave) {
            if ($ta = Cache::get($clave)) {
                return $ta;
            }

            [$ta, $vence] = $this->login();
            // Margen de 10 minutos antes del vencimiento real.
            Cache::put($clave, $ta, $vence->subMinutes(10));

            return $ta;
        });
    }

    /**
     * @return array{0: array{token: string, sign: string}, 1: Carbon}
     */
    private function login(): array
    {
        $ahora = now();
        $tra = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<loginTicketRequest version="1.0"><header>'
            .'<uniqueId>'.$ahora->timestamp.'</uniqueId>'
            .'<generationTime>'.$ahora->copy()->subMinutes(2)->toIso8601String().'</generationTime>'
            .'<expirationTime>'.$ahora->copy()->addMinutes(10)->toIso8601String().'</expirationTime>'
            .'</header><service>wsfe</service></loginTicketRequest>';

        $sobre = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:wsaa="http://wsaa.view.sua.dvadac.desein.afip.gov">'
            .'<soapenv:Header/><soapenv:Body><wsaa:loginCms><wsaa:in0>'.$this->firmar($tra).'</wsaa:in0></wsaa:loginCms></soapenv:Body></soapenv:Envelope>';

        $respuesta = $this->soap($this->url('wsaa'), '', $sobre);
        $ticket = $this->valor($respuesta, 'loginCmsReturn');

        if (! $ticket) {
            throw new ArcaException('ARCA no devolvió el ticket de acceso.');
        }

        $xpath = $this->parsear($ticket);
        $token = $this->valor($xpath, 'token');
        $sign = $this->valor($xpath, 'sign');

        if (! $token || ! $sign) {
            throw new ArcaException('El ticket de acceso de ARCA vino incompleto.');
        }

        $vence = Carbon::parse($this->valor($xpath, 'expirationTime') ?? now()->addHours(11));

        return [['token' => $token, 'sign' => $sign], $vence];
    }

    /** Firma el pedido de acceso (CMS/PKCS#7 con el contenido adentro, en DER) y lo devuelve en base64. */
    private function firmar(string $tra): string
    {
        $certificado = storage_path('app/'.config('arca.certificado'));
        $clave = storage_path('app/'.config('arca.clave'));

        foreach (['El certificado' => $certificado, 'La clave' => $clave] as $nombre => $archivo) {
            if (! is_file($archivo)) {
                throw new ArcaException("{$nombre} de ARCA no está en el servidor: storage/app/".str($archivo)->after(storage_path('app/')).'.');
            }

            if (! is_readable($archivo)) {
                $usuario = function_exists('posix_getpwuid') ? (posix_getpwuid(posix_geteuid())['name'] ?? '?') : get_current_user();

                throw new ArcaException("{$nombre} de ARCA existe pero el usuario del servidor web ({$usuario}) no tiene permiso para leerlo: storage/app/".str($archivo)->after(storage_path('app/')).'.');
            }
        }

        $entrada = tempnam(sys_get_temp_dir(), 'tra');
        $salida = tempnam(sys_get_temp_dir(), 'cms');

        try {
            file_put_contents($entrada, $tra);

            $ok = openssl_cms_sign(
                $entrada,
                $salida,
                'file://'.$certificado,
                'file://'.$clave,
                null,
                OPENSSL_CMS_BINARY,
                OPENSSL_ENCODING_DER,
            );

            if (! $ok) {
                throw new ArcaException('No se pudo firmar el pedido de acceso a ARCA: '.openssl_error_string());
            }

            return base64_encode((string) file_get_contents($salida));
        } finally {
            @unlink($entrada);
            @unlink($salida);
        }
    }

    // --- SOAP / XML ----------------------------------------------------------------------

    private function soap(string $url, string $accion, string $sobre): DOMXPath
    {
        try {
            $respuesta = Http::withHeaders([
                'Content-Type' => 'text/xml; charset=utf-8',
                'SOAPAction' => '"'.$accion.'"',
            ])
                ->timeout((int) config('arca.timeout', 30))
                // Los servidores de ARCA negocian claves DH chicas que OpenSSL 3 rechaza por defecto.
                ->withOptions(['curl' => [CURLOPT_SSL_CIPHER_LIST => 'DEFAULT:@SECLEVEL=1']])
                ->withBody($sobre, 'text/xml; charset=utf-8')
                ->post($url);
        } catch (ConnectionException $e) {
            throw new ArcaException('No hubo respuesta de ARCA (sin conexión o tardó demasiado).', sinRespuesta: true, previous: $e);
        }

        $xpath = $this->parsear($respuesta->body());

        if ($fault = $this->valor($xpath, 'faultstring')) {
            throw new ArcaException($this->traducirFalla($fault));
        }

        if ($respuesta->serverError()) {
            throw new ArcaException("ARCA respondió con un error del servidor (HTTP {$respuesta->status()}).", sinRespuesta: true);
        }

        return $xpath;
    }

    private function traducirFalla(string $fault): string
    {
        if (str_contains($fault, 'ya posee un TA valido')) {
            return 'ARCA ya entregó un ticket de acceso vigente para este certificado a otra instalación (¿el entorno local?). Hay que esperar a que venza (hasta 12 h).';
        }

        return 'ARCA rechazó el pedido: '.trim($fault);
    }

    private function parsear(string $xml): DOMXPath
    {
        $doc = new DOMDocument;

        if (trim($xml) === '' || ! @$doc->loadXML($xml)) {
            throw new ArcaException('La respuesta de ARCA no se pudo leer.', sinRespuesta: true);
        }

        return new DOMXPath($doc);
    }

    private function valor(DOMXPath $xpath, string $nombre, ?\DOMNode $contexto = null): ?string
    {
        $nodo = $xpath->query(($contexto ? './/' : '//').'*[local-name()="'.$nombre.'"]', $contexto)->item(0);

        return $nodo ? trim($nodo->textContent) : null;
    }

    /**
     * @return list<array{code: string, msg: string}>
     */
    private function errores(DOMXPath $xpath): array
    {
        $errores = [];
        foreach ($xpath->query('//*[local-name()="Errors"]/*[local-name()="Err"]') as $err) {
            $errores[] = ['code' => (string) $this->valor($xpath, 'Code', $err), 'msg' => (string) $this->valor($xpath, 'Msg', $err)];
        }

        return $errores;
    }

    private function lanzarErrores(DOMXPath $xpath): void
    {
        if ($errores = $this->errores($xpath)) {
            throw new ArcaException('ARCA: '.collect($errores)->map(fn ($e) => "{$e['code']} - {$e['msg']}")->implode(' | '));
        }
    }

    private function url(string $servicio): string
    {
        return config('arca.urls.'.config('arca.entorno').'.'.$servicio)
            ?? throw new ArcaException('ARCA_ENTORNO inválido: '.config('arca.entorno'));
    }
}
