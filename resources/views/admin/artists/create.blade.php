<x-admin-layout title="New artist">
    <h1 class="text-2xl font-semibold">New artist</h1>
    <div class="mt-4">@include('admin.artists._form', ['action' => route('admin.artists.store')])</div>
</x-admin-layout>
