<?php

namespace App\Services\Ai;

use App\Models\AiRequest;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Cliente mínimo para la API REST de Google Gemini (generateContent).
 *
 * @see https://ai.google.dev/api/generate-content
 */
class GeminiClient
{
    public function __construct(
        private readonly ?string $apiKey,
        private readonly string $model,
        private readonly string $ttsModel,
        private readonly string $baseUrl,
        private readonly int $timeout = 120,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            config('services.gemini.api_key'),
            config('services.gemini.model'),
            config('services.gemini.tts_model'),
            rtrim(config('services.gemini.base_url'), '/'),
            config('services.gemini.timeout', 120),
        );
    }

    public function isConfigured(): bool
    {
        return filled($this->apiKey);
    }

    public function model(): string
    {
        return $this->model;
    }

    public function ttsModel(): string
    {
        return $this->ttsModel;
    }

    /**
     * Genera texto plano a partir de las partes indicadas (texto y/o archivos en línea).
     *
     * @param  array<int, array<string, mixed>>  $parts
     */
    public function generateText(array $parts, string $type, array $generationConfig = []): string
    {
        $response = $this->generate($parts, $type, $generationConfig);

        return trim($this->extractText($response));
    }

    /**
     * Genera una respuesta JSON validada contra el esquema indicado (salida estructurada).
     *
     * @param  array<int, array<string, mixed>>  $parts
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    public function generateJson(array $parts, array $schema, string $type, array $generationConfig = []): array
    {
        $response = $this->generate($parts, $type, array_merge([
            'responseMimeType' => 'application/json',
            'responseSchema' => $schema,
        ], $generationConfig));

        $text = trim($this->extractText($response));
        $text = preg_replace('/^```(?:json)?\s*|\s*```$/', '', $text);
        $decoded = json_decode((string) $text, true);

        if (! is_array($decoded)) {
            throw new GeminiException('La IA devolvió una respuesta que no es JSON válido. Inténtalo de nuevo.');
        }

        return $decoded;
    }

    /**
     * Convierte texto a voz y devuelve el audio PCM crudo junto con su frecuencia de muestreo.
     *
     * @return array{pcm: string, rate: int}
     */
    public function speech(string $text, string $voice): array
    {
        $response = $this->generate([['text' => $text]], 'tts', [
            'responseModalities' => ['AUDIO'],
            'speechConfig' => [
                'voiceConfig' => ['prebuiltVoiceConfig' => ['voiceName' => $voice]],
            ],
        ], $this->ttsModel);

        foreach ($response['candidates'][0]['content']['parts'] ?? [] as $part) {
            $inline = $part['inlineData'] ?? $part['inline_data'] ?? null;

            if ($inline && isset($inline['data'])) {
                $mime = $inline['mimeType'] ?? $inline['mime_type'] ?? '';
                $rate = preg_match('/rate=(\d+)/', $mime, $m) ? (int) $m[1] : 24000;

                return ['pcm' => base64_decode($inline['data']), 'rate' => $rate];
            }
        }

        throw new GeminiException('La IA no devolvió audio. Inténtalo con otro texto o voz.');
    }

    /**
     * @param  array<int, array<string, mixed>>  $parts
     * @param  array<string, mixed>  $generationConfig
     * @return array<string, mixed>
     */
    public function generate(array $parts, string $type, array $generationConfig = [], ?string $model = null): array
    {
        if (! $this->isConfigured()) {
            throw GeminiException::notConfigured();
        }

        $model ??= $this->model;
        $startedAt = microtime(true);
        $payload = ['contents' => [['role' => 'user', 'parts' => $parts]]];

        if ($generationConfig !== []) {
            $payload['generationConfig'] = $generationConfig;
        }

        try {
            $response = Http::withHeaders(['x-goog-api-key' => $this->apiKey])
                ->acceptJson()
                ->timeout($this->timeout)
                ->retry(2, 1500, fn ($exception) => $this->shouldRetry($exception), throw: false)
                ->post("{$this->baseUrl}/models/{$model}:generateContent", $payload);
        } catch (ConnectionException $e) {
            $this->record($type, $model, $startedAt, error: $e->getMessage());

            throw new GeminiException('No se pudo conectar con Google Gemini. Revisa tu conexión e inténtalo de nuevo.', previous: $e);
        }

        if ($response->failed()) {
            $message = $response->json('error.message') ?? $response->body();
            $this->record($type, $model, $startedAt, error: Str::limit((string) $message, 500));
            Log::warning('Gemini request failed', ['status' => $response->status(), 'message' => $message]);

            throw new GeminiException($this->friendlyError($response));
        }

        $json = $response->json() ?? [];
        $finishReason = $json['candidates'][0]['finishReason'] ?? null;

        if (empty($json['candidates']) || in_array($finishReason, ['SAFETY', 'PROHIBITED_CONTENT', 'BLOCKLIST', 'RECITATION'], true)) {
            $reason = $json['promptFeedback']['blockReason'] ?? $finishReason ?? 'desconocido';
            $this->record($type, $model, $startedAt, $json, "Respuesta bloqueada: {$reason}");

            throw new GeminiException("La IA bloqueó la respuesta (motivo: {$reason}). Prueba con otro contenido.");
        }

        $this->record($type, $model, $startedAt, $json);

        return $json;
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function extractText(array $response): string
    {
        return collect($response['candidates'][0]['content']['parts'] ?? [])
            ->reject(fn (array $part) => $part['thought'] ?? false)
            ->pluck('text')
            ->filter()
            ->implode('');
    }

    private function shouldRetry(mixed $exception): bool
    {
        if ($exception instanceof ConnectionException) {
            return true;
        }

        $status = $exception->response?->status();

        return in_array($status, [429, 500, 503], true);
    }

    private function friendlyError(Response $response): string
    {
        return match (true) {
            $response->status() === 400 => 'Gemini rechazó la petición: '.Str::limit((string) $response->json('error.message'), 200),
            in_array($response->status(), [401, 403], true) => 'La clave de API de Gemini no es válida o no tiene permisos.',
            $response->status() === 404 => 'El modelo de Gemini configurado no existe. Revisa GEMINI_MODEL en .env.',
            $response->status() === 429 => 'Se alcanzó el límite de uso de Gemini. Espera un momento e inténtalo de nuevo.',
            default => 'Google Gemini no está disponible en este momento. Inténtalo más tarde.',
        };
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function record(string $type, string $model, float $startedAt, array $response = [], ?string $error = null): void
    {
        try {
            AiRequest::create([
                'user_id' => Auth::id(),
                'type' => $type,
                'model' => $model,
                'success' => $error === null,
                'prompt_tokens' => (int) ($response['usageMetadata']['promptTokenCount'] ?? 0),
                'output_tokens' => (int) ($response['usageMetadata']['candidatesTokenCount'] ?? 0),
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'error' => $error,
            ]);
        } catch (\Throwable $e) {
            Log::error('No se pudo registrar el uso de IA', ['error' => $e->getMessage()]);
        }
    }
}
