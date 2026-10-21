<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Command;

class SetUserRole extends Command
{
    protected $signature = 'rwandanlyrics:set-role {email} {role=super_admin}';

    protected $description = 'Set the role of an existing user (use this to create the first super administrator)';

    public function handle(): int
    {
        $role = Role::tryFrom((string) $this->argument('role'));

        if (! $role) {
            $this->error('Unknown role. Valid roles: '.implode(', ', array_column(Role::cases(), 'value')));

            return self::FAILURE;
        }

        $user = User::where('email', strtolower((string) $this->argument('email')))->first();

        if (! $user) {
            $this->error('No user with that email. Register the account first.');

            return self::FAILURE;
        }

        $user->forceFill(['role' => $role])->save();
        $this->info("{$user->email} is now {$role->value}.");

        return self::SUCCESS;
    }
}
