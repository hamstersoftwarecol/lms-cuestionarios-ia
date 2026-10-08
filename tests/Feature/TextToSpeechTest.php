<?php

namespace Tests\Feature;

use App\Models\AiRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TextToSpeechTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_falls_back_to_the_browser_voice_without_an_api_key(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/tts', ['text' => 'Hola', 'voice' => 'Kore'])
            ->assertStatus(503)
            ->assertJson(['fallback' => true]);
    }

    public function test_gemini_audio_is_returned_as_a_cached_wav_file(): void
    {
        Storage::fake('local');
        $this->enableGemini();
        $pcm = str_repeat("\x00\x01", 2400);
        Http::fake(['*' => Http::response([
            'candidates' => [['content' => ['parts' => [['inlineData' => ['mimeType' => 'audio/L16;codec=pcm;rate=24000', 'data' => base64_encode($pcm)]]]], 'finishReason' => 'STOP']],
        ])]);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/tts', ['text' => 'La fotosíntesis', 'voice' => 'Puck']);

        $response->assertOk()->assertHeader('Content-Type', 'audio/wav');
        $wav = $response->baseResponse->getFile()->getContent();
        $this->assertSame('RIFF', substr($wav, 0, 4));
        $this->assertSame('WAVE', substr($wav, 8, 4));
        $this->assertSame(24000, unpack('V', substr($wav, 24, 4))[1]);
        $this->assertSame(strlen($pcm), unpack('V', substr($wav, 40, 4))[1]);

        Http::assertSent(fn ($request) => $request['generationConfig']['responseModalities'] === ['AUDIO']
            && $request['generationConfig']['speechConfig']['voiceConfig']['prebuiltVoiceConfig']['voiceName'] === 'Puck'
            && str_contains($request->url(), config('services.gemini.tts_model')));

        // La segunda petición con el mismo texto y voz sale de la caché.
        $this->post('/tts', ['text' => 'La fotosíntesis', 'voice' => 'Puck'])->assertOk();
        Http::assertSentCount(1);
        $this->assertSame(1, AiRequest::where('type', 'tts')->count());
    }

    public function test_only_the_eight_supported_voices_are_accepted(): void
    {
        $this->assertCount(8, config('lms.tts.voices'));

        $this->actingAs(User::factory()->create())
            ->postJson('/tts', ['text' => 'Hola', 'voice' => 'Robot'])
            ->assertJsonValidationErrors('voice');
    }

    public function test_requests_from_the_player_receive_json_errors_and_rate_limits(): void
    {
        $user = User::factory()->create();
        $headers = ['Accept' => 'application/json, audio/wav'];

        $this->actingAs($user)->post('/tts', ['voice' => 'Kore'], $headers)->assertStatus(422)->assertJsonValidationErrors('text');

        for ($i = 0; $i < 15; $i++) {
            $this->post('/tts', ['text' => 'Hola', 'voice' => 'Kore'], $headers);
        }

        $this->post('/tts', ['text' => 'Hola', 'voice' => 'Kore'], $headers)->assertStatus(429)->assertJson(['fallback' => true]);
    }
}
