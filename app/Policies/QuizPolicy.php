<?php

namespace App\Policies;

use App\Models\Quiz;
use App\Models\User;

class QuizPolicy
{
    public function view(User $user, Quiz $quiz): bool
    {
        return $quiz->isAccessibleBy($user);
    }

    public function update(User $user, Quiz $quiz): bool
    {
        return $quiz->user_id === $user->id;
    }

    public function delete(User $user, Quiz $quiz): bool
    {
        return $quiz->user_id === $user->id || $user->isAdmin();
    }
}
