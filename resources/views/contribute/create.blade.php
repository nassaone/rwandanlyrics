<x-layouts.app :title="$song ? 'Suggest a correction' : 'Submit a song'" robots="noindex,nofollow">
    <h1 class="text-2xl font-semibold">{{ $song ? 'Suggest a correction' : 'Submit a song' }}</h1>
    <p class="mt-1 text-sm text-muted">Only submit lyrics you wrote or have permission to share. An editor reviews every submission before anything is published.</p>

    <form method="POST" action="{{ route('contribute.store') }}" class="mt-6 space-y-5">
        @csrf
        @if ($song) <input type="hidden" name="song" value="{{ $song->slug }}"> @endif
        <x-field name="title" label="Song title" :value="$song?->title" required />
        <x-field name="artist_name" label="Artist name" :value="$song?->artists->pluck('name')->join(', ')" required />
        <x-field name="album_title" label="Album (optional)" />
        <x-select name="language_id" label="Language" :options="$languages" placeholder="Not sure" />
        <x-textarea name="lyrics" label="Lyrics" rows="14" :value="$song?->currentLyric?->body" required />
        <x-textarea name="notes" label="Notes for the editor (optional)" rows="3" />

        <fieldset class="space-y-3 rounded-md border border-line p-4">
            <legend class="px-1 text-sm font-medium">Rights</legend>
            <x-field name="rights_holder" label="Who owns the lyrics? (optional)" />
            <x-field name="rights_source" label="Proof or permission, e.g. a link or message (optional)" />
            <label class="flex items-start gap-2 text-sm">
                <input type="checkbox" name="rights_confirmed" value="1" class="mt-1 size-4" @checked(old('rights_confirmed'))>
                <span>I wrote these lyrics, or I have permission from the rights holder to submit them for publication on this site.</span>
            </label>
            @error('rights_confirmed') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
        </fieldset>

        <x-button type="submit">Send for review</x-button>
    </form>
</x-layouts.app>
