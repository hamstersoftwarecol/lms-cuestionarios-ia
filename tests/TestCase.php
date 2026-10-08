<?php

namespace Tests;

use App\Services\Ai\GeminiClient;
use Database\Seeders\BadgeSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    protected function seedBadges(): void
    {
        $this->seed(BadgeSeeder::class);
    }

    /**
     * Activa Gemini con una clave falsa (las peticiones se simulan con Http::fake()).
     */
    protected function enableGemini(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        $this->app->forgetInstance(GeminiClient::class);
    }

    /**
     * Respuesta de generateContent con el texto indicado.
     *
     * @return array<string, mixed>
     */
    protected function geminiText(string $text): array
    {
        return [
            'candidates' => [['content' => ['parts' => [['text' => $text]], 'role' => 'model'], 'finishReason' => 'STOP']],
            'usageMetadata' => ['promptTokenCount' => 120, 'candidatesTokenCount' => 80],
        ];
    }
}
