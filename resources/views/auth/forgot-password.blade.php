<x-layouts.app title="Reset your password" robots="noindex,nofollow">
    <div class="mx-auto max-w-sm rounded-xl border border-line p-5 sm:p-8">
        <h1>Reset your password</h1>
        <p class="mt-1 text-sm text-muted">Enter your email and we will send you a reset link.</p>
        <form method="POST" action="{{ route('password.email') }}" class="mt-4 space-y-4">
            @csrf
            <x-field name="email" label="Email" type="email" autocomplete="username" required autofocus />
            <x-button type="submit" class="w-full">Send reset link</x-button>
        </form>
    </div>
</x-layouts.app>
