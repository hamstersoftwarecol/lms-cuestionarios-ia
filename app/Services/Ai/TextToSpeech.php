<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Texto a voz con las voces precompiladas de Gemini. El audio se guarda en caché
 * en disco (WAV) para no volver a pagar por el mismo texto y voz.
 */
class TextToSpeech
{
    private const CACHE_DIR = 'tts';

    public function __construct(private readonly GeminiClient $gemini) {}

    /**
     * @return array<string, string>
     */
    public static function voices(): array
    {
        return config('lms.tts.voices');
    }

    public static function isValidVoice(?string $voice): bool
    {
        return $voice !== null && array_key_exists($voice, self::voices());
    }

    /**
     * Devuelve la ruta relativa (disco local) del WAV generado.
     */
    public function synthesize(string $text, string $voice): string
    {
        $voice = self::isValidVoice($voice) ? $voice : array_key_first(self::voices());
        $text = Str::limit(trim(strip_tags($text)), config('lms.tts.max_chars'), '…');

        if ($text === '') {
            throw new GeminiException('No hay texto para leer en voz alta.');
        }

        $path = self::CACHE_DIR.'/'.hash('sha256', $voice.'|'.$this->gemini->ttsModel().'|'.$text).'.wav';

        if (Storage::disk('local')->exists($path)) {
            return $path;
        }

        $audio = $this->gemini->speech("Lee en voz alta con un tono claro y didáctico:\n\n{$text}", $voice);

        Storage::disk('local')->put($path, $this->pcmToWav($audio['pcm'], $audio['rate']));

        return $path;
    }

    /**
     * Gemini devuelve PCM lineal de 16 bits mono; se añade la cabecera RIFF/WAV.
     */
    public function pcmToWav(string $pcm, int $sampleRate = 24000, int $channels = 1, int $bitsPerSample = 16): string
    {
        $byteRate = $sampleRate * $channels * $bitsPerSample / 8;
        $blockAlign = $channels * $bitsPerSample / 8;
        $dataSize = strlen($pcm);

        return 'RIFF'
            .pack('V', 36 + $dataSize)
            .'WAVE'
            .'fmt '
            .pack('V', 16)
            .pack('v', 1)
            .pack('v', $channels)
            .pack('V', $sampleRate)
            .pack('V', $byteRate)
            .pack('v', $blockAlign)
            .pack('v', $bitsPerSample)
            .'data'
            .pack('V', $dataSize)
            .$pcm;
    }
}
