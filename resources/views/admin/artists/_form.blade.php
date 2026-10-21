@php
    $selectedGenres = array_map('intval', (array) old('genre_ids', $artist->exists ? $artist->genres->pluck('id')->all() : []));
    $aliasText = $artist->exists ? $artist->aliases->pluck('alias')->join("\n") : '';
@endphp
<form method="POST" action="{{ $action }}" class="space-y-8" enctype="multipart/form-data">
    @csrf
    @if ($artist->exists) @method('PUT') @endif

    <section class="space-y-4" aria-labelledby="details">
        <h2 id="details" class="text-lg font-semibold">Profile</h2>
        <x-field name="name" label="Name" :value="$artist->name" required />
        <x-field name="slug" label="URL slug (leave blank to generate)" :value="$artist->slug" />
        @if ($artist->published_at)
            <p class="text-sm text-muted">This artist has been published. If you change the slug, the old URL will redirect to the new one.</p>
        @endif
        <x-textarea name="aliases" label="Alternative spellings (one per line)" rows="3" :value="$aliasText" />
        <x-textarea name="bio" label="Biography" rows="8" :value="$artist->bio" />
        <x-field name="country_code" label="Country code (e.g. RW, optional, only when verified)" :value="$artist->country_code" maxlength="2" />
        <x-field name="website" type="url" label="Official website" :value="$artist->website" />
        <fieldset>
            <legend class="text-sm font-medium">Genres</legend>
            <div class="mt-1 flex flex-wrap gap-x-5 gap-y-2">
                @forelse ($genres as $genre)
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="genre_ids[]" value="{{ $genre->id }}" class="size-4" @checked(in_array($genre->id, $selectedGenres, true))> {{ $genre->name }}
                    </label>
                @empty
                    <p class="text-sm text-muted">No genres yet. Add some under Genres.</p>
                @endforelse
            </div>
        </fieldset>
    </section>

    <section class="space-y-6" aria-labelledby="images-heading">
        <h2 id="images-heading" class="text-lg font-semibold">Images</h2>
        <x-image-field name="image" label="Profile image (optional)" :media="$artist->exists ? $artist->image : null" />
        <x-image-field name="banner" label="Banner (optional)" :media="$artist->exists ? $artist->banner : null" />
    </section>

    <section class="space-y-4" aria-labelledby="publishing">
        <h2 id="publishing" class="text-lg font-semibold">Publishing</h2>
        <x-select name="status" label="Status" :options="\App\Support\EnumOptions::for(\App\Enums\PublicationStatus::class)" :selected="$artist->status?->value ?? 'draft'" required />
        <x-field name="published_at" type="datetime-local" label="Publication date and time ({{ \App\Support\AdminDate::timezone() }})" :value="\App\Support\AdminDate::toInput($artist->published_at)" />
        <x-select name="verification_status" label="Verification" :options="\App\Support\EnumOptions::for(\App\Enums\VerificationStatus::class)" :selected="$artist->verification_status?->value ?? 'unverified'" required />
    </section>

    <section class="space-y-4" aria-labelledby="seo">
        <h2 id="seo" class="text-lg font-semibold">Search engines</h2>
        <x-field name="seo_title" label="SEO title (optional)" :value="$artist->seo_title" />
        <x-textarea name="seo_description" label="Meta description (optional, max 320)" rows="2" :value="$artist->seo_description" />
        <label class="flex items-center gap-2 text-sm">
            <input type="hidden" name="noindex" value="0">
            <input type="checkbox" name="noindex" value="1" class="size-4" @checked(old('noindex', $artist->noindex))> Ask search engines not to index this page
        </label>
    </section>

    <x-button type="submit">Save artist</x-button>
</form>
