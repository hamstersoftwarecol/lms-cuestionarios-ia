<?php

namespace App\Http\Controllers;

use App\Http\Requests\NoteRequest;
use App\Models\Note;
use App\Services\ActivityLogger;
use App\Services\Ai\DocumentScanner;
use App\Services\Ai\GeminiClient;
use App\Services\Ai\GeminiException;
use App\Services\Ai\QuizGenerator;
use App\Services\Ai\TextToSpeech;
use App\Services\GamificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NoteController extends Controller
{
    public function __construct(
        private readonly DocumentScanner $scanner,
        private readonly GamificationService $gamification,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $notes = $user->notes()
            ->with('folder', 'tags')
            ->withCount('quizzes')
            ->search($request->string('q')->toString())
            ->when($request->filled('folder'), fn ($q) => $request->folder === 'none'
                ? $q->whereNull('folder_id')
                : $q->where('folder_id', $request->integer('folder')))
            ->when($request->filled('tag'), fn ($q) => $q->whereHas('tags', fn ($t) => $t->where('tags.id', $request->integer('tag'))))
            ->when($request->boolean('favorites'), fn ($q) => $q->where('is_favorite', true))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('notes.index', [
            'notes' => $notes,
            'folders' => $user->folders()->withCount('notes')->orderBy('name')->get(),
            'tags' => $user->tags()->withCount('notes')->orderBy('name')->get(),
            'totalNotes' => $user->notes()->count(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('notes.create', [
            'folders' => $request->user()->folders()->orderBy('name')->get(),
            'tags' => $request->user()->tags()->orderBy('name')->get(),
            'aiEnabled' => app(GeminiClient::class)->isConfigured(),
        ]);
    }

    public function store(NoteRequest $request): RedirectResponse
    {
        set_time_limit(180);
        $user = $request->user();
        $data = $request->safe()->only(['title', 'content', 'folder_id']);

        if ($file = $request->file('document')) {
            $extension = Str::lower($file->getClientOriginalExtension());
            $data += [
                'original_filename' => $file->getClientOriginalName(),
                'file_path' => $file->store("documents/{$user->id}", 'local'),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ];
            $data['title'] = $data['title'] ?: Str::of($file->getClientOriginalName())->beforeLast('.')->replace(['_', '-'], ' ')->title()->limit(120)->toString();

            try {
                $result = $this->scanner->scan(Storage::disk('local')->path($data['file_path']), $extension, $data['mime_type']);
                $data['content'] = trim(($data['content'] ?? '')."\n\n".$result->text);
                $data['source_type'] = $result->sourceType;
                $data['extraction_status'] = 'completed';
            } catch (GeminiException $e) {
                $data['source_type'] = $this->sourceTypeFor($extension);
                $data['extraction_status'] = 'failed';
                $data['extraction_error'] = $e->getMessage();
            }
        } else {
            $data['title'] = $data['title'] ?: Str::limit(ltrim((string) Str::of($data['content'])->trim()->explode("\n")->first(), '# '), 80);
            $data['source_type'] = 'text';
        }

        $note = $user->notes()->create($data);
        $note->tags()->sync($request->tagIds());

        ActivityLogger::log('note.created', "Creó la nota «{$note->title}»", $note, ['source' => $note->source_type]);
        $this->gamification->registerActivity($user);
        $this->gamification->checkBadges($user);

        return redirect()->route('notes.show', $note)->with(
            $note->extraction_status === 'failed' ? 'error' : 'success',
            $note->extraction_status === 'failed'
                ? "Documento guardado, pero no se pudo extraer el texto: {$note->extraction_error}"
                : ($note->source_type === 'text' ? 'Nota creada.' : 'Documento escaneado correctamente. ¡Ya puedes generar un cuestionario!'),
        );
    }

    public function show(Note $note): View
    {
        $this->authorize('view', $note);

        $note->load('folder', 'tags', 'quizzes');

        return view('notes.show', [
            'note' => $note,
            'voices' => TextToSpeech::voices(),
            'aiEnabled' => app(GeminiClient::class)->isConfigured(),
        ]);
    }

    public function edit(Request $request, Note $note): View
    {
        $this->authorize('update', $note);

        return view('notes.edit', [
            'note' => $note->load('tags'),
            'folders' => $request->user()->folders()->orderBy('name')->get(),
            'tags' => $request->user()->tags()->orderBy('name')->get(),
        ]);
    }

    public function update(NoteRequest $request, Note $note): RedirectResponse
    {
        $this->authorize('update', $note);

        $note->update($request->safe()->only(['title', 'content', 'folder_id']));
        $note->tags()->sync($request->tagIds());

        ActivityLogger::log('note.updated', "Editó la nota «{$note->title}»", $note);

        return redirect()->route('notes.show', $note)->with('success', 'Nota actualizada.');
    }

    public function destroy(Note $note): RedirectResponse
    {
        $this->authorize('delete', $note);

        if ($note->file_path) {
            Storage::disk('local')->delete($note->file_path);
        }

        ActivityLogger::log('note.deleted', "Eliminó la nota «{$note->title}»");
        $note->delete();

        return redirect()->route('notes.index')->with('success', 'Nota eliminada.');
    }

    public function toggleFavorite(Note $note): RedirectResponse
    {
        $this->authorize('update', $note);

        $note->update(['is_favorite' => ! $note->is_favorite]);

        return back();
    }

    public function summarize(Note $note, QuizGenerator $generator): RedirectResponse
    {
        $this->authorize('update', $note);
        abort_unless($note->hasContent(), 422, 'La nota no tiene contenido.');
        set_time_limit(120);

        try {
            $note->update(['summary' => $generator->summarize($note->content)]);
        } catch (GeminiException $e) {
            return back()->with('error', $e->getMessage());
        }

        ActivityLogger::log('ai.summary', "Generó un resumen con IA de «{$note->title}»", $note);

        return back()->with('success', 'Resumen generado con IA.');
    }

    public function rescan(Note $note): RedirectResponse
    {
        $this->authorize('update', $note);
        abort_unless($note->file_path && Storage::disk('local')->exists($note->file_path), 404);
        set_time_limit(180);

        try {
            $result = $this->scanner->scan(
                Storage::disk('local')->path($note->file_path),
                pathinfo($note->original_filename ?? $note->file_path, PATHINFO_EXTENSION),
                $note->mime_type,
            );
        } catch (GeminiException $e) {
            $note->update(['extraction_status' => 'failed', 'extraction_error' => $e->getMessage()]);

            return back()->with('error', $e->getMessage());
        }

        $note->update([
            'content' => $result->text,
            'source_type' => $result->sourceType,
            'extraction_status' => 'completed',
            'extraction_error' => null,
        ]);

        ActivityLogger::log('ai.extract', "Volvió a escanear «{$note->title}»", $note);

        return back()->with('success', 'Documento escaneado de nuevo.');
    }

    public function download(Note $note): StreamedResponse
    {
        $this->authorize('view', $note);
        abort_unless($note->file_path && Storage::disk('local')->exists($note->file_path), 404);

        return Storage::disk('local')->download($note->file_path, $note->original_filename);
    }

    private function sourceTypeFor(string $extension): string
    {
        return match (true) {
            $extension === 'pdf' => 'pdf',
            $extension === 'docx' => 'docx',
            in_array($extension, ['png', 'jpg', 'jpeg', 'webp', 'heic', 'heif'], true) => 'image',
            default => 'text',
        };
    }
}
