@props(['name', 'label', 'options', 'selected' => null, 'multiple' => false, 'placeholder' => null])
@php($current = collect(old($name, $selected))->map(fn ($v) => (string) $v)->all())
<div>
    <label for="{{ $name }}" class="block text-sm font-medium">{{ $label }}</label>
    <select id="{{ $name }}" name="{{ $name }}{{ $multiple ? '[]' : '' }}" @if ($multiple) multiple size="6" @endif
        @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror
        {{ $attributes->class('mt-1 block w-full rounded-md border border-line bg-page px-3 py-2 text-base') }}>
        @if ($placeholder !== null)<option value="">{{ $placeholder }}</option>@endif
        @foreach ($options as $value => $text)
            <option value="{{ $value }}" @selected(in_array((string) $value, $current, true))>{{ $text }}</option>
        @endforeach
    </select>
    @error($name)<p id="{{ $name }}-error" class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
</div>
