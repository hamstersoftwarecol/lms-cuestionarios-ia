<?php

namespace Tests\Feature;

use App\Enums\QuestionType;
use App\Models\Note;
use App\Models\Quiz;
use App\Models\User;
use App\Services\Ai\QuizGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class QuizGenerationTest extends TestCase
{
    use RefreshDatabase;

    private function fakeQuizResponse(): void
    {
        Http::fake(['*' => Http::response($this->geminiText(json_encode([
            'title' => 'La fotosíntesis',
            'description' => 'Conceptos básicos',
            'questions' => [
                ['type' => 'mcq', 'question' => '¿Dónde ocurre?', 'options' => ['Mitocondria', 'Cloroplasto', 'Núcleo', 'Ribosoma'], 'correct_answers' => [1], 'explanation' => 'En el cloroplasto.', 'difficulty' => 'easy'],
                ['type' => 'sata', 'question' => 'Factores que influyen', 'options' => ['Luz', 'CO₂', 'Magnetismo', 'Agua', 'Sonido'], 'correct_answers' => [0, 1, 3], 'explanation' => 'Luz, CO₂ y agua.', 'difficulty' => 'medium'],
                // Marcada como MCQ pero con dos correctas: se convierte en SATA.
                ['type' => 'mcq', 'question' => 'Productos', 'options' => ['O₂', 'Glucosa', 'N₂', 'Helio'], 'correct_answers' => [0, 1], 'explanation' => '', 'difficulty' => 'hard'],
                // Inválidas: índice fuera de rango y opciones duplicadas.
                ['type' => 'mcq', 'question' => 'Mala', 'options' => ['A', 'B'], 'correct_answers' => [5], 'explanation' => 'x', 'difficulty' => 'easy'],
                ['type' => 'mcq', 'question' => 'Duplicada', 'options' => ['A', 'A', 'B'], 'correct_answers' => [0], 'explanation' => 'x', 'difficulty' => 'easy'],
            ],
        ])))]);
    }

    public function test_a_quiz_is_generated_from_a_note_with_gemini(): void
    {
        $this->enableGemini();
        $this->fakeQuizResponse();
        $note = Note::factory()->create(['content' => 'La fotosíntesis ocurre en los cloroplastos…']);

        $response = $this->actingAs($note->user)->post('/quizzes', [
            'mode' => 'ai',
            'note_id' => $note->id,
            'question_count' => 10,
            'question_types' => 'mixed',
            'difficulty' => 'mixed',
            'language' => 'es',
            'time_limit' => 15,
            'is_public' => '1',
        ]);

        $quiz = Quiz::with('questions')->firstOrFail();
        $response->assertRedirect(route('quizzes.show', $quiz));

        $this->assertSame('La fotosíntesis', $quiz->title);
        $this->assertTrue($quiz->generated_by_ai);
        $this->assertTrue($quiz->is_public);
        $this->assertSame(15, $quiz->time_limit);
        $this->assertCount(3, $quiz->questions);
        $this->assertSame(QuestionType::Sata, $quiz->questions[2]->type);
        $this->assertSame([0, 1, 3], $quiz->questions[1]->correct_answers);

        Http::assertSent(fn ($request) => $request['generationConfig']['responseMimeType'] === 'application/json'
            && isset($request['generationConfig']['responseSchema']['properties']['questions'])
            && str_contains($request['contents'][0]['parts'][0]['text'], 'exactamente 10 preguntas'));
    }

    public function test_only_mcq_questions_are_kept_when_requested(): void
    {
        $generator = app(QuizGenerator::class);

        $this->assertNull($generator->sanitize([
            'type' => 'sata', 'question' => 'x', 'options' => ['a', 'b', 'c'], 'correct_answers' => [0, 1],
        ], 'mcq'));
        $this->assertNotNull($generator->sanitize([
            'type' => 'mcq', 'question' => 'x', 'options' => ['a', 'b', 'c'], 'correct_answers' => [2],
        ], 'mcq'));
    }

    public function test_generation_requires_ai_to_be_configured(): void
    {
        $note = Note::factory()->create();

        $this->actingAs($note->user)->post('/quizzes', [
            'mode' => 'ai', 'note_id' => $note->id, 'question_count' => 5,
            'question_types' => 'mcq', 'difficulty' => 'easy', 'language' => 'es',
        ])->assertSessionHas('error');

        $this->assertDatabaseCount('quizzes', 0);
    }

    public function test_notes_of_other_users_cannot_be_used(): void
    {
        $note = Note::factory()->create();

        $this->actingAs(User::factory()->create())->post('/quizzes', [
            'mode' => 'ai', 'note_id' => $note->id, 'question_count' => 5,
            'question_types' => 'mcq', 'difficulty' => 'easy', 'language' => 'es',
        ])->assertSessionHasErrors('note_id');
    }

    public function test_a_manual_quiz_can_be_built_question_by_question(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/quizzes', [
            'mode' => 'manual', 'title' => 'Mi examen', 'difficulty' => 'medium', 'language' => 'es',
        ]);
        $quiz = Quiz::firstOrFail();
        $this->assertFalse($quiz->generated_by_ai);

        $this->post(route('questions.store', $quiz), [
            'type' => 'sata',
            'question_text' => '¿Cuáles son números primos?',
            'options' => ['2', '', '4', '5'],
            'correct_answers' => [0, 3],
            'difficulty' => 'easy',
        ])->assertSessionHasNoErrors();

        $question = $quiz->questions()->firstOrFail();
        // La opción vacía se elimina y los índices correctos se reajustan.
        $this->assertSame(['2', '4', '5'], $question->options);
        $this->assertSame([0, 2], $question->correct_answers);
    }

    public function test_mcq_questions_need_exactly_one_correct_answer(): void
    {
        $quiz = Quiz::factory()->create();

        $this->actingAs($quiz->user)->post(route('questions.store', $quiz), [
            'type' => 'mcq',
            'question_text' => 'Pregunta',
            'options' => ['a', 'b', 'c'],
            'correct_answers' => [0, 1],
            'difficulty' => 'easy',
        ])->assertSessionHasErrors('correct_answers');
    }

    public function test_only_the_owner_can_edit_a_quiz(): void
    {
        $quiz = Quiz::factory()->withQuestions()->create(['is_public' => true]);
        $other = User::factory()->create();

        $this->actingAs($other);
        $this->get(route('quizzes.show', $quiz))->assertOk();
        $this->get(route('quizzes.edit', $quiz))->assertForbidden();
        $this->put(route('questions.update', $quiz->questions->first()), [])->assertForbidden();
    }

    public function test_private_quizzes_are_hidden_from_other_users(): void
    {
        $quiz = Quiz::factory()->create(['is_public' => false]);

        $this->actingAs(User::factory()->create())->get(route('quizzes.show', $quiz))->assertForbidden();
    }
}
