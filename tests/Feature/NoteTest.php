<?php

namespace Tests\Feature;

use App\Models\AiRequest;
use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NoteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->seedBadges();
    }

    public function test_a_text_note_can_be_created_with_folder_and_tags(): void
    {
        $user = User::factory()->create();
        $folder = $user->folders()->create(['name' => 'Historia', 'color' => 'amber']);

        $response = $this->actingAs($user)->post('/notes', [
            'content' => "# Revolución francesa\n\nComenzó en 1789 con la toma de la Bastilla.",
            'folder_id' => $folder->id,
            'new_tags' => 'Historia, Parcial',
        ]);

        $note = Note::firstOrFail();
        $response->assertRedirect(route('notes.show', $note));
        $this->assertSame('Revolución francesa', $note->title);
        $this->assertSame($folder->id, $note->folder_id);
        $this->assertEqualsCanonicalizing(['historia', 'parcial'], $note->tags->pluck('name')->all());
        $this->assertSame(1, $user->fresh()->current_streak);
    }

    public function test_a_plain_text_file_is_extracted_without_ai(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->createWithContent('apuntes_tema-1.txt', "La mitocondria produce ATP.\nEs la central energética de la célula.");

        $this->actingAs($user)->post('/notes', ['document' => $file])->assertSessionHas('success');

        $note = Note::firstOrFail();
        $this->assertSame('Apuntes Tema 1', $note->title);
        $this->assertStringContainsString('mitocondria produce ATP', $note->content);
        $this->assertSame('completed', $note->extraction_status);
        Storage::disk('local')->assertExists($note->file_path);
    }

    public function test_an_image_of_handwritten_notes_is_scanned_with_gemini(): void
    {
        $this->enableGemini();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->geminiText('La célula es la unidad básica de la vida.'))]);
        $user = User::factory()->create();

        $this->actingAs($user)->post('/notes', [
            'title' => 'Foto de mis apuntes',
            'document' => UploadedFile::fake()->image('apuntes.jpg'),
        ]);

        $note = Note::firstOrFail();
        $this->assertSame('image', $note->source_type);
        $this->assertSame('La célula es la unidad básica de la vida.', $note->content);

        Http::assertSent(function ($request) {
            $part = $request['contents'][0]['parts'][1]['inline_data'] ?? null;

            return $request->hasHeader('x-goog-api-key', 'test-key')
                && str_contains($request->url(), ':generateContent')
                && $part['mime_type'] === 'image/jpeg';
        });
        $this->assertDatabaseHas(AiRequest::class, ['type' => 'extract', 'success' => true, 'user_id' => $user->id]);
    }

    public function test_image_upload_is_saved_as_failed_when_ai_is_not_configured(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/notes', ['document' => UploadedFile::fake()->image('foto.png')])->assertSessionHas('error');

        $note = Note::firstOrFail();
        $this->assertSame('failed', $note->extraction_status);
        $this->assertStringContainsString('GEMINI_API_KEY', $note->extraction_error);
    }

    public function test_a_failed_note_can_be_rescanned(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/notes', ['document' => UploadedFile::fake()->image('foto.png')]);
        $note = Note::firstOrFail();

        $this->enableGemini();
        Http::fake(['*' => Http::response($this->geminiText('Texto recuperado'))]);

        $this->post(route('notes.rescan', $note))->assertSessionHas('success');

        $this->assertSame('Texto recuperado', $note->fresh()->content);
        $this->assertSame('completed', $note->fresh()->extraction_status);
    }

    public function test_unsupported_files_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/notes', ['document' => UploadedFile::fake()->create('virus.exe', 10)])
            ->assertSessionHasErrors('document');
    }

    public function test_ai_summary_is_generated(): void
    {
        $this->enableGemini();
        Http::fake(['*' => Http::response($this->geminiText("- Punto clave\nIdea principal: resumen"))]);
        $note = Note::factory()->create();

        $this->actingAs($note->user)->post(route('notes.summary', $note))->assertSessionHas('success');

        $this->assertStringContainsString('Idea principal', $note->fresh()->summary);
    }

    public function test_gemini_errors_are_shown_to_the_user(): void
    {
        $this->enableGemini();
        Http::fake(['*' => Http::response(['error' => ['message' => 'API key not valid']], 403)]);
        $note = Note::factory()->create();

        $this->actingAs($note->user)->post(route('notes.summary', $note))
            ->assertSessionHas('error', 'La clave de API de Gemini no es válida o no tiene permisos.');

        $this->assertDatabaseHas(AiRequest::class, ['type' => 'summary', 'success' => false]);
    }

    public function test_notes_can_be_filtered_by_folder_tag_and_search(): void
    {
        $user = User::factory()->create();
        $folder = $user->folders()->create(['name' => 'Física', 'color' => 'sky']);
        $tag = $user->tags()->create(['name' => 'repaso']);
        $match = Note::factory()->for($user)->create(['title' => 'Leyes de Newton', 'folder_id' => $folder->id]);
        $match->tags()->attach($tag);
        Note::factory()->for($user)->create(['title' => 'Termodinámica']);

        $this->actingAs($user);
        $this->get('/notes?folder='.$folder->id)->assertSee('Leyes de Newton')->assertDontSee('Termodinámica');
        $this->get('/notes?tag='.$tag->id)->assertSee('Leyes de Newton')->assertDontSee('Termodinámica');
        $this->get('/notes?q=Termo')->assertSee('Termodinámica')->assertDontSee('Leyes de Newton');
    }

    public function test_users_cannot_access_notes_of_others(): void
    {
        $note = Note::factory()->create();
        $intruder = User::factory()->create();

        $this->actingAs($intruder);
        $this->get(route('notes.show', $note))->assertForbidden();
        $this->put(route('notes.update', $note), ['title' => 'x', 'content' => 'y'])->assertForbidden();
        $this->delete(route('notes.destroy', $note))->assertForbidden();
    }

    public function test_deleting_a_folder_keeps_its_notes(): void
    {
        $user = User::factory()->create();
        $folder = $user->folders()->create(['name' => 'Química', 'color' => 'rose']);
        $note = Note::factory()->for($user)->create(['folder_id' => $folder->id]);

        $this->actingAs($user)->delete(route('folders.destroy', $folder))->assertRedirect(route('notes.index'));

        $this->assertNull($note->fresh()->folder_id);
    }

    public function test_the_librarian_badge_is_awarded_after_five_documents(): void
    {
        $user = User::factory()->create();
        Note::factory()->count(4)->for($user)->create();

        $this->actingAs($user)->post('/notes', ['content' => 'Quinta nota']);

        $this->assertTrue($user->badges()->where('slug', 'librarian')->exists());
    }
}
