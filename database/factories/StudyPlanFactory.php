<?php

namespace Database\Factories;

use App\Models\StudyPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudyPlan>
 */
class StudyPlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => 'Examen de '.fake()->word(),
            'start_date' => today(),
            'exam_date' => today()->addDays(10),
            'daily_minutes' => 60,
            'topics' => ['Tema 1', 'Tema 2', 'Tema 3'],
            'status' => 'active',
        ];
    }
}
