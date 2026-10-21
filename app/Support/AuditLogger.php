<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AuditLogger
{
    /** Records who did what. Never put lyrics text, passwords or other sensitive content in $meta. */
    public static function record(string $action, ?Model $subject = null, array $meta = [], ?User $actor = null): AuditLog
    {
        $actor ??= auth()->user();

        return AuditLog::create([
            'user_id' => $actor?->id,
            'action' => $action,
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id' => $subject?->getKey(),
            'subject_label' => $subject ? Str::limit((string) ($subject->title ?? $subject->name ?? ''), 150, '') : null,
            'meta' => $meta ?: null,
        ]);
    }
}
