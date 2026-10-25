@php
    $primaryId = $song->exists ? $song->artists->firstWhere('pivot.role', 'primary')?->id : null;
    $featuredIds = $song->exists ? $song->artists->where('pivot.role', 'featured')->pluck('id')->all() : [];
    $selectedGenres = array_map('intval', (array) old('genre_ids', $song->exists ? $song->genres->pluck('id')->all() : []));
    $statusOptions = \App\Support\EnumOptions::for(\App\Enums\PublicationStatus::class);
@endphp
<form method="POST" action="{{ $action }}" class="space-y-8" enctype="multipart/form-data">
    @csrf
    @if ($song->exists) @method('PUT') @endif

    <section class="space-y-4" aria-labelledby="details">
        <h2 id="details" class="text-lg font-semibold">Details</h2>
        <x-field name="title" label="Title" :value="$song->title" required />
        <x-field name="slug" label="URL slug (leave blank to generate)" :value="$song->slug" />
        @if ($song->published_at)
            <p class="text-sm text-muted">This song has been published. If you change the slug, the old URL will redirect to the new one.</p>
        @endif
        <x-select name="primary_artist_id" label="Primary artist" :options="$artists" :selected="$primaryId" placeholder="Choose an artist" required />
        <x-select name="featured_artist_ids" label="Featured / additional artists (optional)" :options="$artists" :selected="$featuredIds" :multiple="true" />
        <x-select name="album_id" label="Album" :options="$albums" :selected="$song->album_id" placeholder="No album (single)" />
        <x-field name="track_number" type="number" label="Track number" :value="$song->track_number" min="1" />
        <x-select name="language_id" label="Language" :options="$languages" :selected="$song->language_id" placeholder="Unknown" />
        <x-field name="release_date" type="date" label="Release date" :value="$song->release_date?->format('Y-m-d')" />
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
        <x-textarea name="description" label="Editorial notes (optional)" rows="4" :value="$song->description" />
        <x-field name="youtube_url" label="YouTube link (optional)" :value="$song->youtube_id ? \App\Support\YouTube::watchUrl($song->youtube_id) : null" />
        <p class="text-sm text-muted">Paste the link of the official video. Only the video ID is saved. Add only videos you are allowed to link to.</p>
    </section>

    <section class="space-y-4" aria-labelledby="cover-heading">
        <h2 id="cover-heading" class="text-lg font-semibold">Cover image</h2>
        <x-image-field name="cover" label="Cover (optional)" :media="$song->exists ? $song->cover : null" />
    </section>

    <section class="space-y-4" aria-labelledby="lyrics-heading">
        <h2 id="lyrics-heading" class="text-lg font-semibold">Lyrics</h2>
        <x-textarea name="lyrics" label="Lyrics (authorized text only)" rows="14" :value="$song->exists ? $song->currentLyric?->body : null" />
        <p class="text-sm text-muted">Only enter lyrics you have the right to publish. Saving changed text creates a new revision and keeps the old one.</p>
    </section>

    <section class="space-y-4" aria-labelledby="publishing">
        <h2 id="publishing" class="text-lg font-semibold">Publishing</h2>
        <x-select name="status" label="Status" :options="$statusOptions" :selected="$song->status?->value ?? 'draft'" required />
        <x-field name="published_at" type="datetime-local" label="Publication date and time ({{ \App\Support\AdminDate::timezone() }})" :value="\App\Support\AdminDate::toInput($song->published_at)" />
        <p class="text-sm text-muted">Leave blank to publish immediately. For "Scheduled", choose a future time.</p>
        <x-select name="verification_status" label="Content verification" :options="\App\Support\EnumOptions::for(\App\Enums\VerificationStatus::class)" :selected="$song->verification_status?->value ?? 'unverified'" required />
    </section>

    <section class="space-y-4" aria-labelledby="rights">
        <h2 id="rights" class="text-lg font-semibold">Rights</h2>
        <x-select name="rights_status" label="Rights status" :options="\App\Support\EnumOptions::for(\App\Enums\RightsStatus::class)" :selected="$song->rights_status?->value ?? 'unknown'" required />
        <p class="text-sm text-muted">The public site shows a song only when it is published, its date has passed, and rights are Authorized.</p>
        <x-field name="rights_holder" label="Rights holder" :value="$song->rights_holder" />
        <x-field name="rights_source" label="Permission source / reference" :value="$song->rights_source" />
        <x-textarea name="rights_notes" label="Licensing notes (internal)" rows="3" :value="$song->rights_notes" />
    </section>

    <section class="space-y-4" aria-labelledby="seo">
        <h2 id="seo" class="text-lg font-semibold">Search engines</h2>
        <x-field name="seo_title" label="SEO title (optional)" :value="$song->seo_title" />
        <x-textarea name="seo_description" label="Meta description (optional, max 320)" rows="2" :value="$song->seo_description" />
        <x-field name="canonical_url" type="url" label="Canonical URL override (rarely needed)" :value="$song->canonical_url" />
        <label class="flex items-center gap-2 text-sm">
            <input type="hidden" name="noindex" value="0">
            <input type="checkbox" name="noindex" value="1" class="size-4" @checked(old('noindex', $song->noindex))> Ask search engines not to index this page
        </label>
    </section>

    <x-button type="submit">Save song</x-button>
</form>
