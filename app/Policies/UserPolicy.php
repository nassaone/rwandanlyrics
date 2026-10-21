<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->atLeast(Role::Admin);
    }

    /** Nobody changes their own role; admins can only manage users below them and grant roles below their own. */
    public function assignRole(User $actor, User $target, Role $newRole): bool
    {
        if ($actor->is($target) || ! $actor->atLeast(Role::Admin)) {
            return false;
        }

        if ($actor->role === Role::SuperAdmin) {
            return true;
        }

        return $actor->role->level() > $target->role->level()
            && $actor->role->level() > $newRole->level();
    }

    public function suspend(User $actor, User $target): bool
    {
        return ! $actor->is($target)
            && $actor->atLeast(Role::Admin)
            && $actor->role->level() > $target->role->level();
    }

    public function delete(User $actor, User $target): bool
    {
        return ! $actor->is($target) && $actor->role === Role::SuperAdmin;
    }
}
