@props(['name'])
@php
    $s = app(\App\Services\SettingsService::class);
    $code = $s->bool('ads_enabled') && ! request()->routeIs('admin.*') ? $s->value('ad_'.$name) : null;
@endphp
@if ($code)
    <aside class="my-6 flex justify-center overflow-hidden" aria-label="Advertisement" data-ad-slot="{{ $name }}">{!! $code !!}</aside>
@endif
