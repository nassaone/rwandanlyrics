@props(['type' => 'info'])
<div role="alert" {{ $attributes->class([
    'rounded-md border px-4 py-3 text-sm',
    'border-accent-100 bg-accent-50 text-accent-700' => $type === 'info',
    'border-red-200 bg-red-50 text-red-800' => $type === 'error',
    'border-green-200 bg-green-50 text-green-800' => $type === 'success',
]) }}>{{ $slot }}</div>
