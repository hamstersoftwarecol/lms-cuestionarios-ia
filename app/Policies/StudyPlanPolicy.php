<?php

namespace App\Policies;

use App\Models\StudyPlan;
use App\Models\User;

class StudyPlanPolicy
{
    public function view(User $user, StudyPlan $plan): bool
    {
        return $plan->user_id === $user->id;
    }

    public function update(User $user, StudyPlan $plan): bool
    {
        return $plan->user_id === $user->id;
    }

    public function delete(User $user, StudyPlan $plan): bool
    {
        return $plan->user_id === $user->id;
    }
}
