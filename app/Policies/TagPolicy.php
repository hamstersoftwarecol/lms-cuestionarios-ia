<?php

namespace App\Policies;

use App\Models\Tag;
use App\Models\User;

class TagPolicy
{
    public function delete(User $user, Tag $tag): bool
    {
        return $tag->user_id === $user->id;
    }
}
