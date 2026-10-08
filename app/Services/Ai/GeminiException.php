<?php

namespace App\Services\Ai;

use RuntimeException;

class GeminiException extends RuntimeException
{
    public static function notConfigured(): self
    {
        return new self('La IA no está configurada. Añade GEMINI_API_KEY a tu archivo .env.');
    }
}
