@props(['media' => null])
@if ($media)
    <img src="{{ $media->url() }}" alt="{{ $media->alt_text }}"
         @if ($media->width && $media->height) width="{{ $media->width }}" height="{{ $media->height }}" @endif
         decoding="async" {{ $attributes->class('h-auto max-w-full') }}>
@endif
