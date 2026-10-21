<?php

namespace App\Models;

use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /** `role` and `suspended_at` are deliberately NOT mass-assignable (prevents privilege escalation). */
    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected $attributes = ['role' => 'user'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'suspended_at' => 'datetime',
        ];
    }

    public function atLeast(Role $role): bool
    {
        return $this->role->atLeast($role);
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }
}
