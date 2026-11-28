<form action="{{ url('/search') }}" method="GET" role="search" class="flex gap-2"
      x-data="{
        q: @js((string) request('q', '')),
        open: false,
        items: { songs: [], artists: [] },
        timer: null,
        suggest() {
            clearTimeout(this.timer);
            const term = this.q.trim();
            if (term.length < 2) { this.open = false; return; }
            this.timer = setTimeout(async () => {
                try {
                    const res = await fetch('{{ url('/search/suggest') }}?q=' + encodeURIComponent(term), { headers: { 'Accept': 'application/json' } });
                    if (! res.ok) return;
                    this.items = await res.json();
                    this.open = (this.items.songs.length + this.items.artists.length) > 0;
                } catch (e) {}
            }, 200);
        },
      }"
      @keydown.escape="open = false" @click.outside="open = false">
    <label for="q" class="sr-only">Search songs, artists, albums</label>
    <div class="relative min-w-0 flex-1">
        <input id="q" name="q" type="search" maxlength="100" value="{{ request('q') }}" placeholder="Search songs, artists, albums"
               autocomplete="off" x-model="q" @input="suggest()" @focus="suggest()"
               class="w-full min-w-0 min-h-11 rounded-lg border border-line px-3 py-2.5 text-base">
        <div x-show="open" style="display: none;" class="absolute left-0 right-0 z-30 mt-1 overflow-hidden rounded-xl border border-line bg-page shadow-lg">
            <template x-for="song in items.songs" :key="song.url">
                <a :href="song.url" class="flex flex-col px-4 py-2.5 hover:bg-accent-50">
                    <span class="font-medium text-ink" x-text="song.title"></span>
                    <span class="text-sm text-muted" x-text="song.artist"></span>
                </a>
            </template>
            <template x-for="artist in items.artists" :key="artist.url">
                <a :href="artist.url" class="flex items-center justify-between border-t border-line px-4 py-2.5 hover:bg-accent-50">
                    <span class="font-medium text-ink" x-text="artist.name"></span>
                    <span class="text-sm text-muted">Artist</span>
                </a>
            </template>
        </div>
    </div>
    <x-button type="submit">Search</x-button>
</form>
