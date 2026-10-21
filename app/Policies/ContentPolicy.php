<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** Shared rules for catalog content. Contributors submit; editors publish; admins delete. */
abstract class ContentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->atLeast(Role::Contributor);
    }

    public function view(User $user, Model $model): bool
    {
        return $user->atLeast(Role::Contributor);
    }

    public function create(User $user): bool
    {
        return $user->atLeast(Role::Contributor);
    }

    public function update(User $user, Model $model): bool
    {
        return $user->atLeast(Role::Editor);
    }

    public function publish(User $user, Model $model): bool
    {
        return $user->atLeast(Role::Editor);
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->atLeast(Role::Admin);
    }
}
