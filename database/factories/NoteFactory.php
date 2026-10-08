<?php

namespace Database\Factories;

use App\Models\Note;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Note>
 */
class NoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => ucfirst(fake()->words(3, true)),
            'content' => fake()->paragraphs(4, true),
            'source_type' => 'text',
            'extraction_status' => 'completed',
        ];
    }
}
