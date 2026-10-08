<?php

namespace Database\Factories;

use App\Models\StudyGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudyGroup>
 */
class StudyGroupFactory extends Factory
{
    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'name' => 'Grupo '.fake()->words(2, true),
            'description' => fake()->sentence(),
            'max_members' => 50,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (StudyGroup $group) {
            $group->members()->syncWithoutDetaching([$group->owner_id => ['role' => 'owner', 'joined_at' => now()]]);
        });
    }
}
