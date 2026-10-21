<x-admin-layout title="Activity log">
    <h1 class="text-2xl font-semibold">Activity log</h1>
    <div class="mt-4"><x-audit-list :logs="$logs" /></div>
    <div class="mt-6">{{ $logs->links() }}</div>
</x-admin-layout>
