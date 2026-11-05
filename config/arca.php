<?php

return [
    // Facturación electrónica ARCA (ex AFIP) por web service: Factura C de monotributo.
    // ARCA_HABILITADA=false apaga la opción "Factura ARCA" en Nueva venta.
    // Sin CUIT, punto de venta o certificado, tampoco se ofrece: la venta sigue igual.
    'habilitada' => (bool) env('ARCA_HABILITADA', false),

    // produccion | homologacion. El certificado de producción no sirve en homologación:
    // para probar hace falta otro, generado desde WSASS.
    'entorno' => env('ARCA_ENTORNO', 'homologacion'),

    'cuit' => env('ARCA_CUIT', ''),
    'punto_venta' => (int) env('ARCA_PUNTO_VENTA', 0),

    // Clave y certificado: storage/app/arca/ (gitignored). Rutas relativas a storage/app.
    'certificado' => env('ARCA_CERTIFICADO', 'arca/certificado.crt'),
    'clave' => env('ARCA_CLAVE', 'arca/clave.key'),

    // 1 = Productos. Con 2 (servicios) o 3 (productos y servicios) se informan además las
    // fechas del servicio y de vencimiento de pago (el mismo día de la factura).
    'concepto' => (int) env('ARCA_CONCEPTO', 1),

    // Las facturas salen a Consumidor Final sin identificar. A partir de este importe ARCA exige
    // identificar al comprador (DNI/CUIT); el sistema no lo pide, así que no factura por encima.
    // Confirmar el umbral vigente con el contador.
    'limite_consumidor_final' => (float) env('ARCA_LIMITE_CONSUMIDOR_FINAL', 10000000),

    // Datos del emisor que se imprimen en la factura. Vacíos hasta que los pase el contador.
    'emisor' => [
        'razon_social' => env('ARCA_RAZON_SOCIAL', ''),
        'domicilio' => env('ARCA_DOMICILIO', ''),
        'condicion_iva' => 'Responsable Monotributo',
        'ingresos_brutos' => env('ARCA_INGRESOS_BRUTOS'),
        'inicio_actividades' => env('ARCA_INICIO_ACTIVIDADES'), // dd/mm/aaaa
    ],

    'urls' => [
        'produccion' => [
            'wsaa' => 'https://wsaa.afip.gov.ar/ws/services/LoginCms',
            'wsfe' => 'https://servicios1.afip.gov.ar/wsfev1/service.asmx',
        ],
        'homologacion' => [
            'wsaa' => 'https://wsaahomo.afip.gov.ar/ws/services/LoginCms',
            'wsfe' => 'https://wswhomo.afip.gov.ar/wsfev1/service.asmx',
        ],
    ],

    // Segundos por llamada. Bajo a propósito: la factura se pide mientras el cajero espera.
    'timeout' => (int) env('ARCA_TIMEOUT', 10),
];
