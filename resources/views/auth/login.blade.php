<x-layouts.app title="Log in" robots="noindex,nofollow">
    <div class="mx-auto max-w-sm rounded-xl border border-line p-5 sm:p-8">
        <h1>Log in</h1>
        <form method="POST" action="{{ route('login') }}" class="mt-4 space-y-4">
            @csrf
            <x-field name="email" label="Email" type="email" autocomplete="username" required autofocus />
            <x-field name="password" label="Password" type="password" autocomplete="current-password" required />
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remember" value="1" class="size-5"> Remember me</label>
            <x-button type="submit" class="w-full">Log in</x-button>
        </form>
        <p class="mt-4 text-sm"><a href="{{ route('password.request') }}" class="text-accent-600 underline">Forgot your password?</a></p>
        @if (config('rwandanlyrics.registration_enabled'))
            <p class="mt-2 text-sm">No account yet? <a href="{{ route('register') }}" class="text-accent-600 underline">Create one</a></p>
        @endif
    </div>
</x-layouts.app>
