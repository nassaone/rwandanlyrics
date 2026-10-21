<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Usage: EnsureRole::class.':editor' — allows the given role and anything above it. */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string $minimum): Response
    {
        $user = $request->user();

        abort_unless($user && $user->atLeast(Role::from($minimum)), 403);

        return $next($request);
    }
}
