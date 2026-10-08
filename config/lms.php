<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Documentos
    |--------------------------------------------------------------------------
    |
    | Tamaño máximo de subida (KB) y extensiones admitidas por el escáner. Gemini
    | acepta hasta 20 MB por petición en línea, por lo que 10 MB deja margen para
    | la codificación base64.
    |
    */

    'max_upload_kb' => (int) env('LMS_MAX_UPLOAD_KB', 10240),

    'allowed_extensions' => ['pdf', 'png', 'jpg', 'jpeg', 'webp', 'heic', 'heif', 'txt', 'md', 'docx'],

    // Caracteres máximos del material que se envían a la IA por petición.
    'max_ai_chars' => (int) env('LMS_MAX_AI_CHARS', 60000),

    /*
    |--------------------------------------------------------------------------
    | Cuestionarios
    |--------------------------------------------------------------------------
    */

    'quiz' => [
        'min_questions' => 3,
        'max_questions' => 30,
        'default_questions' => 10,
        'perfect_bonus' => 0.25, // +25 % de puntos al obtener el 100 %.
        'languages' => [
            'es' => 'Español',
            'en' => 'Inglés',
            'pt' => 'Portugués',
            'fr' => 'Francés',
            'auto' => 'Mismo idioma del documento',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Texto a voz (Gemini TTS)
    |--------------------------------------------------------------------------
    |
    | Ocho voces precompiladas de Gemini. La clave es el nombre que espera la API
    | y el valor la descripción que se muestra en la interfaz.
    |
    */

    'tts' => [
        'max_chars' => (int) env('LMS_TTS_MAX_CHARS', 3000),
        'voices' => [
            'Kore' => 'Kore · Firme',
            'Puck' => 'Puck · Animada',
            'Charon' => 'Charon · Informativa',
            'Zephyr' => 'Zephyr · Brillante',
            'Fenrir' => 'Fenrir · Enérgica',
            'Leda' => 'Leda · Juvenil',
            'Orus' => 'Orus · Seria',
            'Aoede' => 'Aoede · Fresca',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Verificación OTP por correo
    |--------------------------------------------------------------------------
    */

    'otp' => [
        'ttl_minutes' => 10,
        'max_attempts' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Administrador inicial (php artisan db:seed)
    |--------------------------------------------------------------------------
    */

    'admin' => [
        'name' => env('ADMIN_NAME', 'Administrador'),
        'email' => env('ADMIN_EMAIL', 'admin@lms.test'),
        'password' => env('ADMIN_PASSWORD', 'password'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Copias de seguridad
    |--------------------------------------------------------------------------
    */

    'backups' => [
        'keep' => (int) env('LMS_BACKUPS_KEEP', 14),
    ],

];
