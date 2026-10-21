<x-admin-layout title="Users">
    <h1 class="text-2xl font-semibold">Users</h1>

    @if (session('status'))
        <x-alert type="success" class="mt-4">{{ session('status') }}</x-alert>
    @endif

    <form method="GET" action="{{ route('admin.users.index') }}" class="mt-6 flex flex-wrap gap-2" role="search">
        <label for="q" class="sr-only">Search name or email</label>
        <input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="Name or email" class="min-w-0 flex-1 rounded-md border border-line px-3 py-2 text-base">
        <label for="role" class="sr-only">Role</label>
        <select id="role" name="role" class="rounded-md border border-line px-3 py-2 text-base">
            <option value="">All roles</option>
            @foreach (\App\Enums\Role::cases() as $role)
                <option value="{{ $role->value }}" @selected(request('role') === $role->value)>{{ $role->label() }}</option>
            @endforeach
        </select>
        <x-button type="submit" variant="secondary">Filter</x-button>
    </form>

    <ul class="mt-6 divide-y divide-line rounded-md border border-line text-sm">
        @foreach ($users as $user)
            <li class="flex items-center justify-between gap-3 p-3">
                <div class="min-w-0">
                    <p class="truncate font-medium">{{ $user->name }}</p>
                    <p class="truncate text-muted">{{ $user->email }}</p>
                    <p class="mt-1 flex flex-wrap gap-2">
                        <x-badge>{{ $user->role->label() }}</x-badge>
                        @if ($user->suspended_at) <x-badge class="bg-red-50 text-red-800">Suspended</x-badge> @endif
                    </p>
                </div>
                <a class="text-accent-600 underline" href="{{ route('admin.users.edit', $user) }}">Manage</a>
            </li>
        @endforeach
    </ul>
    <div class="mt-6">{{ $users->links() }}</div>
</x-admin-layout>
