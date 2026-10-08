<?php

namespace App\Policies;

use App\Models\StudyTask;
use App\Models\User;

class StudyTaskPolicy
{
    public function update(User $user, StudyTask $task): bool
    {
        return $task->user_id === $user->id;
    }
}
