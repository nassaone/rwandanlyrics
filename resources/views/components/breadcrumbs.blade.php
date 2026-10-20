@props(['items'])
<nav aria-label="Breadcrumb" class="mb-4 text-sm text-muted">
    <ol class="flex flex-wrap gap-1">
        @foreach ($items as $label => $url)
            <li class="flex gap-1">
                @if ($url)
                    <a href="{{ $url }}" class="hover:text-accent-600">{{ $label }}</a>
                @else
                    <span aria-current="page">{{ $label }}</span>
                @endif
                @unless ($loop->last)<span aria-hidden="true">/</span>@endunless
            </li>
        @endforeach
    </ol>
</nav>
