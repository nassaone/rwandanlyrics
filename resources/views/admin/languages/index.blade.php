<x-admin-layout title="Languages">
    <h1 class="text-2xl font-semibold">Languages</h1>
    <form method="POST" action="{{ route('admin.languages.store') }}" class="mt-4 space-y-4 rounded-md border border-line p-4">
        @csrf
        <h2 class="font-semibold">Add a language</h2>
        <x-field name="name" label="Name" required />
        <x-field name="code" label="Code (optional, e.g. rw)" maxlength="10" />
        <x-button type="submit">Add language</x-button>
    </form>
    <ul class="mt-6 divide-y divide-line rounded-md border border-line">
        @forelse ($languages as $language)
            <li class="flex items-center justify-between gap-2 p-3">
                <a href="{{ route('admin.languages.edit', $language) }}" class="font-medium hover:text-accent-600">{{ $language->name }}</a>
                <span class="text-sm text-muted">{{ $language->code }}</span>
            </li>
        @empty
            <li class="p-3 text-muted">No languages yet.</li>
        @endforelse
    </ul>
    <div class="mt-6">{{ $languages->links() }}</div>
</x-admin-layout>
