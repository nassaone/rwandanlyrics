<x-layouts.app title="Choose a new password" robots="noindex,nofollow">
    <div class="mx-auto max-w-sm rounded-xl border border-line p-5 sm:p-8">
        <h1>Choose a new password</h1>
        <form method="POST" action="{{ route('password.store') }}" class="mt-4 space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <x-field name="email" label="Email" type="email" :value="$email" autocomplete="username" required />
            <x-field name="password" label="New password (at least 8 characters)" type="password" autocomplete="new-password" required autofocus />
            <x-field name="password_confirmation" label="Confirm new password" type="password" autocomplete="new-password" required />
            <x-button type="submit" class="w-full">Reset password</x-button>
        </form>
    </div>
</x-layouts.app>
