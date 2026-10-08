<?php

namespace Database\Seeders;

use App\Enums\AssessmentType;
use App\Enums\Difficulty;
use App\Models\Announcement;
use App\Models\Quiz;
use App\Models\StudyGroup;
use App\Models\User;
use App\Services\GamificationService;
use App\Services\StudyPlanGenerator;
use Illuminate\Database\Seeder;

/**
 * Datos de demostración: un estudiante, apuntes con cuestionario, grupo, plan y clasificación.
 * No usa la API de Gemini: las preguntas están escritas a mano.
 */
class DemoSeeder extends Seeder
{
    private const PHOTOSYNTHESIS = <<<'TEXT'
        # La fotosíntesis

        La fotosíntesis es el proceso mediante el cual las plantas, las algas y algunas bacterias transforman la energía luminosa en energía química. Ocurre principalmente en los cloroplastos, unos orgánulos que contienen clorofila, el pigmento verde que absorbe la luz.

        ## Fases

        **Fase luminosa.** Tiene lugar en los tilacoides. La luz excita la clorofila y se produce la fotólisis del agua: las moléculas de agua se rompen liberando oxígeno (O₂) a la atmósfera. La energía se almacena en forma de ATP y NADPH.

        **Fase oscura o ciclo de Calvin.** Ocurre en el estroma del cloroplasto y no necesita luz directamente. Utiliza el ATP y el NADPH de la fase luminosa para fijar el dióxido de carbono (CO₂) y producir glucosa. La enzima clave es la RuBisCO.

        ## Ecuación general

        6 CO₂ + 6 H₂O + luz → C₆H₁₂O₆ + 6 O₂

        ## Factores que influyen

        - Intensidad de la luz
        - Concentración de CO₂
        - Temperatura
        - Disponibilidad de agua

        ## Importancia

        La fotosíntesis produce el oxígeno que respiramos, es la base de casi todas las cadenas alimentarias y ayuda a regular el CO₂ atmosférico, contribuyendo a mitigar el cambio climático.
        TEXT;

    private const QUESTIONS = [
        ['type' => 'mcq', 'question_text' => '¿En qué orgánulo celular ocurre la fotosíntesis?', 'options' => ['Mitocondria', 'Cloroplasto', 'Ribosoma', 'Núcleo'], 'correct_answers' => [1], 'explanation' => 'La fotosíntesis ocurre en los cloroplastos, que contienen la clorofila. La mitocondria realiza la respiración celular.', 'difficulty' => 'easy'],
        ['type' => 'mcq', 'question_text' => '¿Qué gas se libera durante la fotólisis del agua?', 'options' => ['Dióxido de carbono', 'Nitrógeno', 'Oxígeno', 'Hidrógeno'], 'correct_answers' => [2], 'explanation' => 'Al romperse las moléculas de agua en la fase luminosa se libera oxígeno (O₂).', 'difficulty' => 'easy'],
        ['type' => 'sata', 'question_text' => 'Selecciona todos los factores que influyen en la fotosíntesis.', 'options' => ['Intensidad de la luz', 'Concentración de CO₂', 'Temperatura', 'Campo magnético terrestre', 'Disponibilidad de agua'], 'correct_answers' => [0, 1, 2, 4], 'explanation' => 'Luz, CO₂, temperatura y agua afectan a la fotosíntesis; el campo magnético no es un factor relevante.', 'difficulty' => 'medium'],
        ['type' => 'mcq', 'question_text' => '¿Dónde tiene lugar el ciclo de Calvin?', 'options' => ['En los tilacoides', 'En el estroma', 'En el citoplasma', 'En la membrana plasmática'], 'correct_answers' => [1], 'explanation' => 'El ciclo de Calvin (fase oscura) ocurre en el estroma; la fase luminosa ocurre en los tilacoides.', 'difficulty' => 'medium'],
        ['type' => 'sata', 'question_text' => '¿Qué moléculas produce la fase luminosa y utiliza el ciclo de Calvin?', 'options' => ['ATP', 'Glucosa', 'NADPH', 'RuBisCO', 'CO₂'], 'correct_answers' => [0, 2], 'explanation' => 'La fase luminosa almacena energía en ATP y NADPH, que el ciclo de Calvin usa para fijar CO₂.', 'difficulty' => 'hard'],
        ['type' => 'mcq', 'question_text' => 'Si una planta recibe más CO₂ pero la luz es muy escasa, ¿qué es lo más probable?', 'options' => ['La fotosíntesis aumenta sin límite', 'La luz se convierte en el factor limitante', 'La planta deja de producir oxígeno para siempre', 'El ciclo de Calvin se realiza en los tilacoides'], 'correct_answers' => [1], 'explanation' => 'Con poca luz, la fase luminosa no produce suficiente ATP y NADPH, por lo que la luz limita el proceso aunque haya más CO₂.', 'difficulty' => 'hard'],
    ];

