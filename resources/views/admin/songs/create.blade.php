<x-admin-layout title="New song">
    <h1 class="text-2xl font-semibold">New song</h1>
    <div class="mt-4">@include('admin.songs._form', ['action' => route('admin.songs.store')])</div>
</x-admin-layout>
