<?php

namespace App\Enums;

enum Role: string
{
    case User = 'user';
    case Contributor = 'contributor';
    case Editor = 'editor';
    case Admin = 'admin';
    case SuperAdmin = 'super_admin';

    public function level(): int
    {
        return match ($this) {
            self::User => 1,
            self::Contributor => 2,
            self::Editor => 3,
            self::Admin => 4,
            self::SuperAdmin => 5,
        };
    }

    public function atLeast(self $role): bool
    {
        return $this->level() >= $role->level();
    }

    public function label(): string
    {
        return match ($this) {
            self::User => 'Registered user',
            self::Contributor => 'Contributor',
            self::Editor => 'Editor',
            self::Admin => 'Administrator',
            self::SuperAdmin => 'Super administrator',
        };
    }
}
