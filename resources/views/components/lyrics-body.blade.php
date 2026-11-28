@props(['text'])
@php
    // Turns "[Verse 1]" lines into section labels and blank lines into stanza breaks.
    $stanzas = [];
    $current = ['label' => null, 'lines' => []];
    $flush = function () use (&$stanzas, &$current) {
        if ($current['label'] !== null || $current['lines']) {
            $stanzas[] = $current;
        }
        $current = ['label' => null, 'lines' => []];
    };
    foreach (preg_split('/\R/u', trim((string) $text)) as $line) {
        $line = rtrim($line);
        if (trim($line) === '') {
            $flush();

            continue;
        }
        if (preg_match('/^\s*\[(.+?)\]\s*$/u', $line, $m)) {
            $flush();
            $current['label'] = trim($m[1]);

            continue;
        }
        $current['lines'][] = $line;
    }
    $flush();
@endphp
<div {{ $attributes->class('lyrics') }}>
    @foreach ($stanzas as $stanza)
        <div class="mt-7 first:mt-0">
            @if ($stanza['label'])
                <p class="mb-2 font-sans text-sm font-semibold text-accent-600">{{ $stanza['label'] }}</p>
            @endif
            @foreach ($stanza['lines'] as $line)
                <p>{{ $line }}</p>
            @endforeach
        </div>
    @endforeach
</div>
