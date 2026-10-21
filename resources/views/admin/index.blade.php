<x-layouts.app title="Admin" robots="noindex,nofollow">
    <h1 class="text-2xl font-semibold">Admin</h1>
    <p class="mt-1 text-muted">Signed in as {{ auth()->user()->name }} ({{ auth()->user()->role->label() }}).</p>
    <x-alert class="mt-4">Content management tools are not available yet.</x-alert>
</x-layouts.app>
