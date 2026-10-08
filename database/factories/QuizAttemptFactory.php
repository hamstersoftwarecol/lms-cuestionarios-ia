<?php

namespace Database\Factories;

use App\Enums\AssessmentType;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizAttempt>
 */
class QuizAttemptFactory extends Factory
{
    public function definition(): array
    {
        $total = 10;
        $score = fake()->numberBetween(3, 10);

        return [
            'user_id' => User::factory(),
            'quiz_id' => Quiz::factory(),
            'assessment_type' => AssessmentType::Practice,
            'confidence_before' => fake()->numberBetween(1, 5),
            'score' => $score,
            'total_questions' => $total,
            'percentage' => $score / $total * 100,
            'points_earned' => $score * 15,
            'time_spent' => fake()->numberBetween(60, 900),
            'started_at' => now()->subMinutes(15),
            'completed_at' => now(),
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn () => [
            'score' => 0,
            'percentage' => 0,
            'points_earned' => 0,
            'completed_at' => null,
        ]);
    }
}
