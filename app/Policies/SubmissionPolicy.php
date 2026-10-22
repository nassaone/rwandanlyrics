<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Submission;
use App\Models\User;

class SubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->atLeast(Role::Contributor);
    }

    public function create(User $user): bool
    {
        return $user->atLeast(Role::Contributor);
    }

    /** Owners see their own submissions; editors and above see all. */
    public function view(User $user, Submission $submission): bool
    {
        return $user->id === $submission->user_id || $user->atLeast(Role::Editor);
    }

    public function review(User $user, Submission $submission): bool
    {
        return $user->atLeast(Role::Editor);
    }
}
