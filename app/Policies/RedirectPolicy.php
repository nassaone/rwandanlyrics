<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Redirect;
use App\Models\User;

class RedirectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->atLeast(Role::Editor);
    }

    public function create(User $user): bool
    {
        return $user->atLeast(Role::Editor);
    }

    public function delete(User $user, Redirect $redirect): bool
    {
        return $user->atLeast(Role::Admin);
    }
}
