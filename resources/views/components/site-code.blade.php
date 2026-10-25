@props(['position'])
@php
    $s = app(\App\Services\SettingsService::class);
    $on = $s->bool('ads_enabled') && ! request()->routeIs('admin.*');
@endphp
@if ($on)
    @if ($position === 'head')
        @if ($ga = $s->value('ga4_id'))
            <script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga }}"></script>
            <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments)}gtag('js',new Date());gtag('config','{{ $ga }}');</script>
        @endif
        @if ($pl = $s->value('plausible_domain'))
            <script defer data-domain="{{ $pl }}" src="https://plausible.io/js/script.js"></script>
        @endif
        {!! $s->value('head_code') !!}
    @elseif ($position === 'body_start')
        {!! $s->value('body_start_code') !!}
    @elseif ($position === 'body_end')
        {!! $s->value('body_end_code') !!}
    @endif
@endif
