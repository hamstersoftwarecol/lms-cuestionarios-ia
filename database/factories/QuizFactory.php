<?php

namespace Database\Factories;

use App\Enums\Difficulty;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quiz>
 */
class QuizFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => 'Cuestionario: '.fake()->words(3, true),
            'description' => fake()->sentence(),
            'difficulty' => Difficulty::Mixed,
            'language' => 'es',
            'is_public' => false,
            'generated_by_ai' => true,
        ];
    }

    public function withQuestions(int $count = 3): static
    {
        return $this->afterCreating(function (Quiz $quiz) use ($count) {
            Question::factory()->count($count)->sequence(fn ($sequence) => ['position' => $sequence->index])->for($quiz)->create();
        });
    }
}
