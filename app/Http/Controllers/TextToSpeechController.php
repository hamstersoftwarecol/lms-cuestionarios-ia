<?php

namespace App\Http\Controllers;

use App\Services\Ai\GeminiClient;
use App\Services\Ai\GeminiException;
use App\Services\Ai\TextToSpeech;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Devuelve un WAV generado con Gemini TTS. Si la IA no está configurada responde
 * 503 con `fallback: true` para que el navegador use su síntesis de voz nativa.
 */
class TextToSpeechController extends Controller
{
    public function __invoke(Request $request, GeminiClient $gemini, TextToSpeech $tts): BinaryFileResponse|JsonResponse
    {
        $validated = $request->validate([
            'text' => ['required', 'string', 'max:20000'],
            'voice' => ['required', Rule::in(array_keys(TextToSpeech::voices()))],
        ]);

        if (! $gemini->isConfigured()) {
            return response()->json(['fallback' => true, 'message' => 'Gemini no está configurado; se usará la voz del navegador.'], 503);
        }

        set_time_limit(120);

        try {
            $path = $tts->synthesize($validated['text'], $validated['voice']);
        } catch (GeminiException $e) {
            return response()->json(['fallback' => true, 'message' => $e->getMessage()], 422);
        }

        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => 'audio/wav',
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}
