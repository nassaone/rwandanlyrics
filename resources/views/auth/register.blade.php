<x-layouts.app title="Create an account" robots="noindex,nofollow">
    <div class="mx-auto max-w-sm rounded-xl border border-line p-5 sm:p-8">
        <h1>Create an account</h1>
        <form method="POST" action="{{ route('register') }}" class="mt-4 space-y-4">
            @csrf
            <x-field name="name" label="Name" autocomplete="name" required autofocus />
            <x-field name="email" label="Email" type="email" autocomplete="username" required />
            <x-field name="password" label="Password (at least 8 characters)" type="password" autocomplete="new-password" required />
            <x-field name="password_confirmation" label="Confirm password" type="password" autocomplete="new-password" required />
            <x-button type="submit" class="w-full">Register</x-button>
        </form>
        <p class="mt-4 text-sm">Already registered? <a href="{{ route('login') }}" class="text-accent-600 underline">Log in</a></p>
    </div>
</x-layouts.app>
