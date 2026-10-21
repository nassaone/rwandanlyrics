<x-admin-layout title="Manage user">
    <h1 class="text-2xl font-semibold">{{ $user->name }}</h1>
    <p class="text-sm text-muted">{{ $user->email }}</p>

    @if (session('status'))
        <x-alert type="success" class="mt-4">{{ session('status') }}</x-alert>
    @endif
    @if ($errors->any())
        <x-alert type="error" class="mt-4">{{ $errors->first() }}</x-alert>
    @endif

    <form method="POST" action="{{ route('admin.users.role', $user) }}" class="mt-6 space-y-4">
        @csrf @method('PUT')
        <h2 class="text-lg font-semibold">Role</h2>
        <x-select name="role" label="Role" :options="$assignable" :selected="$user->role->value" required />
        <x-button type="submit">Save role</x-button>
    </form>

    @can('suspend', $user)
        <div class="mt-10 border-t border-line pt-6">
            <h2 class="text-lg font-semibold">Access</h2>
            @if ($user->suspended_at)
                <p class="mt-1 text-sm text-muted">Suspended on {{ $user->suspended_at->timezone(\App\Support\AdminDate::timezone())->format('j M Y H:i') }}.</p>
                <form method="POST" action="{{ route('admin.users.unsuspend', $user) }}" class="mt-3">
                    @csrf
                    <x-button type="submit" variant="secondary">Restore access</x-button>
                </form>
            @else
                <p class="mt-1 text-sm text-muted">Suspending signs the user out everywhere and blocks sign-in.</p>
                <form method="POST" action="{{ route('admin.users.suspend', $user) }}" class="mt-3" onsubmit="return confirm('Suspend this user?')">
                    @csrf
                    <x-button type="submit" variant="secondary" class="text-red-700">Suspend user</x-button>
                </form>
            @endif
        </div>
    @endcan

    @can('delete', $user)
        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="mt-10 border-t border-line pt-6"
              onsubmit="return confirm('Delete this user permanently?')">
            @csrf @method('DELETE')
            <x-button type="submit" variant="secondary" class="text-red-700">Delete user</x-button>
        </form>
    @endcan
</x-admin-layout>
