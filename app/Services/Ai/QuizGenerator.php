<?php

namespace App\Services\Ai;

use App\Enums\Difficulty;
use App\Enums\QuestionType;
use Illuminate\Support\Str;

/**
 * Genera preguntas MCQ (una respuesta) y SATA (selecciona todas las que apliquen)
 * con explicación y nivel de dificultad a partir del material de estudio.
 */
class QuizGenerator
{
    public function __construct(private readonly GeminiClient $gemini) {}

    /**
     * @param  'mcq'|'sata'|'mixed'  $types
     * @return array{title: string, description: string, questions: array<int, array<string, mixed>>}
     */
    public function generate(string $content, int $count, string $types = 'mixed', string $difficulty = 'mixed', string $language = 'es'): array
    {
        $material = Str::limit($content, config('lms.max_ai_chars'), '…');

        $data = $this->gemini->generateJson(
            [['text' => $this->prompt($material, $count, $types, $difficulty, $language)]],
            $this->schema(),
            'quiz',
            ['temperature' => 0.7],
        );

        $questions = collect($data['questions'] ?? [])
            ->map(fn ($question) => is_array($question) ? $this->sanitize($question, $types, $difficulty) : null)
            ->filter()
            ->take($count)
            ->values()
            ->all();

        if ($questions === []) {
            throw new GeminiException('La IA no generó preguntas válidas. Prueba con más contenido o menos preguntas.');
        }

        return [
            'title' => Str::limit(trim((string) ($data['title'] ?? '')), 120, ''),
            'description' => Str::limit(trim((string) ($data['description'] ?? '')), 500),
            'questions' => $questions,
        ];
    }

    /**
     * Resume el material en viñetas para repasar o escuchar con texto a voz.
     */
    public function summarize(string $content, string $language = 'es'): string
    {
        $material = Str::limit($content, config('lms.max_ai_chars'), '…');
        $languageName = $this->languageName($language);

        return $this->gemini->generateText([[
            'text' => <<<PROMPT
                Resume el siguiente material de estudio en {$languageName} para un estudiante. Usa entre 5 y 10
                viñetas en Markdown con los conceptos clave, definiciones y datos importantes. Termina con una
                línea "Idea principal:" de una sola frase. No inventes información que no esté en el material.

                MATERIAL:
                """
                {$material}
                """
                PROMPT,
        ]], 'summary', ['temperature' => 0.3]);
    }

    /**
     * Valida y normaliza una pregunta devuelta por la IA. Devuelve null si no es utilizable.
     *
     * @param  array<string, mixed>  $question
     * @return array<string, mixed>|null
     */
    public function sanitize(array $question, string $types = 'mixed', string $difficulty = 'mixed'): ?array
    {
        $text = trim((string) ($question['question'] ?? $question['question_text'] ?? ''));
        $options = collect($question['options'] ?? [])
            ->map(fn ($option) => trim((string) $option))
            ->filter()
            ->values();

        if ($text === '' || $options->count() < 2 || $options->unique()->count() !== $options->count()) {
            return null;
        }

        $options = $options->take(6);
        $correct = collect($question['correct_answers'] ?? [])
            ->map(fn ($index) => (int) $index)
            ->filter(fn (int $index) => $index >= 0 && $index < $options->count())
            ->unique()
            ->sort()
            ->values();

        if ($correct->isEmpty()) {
            return null;
        }

        $type = QuestionType::tryFrom((string) ($question['type'] ?? '')) ?? QuestionType::Mcq;

        // Una pregunta con varias respuestas correctas siempre es SATA.
        if ($correct->count() > 1) {
            $type = QuestionType::Sata;
        }

        if ($types === 'mcq' && $type === QuestionType::Sata) {
            return null;
        }

        $questionDifficulty = Difficulty::tryFrom((string) ($question['difficulty'] ?? ''));

        if (! in_array($questionDifficulty, Difficulty::forQuestions(), true)) {
            $questionDifficulty = in_array(Difficulty::tryFrom($difficulty), Difficulty::forQuestions(), true)
                ? Difficulty::from($difficulty)
                : Difficulty::Medium;
        }

        return [
            'type' => $type->value,
            'question_text' => $text,
            'options' => $options->all(),
            'correct_answers' => $correct->all(),
            'explanation' => trim((string) ($question['explanation'] ?? '')) ?: null,
            'difficulty' => $questionDifficulty->value,
        ];
    }

    private function prompt(string $material, int $count, string $types, string $difficulty, string $language): string
    {
        $typeRule = match ($types) {
            'mcq' => 'Todas las preguntas deben ser de tipo "mcq".',
            'sata' => 'Todas las preguntas deben ser de tipo "sata".',
            default => 'Combina ambos tipos: aproximadamente 60 % "mcq" y 40 % "sata".',
        };

        $difficultyRule = match ($difficulty) {
            'easy' => 'Todas las preguntas de dificultad "easy".',
            'medium' => 'Todas las preguntas de dificultad "medium".',
            'hard' => 'Todas las preguntas de dificultad "hard".',
            default => 'Mezcla dificultades: 30 % "easy", 40 % "medium" y 30 % "hard".',
        };

        $languageName = $this->languageName($language);

        return <<<PROMPT
            Eres un docente experto en diseño de evaluaciones. Genera exactamente {$count} preguntas basadas
            ÚNICAMENTE en el material de estudio que aparece al final.

            Reglas:
            - {$typeRule}
            - "mcq": 4 opciones y exactamente 1 respuesta correcta.
            - "sata" (selecciona todas las que apliquen): 5 opciones y entre 2 y 4 respuestas correctas.
            - "correct_answers" contiene los índices (empezando en 0) de las opciones correctas.
            - {$difficultyRule} "easy" = recordar, "medium" = comprender/aplicar, "hard" = analizar/evaluar.
            - "explanation": explica en 1-3 frases por qué la respuesta es correcta y por qué las demás no.
            - Varía la posición de las respuestas correctas y evita "Todas las anteriores" o "Ninguna de las anteriores".
            - No repitas preguntas ni incluyas información que no esté en el material.
            - Redacta título, descripción, preguntas, opciones y explicaciones en {$languageName}.

            MATERIAL:
            """
            {$material}
            """
            PROMPT;
    }

    private function languageName(string $language): string
    {
        return match ($language) {
            'en' => 'inglés',
            'pt' => 'portugués',
            'fr' => 'francés',
            'auto' => 'el mismo idioma en que está escrito el material',
            default => 'español',
        };
    }

    /**
     * Esquema de salida estructurada (subconjunto OpenAPI que admite Gemini).
     *
     * @return array<string, mixed>
     */
    private function schema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'title' => ['type' => 'STRING', 'description' => 'Título breve del cuestionario'],
                'description' => ['type' => 'STRING', 'description' => 'Una frase que describe lo que evalúa'],
                'questions' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'type' => ['type' => 'STRING', 'enum' => ['mcq', 'sata']],
                            'question' => ['type' => 'STRING'],
                            'options' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                            'correct_answers' => ['type' => 'ARRAY', 'items' => ['type' => 'INTEGER']],
                            'explanation' => ['type' => 'STRING'],
                            'difficulty' => ['type' => 'STRING', 'enum' => ['easy', 'medium', 'hard']],
                        ],
                        'required' => ['type', 'question', 'options', 'correct_answers', 'explanation', 'difficulty'],
                        'propertyOrdering' => ['type', 'question', 'options', 'correct_answers', 'explanation', 'difficulty'],
                    ],
                ],
            ],
            'required' => ['title', 'description', 'questions'],
            'propertyOrdering' => ['title', 'description', 'questions'],
        ];
    }
}
