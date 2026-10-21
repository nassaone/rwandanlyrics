<x-admin-layout title="New album">
    <h1 class="text-2xl font-semibold">New album</h1>
    <div class="mt-4">@include('admin.albums._form', ['action' => route('admin.albums.store')])</div>
</x-admin-layout>
