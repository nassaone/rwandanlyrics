<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

class GenrePolicy extends ContentPolicy
{
    public function create(User $user): bool
    {
        return $user->atLeast(Role::Editor);
    }
}
