<?php

namespace Database\Seeders;

use App\Models\Badge;
use Illuminate\Database\Seeder;

/**
 * Las 9 insignias de logros del motor de gamificación.
 */
class BadgeSeeder extends Seeder
{
    public const BADGES = [
        ['slug' => 'first-quiz', 'name' => 'Primer paso', 'description' => 'Completa tu primer cuestionario.', 'icon' => '🚀', 'color' => 'sky', 'criteria_type' => 'quizzes_completed', 'criteria_value' => 1],
        ['slug' => 'perfect-score', 'name' => 'Perfección', 'description' => 'Obtén un 100 % en un cuestionario.', 'icon' => '💯', 'color' => 'emerald', 'criteria_type' => 'perfect_scores', 'criteria_value' => 1],
        ['slug' => 'streak-3', 'name' => 'En racha', 'description' => 'Estudia 3 días seguidos.', 'icon' => '🔥', 'color' => 'amber', 'criteria_type' => 'streak', 'criteria_value' => 3],
        ['slug' => 'streak-7', 'name' => 'Imparable', 'description' => 'Mantén una racha de 7 días.', 'icon' => '⚡', 'color' => 'orange', 'criteria_type' => 'streak', 'criteria_value' => 7],
        ['slug' => 'streak-30', 'name' => 'Leyenda', 'description' => 'Mantén una racha de 30 días.', 'icon' => '👑', 'color' => 'violet', 'criteria_type' => 'streak', 'criteria_value' => 30],
        ['slug' => 'quiz-master', 'name' => 'Maestro de cuestionarios', 'description' => 'Completa 10 cuestionarios.', 'icon' => '🧠', 'color' => 'indigo', 'criteria_type' => 'quizzes_completed', 'criteria_value' => 10],
        ['slug' => 'librarian', 'name' => 'Bibliotecario', 'description' => 'Sube o crea 5 documentos de estudio.', 'icon' => '📚', 'color' => 'teal', 'criteria_type' => 'notes_created', 'criteria_value' => 5],
        ['slug' => 'team-player', 'name' => 'Aprendiz social', 'description' => 'Crea o únete a un grupo de estudio.', 'icon' => '🤝', 'color' => 'pink', 'criteria_type' => 'groups_joined', 'criteria_value' => 1],
        ['slug' => 'planner-pro', 'name' => 'Planificador experto', 'description' => 'Completa 10 tareas del planificador de estudio.', 'icon' => '🗓️', 'color' => 'rose', 'criteria_type' => 'tasks_completed', 'criteria_value' => 10],
    ];

    public function run(): void
    {
        foreach (self::BADGES as $badge) {
            Badge::updateOrCreate(['slug' => $badge['slug']], $badge);
        }
    }
}
