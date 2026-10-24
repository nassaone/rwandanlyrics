@props(['media' => null, 'name' => '', 'shape' => 'circle', 'size' => 'size-10'])
@php $round = $shape === 'circle' ? 'rounded-full' : 'rounded-lg'; @endphp
@if ($media)
    <img src="{{ $media->url() }}" alt="" loading="lazy" decoding="async"
         @if ($media->width && $media->height) width="{{ $media->width }}" height="{{ $media->height }}" @endif
         class="{{ $size }} {{ $round }} shrink-0 bg-surface object-cover">
@else
    <span aria-hidden="true" class="{{ $size }} {{ $round }} inline-flex shrink-0 items-center justify-center bg-accent-100 font-semibold text-accent-700">{{ mb_strtoupper(mb_substr(trim($name), 0, 1)) }}</span>
@endif
