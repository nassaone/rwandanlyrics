<?php

namespace App\Support;

use App\Models\Redirect;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class Redirects
{
    /** Redirect $from to $to (internal paths). Keeps redirects one hop long and loop-free. */
    public static function record(string $from, string $to, ?User $actor = null): ?Redirect
    {
        if ($from === $to) {
            return null;
        }

        return DB::transaction(function () use ($from, $to, $actor) {
            // The target is live again, so it must not redirect anywhere.
            Redirect::where('from_path', $to)->delete();
            // Anything that pointed at the old URL now points straight at the new one.
            Redirect::where('to_path', $from)->update(['to_path' => $to]);

            $redirect = Redirect::updateOrCreate(
                ['from_path' => $from],
                ['to_path' => $to, 'status_code' => 301, 'created_by' => $actor?->id]
            );

            AuditLogger::record('redirect.created', $redirect, ['from' => $from, 'to' => $to], $actor);

            return $redirect;
        });
    }
}
