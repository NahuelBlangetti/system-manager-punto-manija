<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4.1-mini'),
    ],

    'discord' => [
        'webhook_url' => env('DISCORD_WEBHOOK_URL'),
    ],

    'google_maps' => [
        'key' => env('GOOGLE_MAPS_API_KEY'),
    ],

    'import' => [
        'max_chunks' => (int) env('IMPORT_MAX_CHUNKS', 25),
        'chunk_size' => (int) env('IMPORT_CHUNK_SIZE', 4000),
        'stale_after_minutes' => (int) env('IMPORT_STALE_AFTER_MINUTES', 15),
    ],

    'print_agent' => [
        // Nombre exacto (case-sensitive) con el que Windows tiene instalada la
        // impresora de tickets/etiquetas. Debe coincidir tal cual con el nombre que
        // usa el Print Agent local para encontrarla.
        'ticket_printer' => env('PRINT_AGENT_TICKET_PRINTER', 'POS-80C'),
    ],

    'mercadopago' => [
        // Access Token de PRODUCCIÓN de la aplicación de Mercado Pago de la
        // cuenta dueña del posnet (Tus integraciones → Credenciales).
        'access_token' => env('MERCADOPAGO_ACCESS_TOKEN'),
        // Clave secreta de Webhooks (Tus integraciones → Webhooks). Si está
        // vacía no se valida la firma x-signature.
        'webhook_secret' => env('MERCADOPAGO_WEBHOOK_SECRET'),
        // Terminal por defecto (ej. NEWLAND_N950__N950NCB801293324). Se puede
        // pisar desde el panel en "Posnet Mercado Pago".
        'point_terminal_id' => env('MERCADOPAGO_POINT_TERMINAL_ID'),
        // Tiempo que el cobro queda disponible en el posnet (ISO 8601).
        'point_expiration' => env('MERCADOPAGO_POINT_EXPIRATION', 'PT5M'),
        // no_ticket | seller_ticket | full_ticket (impresión del posnet).
        'point_print_on_terminal' => env('MERCADOPAGO_POINT_PRINT', 'no_ticket'),
        'base_url' => env('MERCADOPAGO_BASE_URL', 'https://api.mercadopago.com'),
    ],

];
