@props(['type' => 'button', 'variant' => 'primary'])
<button type="{{ $type }}" {{ $attributes->class([
    'inline-flex items-center justify-center rounded-md px-4 py-2 text-sm font-medium min-h-11',
    'bg-accent-600 text-on-accent hover:bg-accent-700' => $variant === 'primary',
    'border border-line bg-page hover:bg-surface' => $variant === 'secondary',
]) }}>{{ $slot }}</button>
