<x-layouts.app title="Your account" robots="noindex,nofollow">
    <div class="mx-auto max-w-lg">
        <h1 class="text-2xl font-semibold">Your account</h1>
        <p class="mt-1 text-sm text-muted">Role: {{ auth()->user()->role->label() }}</p>

        <section class="mt-6" aria-labelledby="profile">
            <h2 id="profile" class="text-lg font-semibold">Profile</h2>
            <form method="POST" action="{{ route('account.update') }}" class="mt-3 space-y-4">
                @csrf @method('PATCH')
                <x-field name="name" label="Name" :value="auth()->user()->name" autocomplete="name" required />
                <x-field name="email" label="Email" type="email" :value="auth()->user()->email" autocomplete="username" required />
                <x-button type="submit">Save profile</x-button>
            </form>
        </section>

        <section class="mt-10" aria-labelledby="password-heading">
            <h2 id="password-heading" class="text-lg font-semibold">Change password</h2>
            <form method="POST" action="{{ route('account.password') }}" class="mt-3 space-y-4">
                @csrf @method('PUT')
                <x-field name="current_password" label="Current password" type="password" autocomplete="current-password" required />
                <x-field name="password" label="New password (at least 8 characters)" type="password" autocomplete="new-password" required />
                <x-field name="password_confirmation" label="Confirm new password" type="password" autocomplete="new-password" required />
                <x-button type="submit">Change password</x-button>
            </form>
        </section>

        <section class="mt-10" aria-labelledby="delete-heading">
            <h2 id="delete-heading" class="text-lg font-semibold">Delete account</h2>
            <p class="mt-1 text-sm text-muted">This permanently removes your account. Content you contributed stays on the site without your name attached.</p>
            <form method="POST" action="{{ route('account.destroy') }}" class="mt-3 space-y-4" onsubmit="return confirm('Delete your account permanently?')">
                @csrf @method('DELETE')
                <x-field name="delete_password" label="Confirm with your password" type="password" autocomplete="current-password" required />
                <x-button type="submit" variant="secondary" class="text-red-700">Delete my account</x-button>
            </form>
        </section>
    </div>
</x-layouts.app>
