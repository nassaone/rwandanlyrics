<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);

        $q = trim((string) $request->query('q'));

        $users = User::query()
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('name', 'like', '%'.$q.'%')->orWhere('email', 'like', '%'.$q.'%')))
            ->when(Role::tryFrom((string) $request->query('role')), fn ($query, $role) => $query->where('role', $role->value))
            ->orderBy('name')->paginate(30)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function edit(Request $request, User $user): View
    {
        Gate::authorize('viewAny', User::class);

        $assignable = collect(Role::cases())
            ->filter(fn (Role $role) => $request->user()->can('assignRole', [$user, $role]))
            ->mapWithKeys(fn (Role $role) => [$role->value => $role->label()]);

        return view('admin.users.edit', compact('user', 'assignable'));
    }

    public function updateRole(UpdateUserRoleRequest $request, User $user): RedirectResponse
    {
        $role = Role::from($request->validated('role'));
        Gate::authorize('assignRole', [$user, $role]);

        if ($user->role === $role) {
            return back()->with('status', 'No change: the user already has that role.');
        }

        $old = $user->role->value;
        $user->forceFill(['role' => $role])->save();
        AuditLogger::record('user.role_changed', $user, ['from' => $old, 'to' => $role->value]);

        return back()->with('status', 'Role updated.');
    }

    public function suspend(User $user): RedirectResponse
    {
        Gate::authorize('suspend', $user);

        $user->forceFill(['suspended_at' => now(), 'remember_token' => null])->save();

        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        }

        AuditLogger::record('user.suspended', $user);

        return back()->with('status', 'Account suspended. The user has been signed out.');
    }

    public function unsuspend(User $user): RedirectResponse
    {
        Gate::authorize('suspend', $user);

        $user->forceFill(['suspended_at' => null])->save();
        AuditLogger::record('user.unsuspended', $user);

        return back()->with('status', 'Account restored.');
    }

    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);

        AuditLogger::record('user.deleted', $user, ['role' => $user->role->value]);
        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'User deleted.');
    }
}
