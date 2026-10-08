<?php

namespace App\Policies;

use App\Models\StudyGroup;
use App\Models\User;

class StudyGroupPolicy
{
    public function view(User $user, StudyGroup $group): bool
    {
        return $group->hasMember($user) || $user->isAdmin();
    }

    /** Compartir cuestionarios: cualquier miembro. */
    public function contribute(User $user, StudyGroup $group): bool
    {
        return $group->hasMember($user);
    }

    /** Editar, regenerar el código o expulsar miembros: solo el propietario. */
    public function manage(User $user, StudyGroup $group): bool
    {
        return $group->isOwner($user);
    }

    public function delete(User $user, StudyGroup $group): bool
    {
        return $group->isOwner($user) || $user->isAdmin();
    }
}
