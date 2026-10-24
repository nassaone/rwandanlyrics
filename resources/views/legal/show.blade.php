<x-layouts.app :title="$title" :description="$description">
    <x-breadcrumbs :items="['Home' => route('home'), $title => null]" />
    <h1>{{ $title }}</h1>
    <div class="policy mt-4 max-w-3xl">{!! $body !!}</div>
</x-layouts.app>
