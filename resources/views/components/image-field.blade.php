@props(['name', 'label', 'media' => null])
<fieldset class="space-y-3">
    <legend class="text-sm font-medium">{{ $label }}</legend>
    @if ($media)
        <div class="flex items-start gap-3">
            <img src="{{ $media->url() }}" alt="{{ $media->alt_text }}" width="80" height="80" class="size-20 rounded-md border border-line object-cover">
            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="remove_{{ $name }}" value="0">
                <input type="checkbox" name="remove_{{ $name }}" value="1" class="size-4"> Remove this image
            </label>
        </div>
    @endif
    <x-field :name="$name" :label="$media ? 'Replace with a new file' : 'Choose a file'" type="file" accept="image/jpeg,image/png,image/webp" />
    <p class="text-sm text-muted">JPG, PNG or WebP, up to 2 MB, at least 200 by 200 pixels.</p>
    <x-field :name="$name.'_alt'" label="Alt text (describe the image for people who cannot see it)" :value="$media?->alt_text" />
</fieldset>
