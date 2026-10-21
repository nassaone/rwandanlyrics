@props(['name', 'label', 'value' => null, 'rows' => 4])
<div>
    <label for="{{ $name }}" class="block text-sm font-medium">{{ $label }}</label>
    <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}"
        @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror
        {{ $attributes->class('mt-1 block w-full rounded-md border border-line px-3 py-2 text-base') }}>{{ old($name, $value) }}</textarea>
    @error($name)<p id="{{ $name }}-error" class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
</div>
