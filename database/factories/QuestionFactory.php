<?php

namespace Database\Factories;

use App\Enums\Difficulty;
use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'type' => QuestionType::Mcq,
            'question_text' => fake()->sentence().'?',
            'options' => ['Opción A', 'Opción B', 'Opción C', 'Opción D'],
            'correct_answers' => [0],
            'explanation' => fake()->sentence(),
            'difficulty' => Difficulty::Medium,
            'position' => 0,
        ];
    }

    public function sata(array $correct = [0, 2]): static
    {
        return $this->state(fn () => [
            'type' => QuestionType::Sata,
            'options' => ['Opción A', 'Opción B', 'Opción C', 'Opción D', 'Opción E'],
            'correct_answers' => $correct,
        ]);
    }
}