    public function run(GamificationService $gamification, StudyPlanGenerator $planner): void
    {
        $admin = User::where('email', config('lms.admin.email'))->first();

        $student = User::firstOrNew(['email' => 'demo@lms.test']);
        $student->forceFill([
            'name' => 'Estudiante Demo',
            'password' => 'password',
            'email_verified_at' => now(),
            'is_active' => true,
        ])->save();

        if ($student->notes()->exists()) {
            return; // Ya sembrado.
        }

        $folder = $student->folders()->create(['name' => 'Biología', 'color' => 'emerald']);
        $tag = $student->tags()->create(['name' => 'examen', 'color' => 'rose']);

        $note = $student->notes()->create([
            'folder_id' => $folder->id,
            'title' => 'La fotosíntesis',
            'content' => self::PHOTOSYNTHESIS,
            'source_type' => 'text',
            'is_favorite' => true,
        ]);
        $note->tags()->attach($tag);

        $quiz = $student->quizzes()->create([
            'note_id' => $note->id,
            'title' => 'Fotosíntesis: conceptos clave',
            'description' => 'Evalúa las fases, la ecuación y los factores de la fotosíntesis.',
            'difficulty' => Difficulty::Mixed,
            'language' => 'es',
            'time_limit' => 10,
            'is_public' => true,
        ]);

        foreach (self::QUESTIONS as $position => $question) {
            $quiz->questions()->create($question + ['position' => $position]);
        }

        $group = new StudyGroup(['name' => 'Biología 101', 'description' => 'Grupo de repaso para el parcial de biología.']);
        $group->owner()->associate($student)->save();
        $group->members()->attach($student->id, ['role' => 'owner', 'joined_at' => now()]);
        $group->quizzes()->attach($quiz->id, ['shared_by' => $student->id]);

        // Compañeros con intentos repartidos en el tiempo para poblar las clasificaciones.
        $classmates = User::factory()->count(6)->sequence(fn ($sequence) => [
            'created_at' => now()->subDays(random_int(1, 28)),
            'last_login_at' => now()->subHours(random_int(1, 200)),
        ])->create();

        foreach ($classmates as $i => $classmate) {
            $group->members()->attach($classmate->id, ['role' => 'member', 'joined_at' => now()]);
            $this->fakeAttempts($classmate, $quiz, attempts: 6 - $i);
            $gamification->checkBadges($classmate);
        }

        $this->fakeAttempts($student, $quiz, attempts: 4, withPrePost: true);
        $student->forceFill(['current_streak' => 4, 'longest_streak' => 6, 'last_activity_date' => today()])->save();
        $gamification->checkBadges($student);

        $plan = $student->studyPlans()->create([
            'title' => 'Parcial de Biología',
            'description' => 'Plan generado automáticamente para el primer parcial.',
            'start_date' => today(),
            'exam_date' => today()->addDays(14),
            'daily_minutes' => 60,
            'topics' => ['Respiración celular', 'Genética mendeliana'],
        ]);
        $plan->notes()->attach($note);
        $planner->generate($plan);

        Announcement::create([
            'title' => '¡Bienvenido al LMS con IA!',
            'body' => 'Sube un PDF, una imagen o una foto de tus apuntes y la IA generará un cuestionario en segundos.',
            'type' => 'info',
            'is_active' => true,
        ])->author()->associate($admin)->save();
    }

    private function fakeAttempts(User $user, Quiz $quiz, int $attempts, bool $withPrePost = false): void
    {
        $questions = $quiz->questions()->get();
        $total = $questions->count();

        for ($n = 0; $n < $attempts; $n++) {
            $score = random_int((int) ceil($total / 2), $total);
            $completedAt = now()->subDays(random_int(0, 40))->subMinutes(random_int(0, 600));

            $type = match (true) {
                $withPrePost && $n === 0 => AssessmentType::Pre,
                $withPrePost && $n === $attempts - 1 => AssessmentType::Post,
                default => AssessmentType::Practice,
            };

            if ($type === AssessmentType::Pre) {
                $score = (int) floor($total / 2);
                $completedAt = now()->subDays(7);
            } elseif ($type === AssessmentType::Post) {
                $score = $total;
                $completedAt = now()->subHours(3);
            }

            $points = $score * 15 + ($score === $total ? 22 : 0);

            $attempt = $user->attempts()->create([
                'quiz_id' => $quiz->id,
                'assessment_type' => $type,
                'confidence_before' => random_int(2, 5),
                'score' => $score,
                'total_questions' => $total,
                'percentage' => round($score / $total * 100, 2),
                'points_earned' => $points,
                'time_spent' => random_int(120, 600),
                'started_at' => $completedAt->copy()->subMinutes(8),
                'completed_at' => $completedAt,
            ]);

            $user->increment('points', $points);

            // Respuestas coherentes con la nota para alimentar la analítica por dificultad y tipo.
            $correctIds = $questions->shuffle()->take($score)->pluck('id');

            foreach ($questions as $question) {
                $isCorrect = $correctIds->contains($question->id);
                $wrong = collect(array_keys($question->options))->diff($question->correct_answers)->values();

                $attempt->answers()->create([
                    'question_id' => $question->id,
                    'selected_answers' => $isCorrect ? $question->correct_answers : [$wrong->random()],
                    'is_correct' => $isCorrect,
                ]);
            }
        }
    }
}
