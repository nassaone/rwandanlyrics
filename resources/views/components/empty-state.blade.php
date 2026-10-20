@props(['title', 'message' => null])
<div class="rounded-md border border-dashed border-line p-8 text-center">
    <p class="font-medium">{{ $title }}</p>
    @if ($message)<p class="mt-1 text-sm text-muted">{{ $message }}</p>@endif
    {{ $slot }}
</div>
