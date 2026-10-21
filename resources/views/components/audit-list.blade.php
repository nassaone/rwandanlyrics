@props(['logs'])
@if ($logs->isEmpty())
    <x-empty-state title="No activity recorded yet" />
@else
    <ul class="divide-y divide-line rounded-md border border-line">
        @foreach ($logs as $log)
            <li class="p-3 text-sm">
                <p><span class="font-medium">{{ $log->action }}</span>
                    @if ($log->subject_label) &middot; {{ $log->subject_label }} @endif</p>
                <p class="text-muted">{{ $log->actor?->name ?? 'System' }} &middot; {{ \App\Support\AdminDate::display($log->created_at) }}
                    @if ($log->meta) &middot; {{ json_encode($log->meta) }} @endif</p>
            </li>
        @endforeach
    </ul>
@endif
