@php
    $locked = $album->exists && $album->published_at !== null;
    $selectedArtists = $album->exists ? $album->artists->pluck('id')->all() : [];
@endphp
<form method="POST" action="{{ $action }}" class="space-y-8">
    @csrf
    @if ($album->exists) @method('PUT') @endif

    <section class="space-y-4" aria-labelledby="details">
        <h2 id="details" class="text-lg font-semibold">Details</h2>
        <x-field name="title" label="Title" :value="$album->title" required />
        <x-field name="slug" label="URL slug (leave blank to generate)" :value="$album->slug" :readonly="$locked" />
        @if ($locked)
            <p class="text-sm text-muted">The slug is locked because this album has been published.</p>
        @endif
        <x-select name="artist_ids" label="Artists" :options="$artists" :selected="$selectedArtists" :multiple="true" required />
        <x-select name="type" label="Type" :options="\App\Support\EnumOptions::for(\App\Enums\AlbumType::class)" :selected="$album->type?->value ?? 'album'" required />
        <x-field name="release_date" type="date" label="Release date" :value="$album->release_date?->format('Y-m-d')" />
        <x-textarea name="description" label="Description" rows="5" :value="$album->description" />
    </section>

    <section class="space-y-4" aria-labelledby="publishing">
        <h2 id="publishing" class="text-lg font-semibold">Publishing</h2>
        <x-select name="status" label="Status" :options="\App\Support\EnumOptions::for(\App\Enums\PublicationStatus::class)" :selected="$album->status?->value ?? 'draft'" required />
        <x-field name="published_at" type="datetime-local" label="Publication date and time ({{ \App\Support\AdminDate::timezone() }})" :value="\App\Support\AdminDate::toInput($album->published_at)" />
    </section>

    <section class="space-y-4" aria-labelledby="seo">
        <h2 id="seo" class="text-lg font-semibold">Search engines</h2>
        <x-field name="seo_title" label="SEO title (optional)" :value="$album->seo_title" />
        <x-textarea name="seo_description" label="Meta description (optional, max 320)" rows="2" :value="$album->seo_description" />
        <label class="flex items-center gap-2 text-sm">
            <input type="hidden" name="noindex" value="0">
            <input type="checkbox" name="noindex" value="1" class="size-4" @checked(old('noindex', $album->noindex))> Ask search engines not to index this page
        </label>
    </section>

    <x-button type="submit">Save album</x-button>
</form>
