<form action="{{ url('/search') }}" method="GET" role="search" class="flex gap-2">
    <label for="q" class="sr-only">Search songs, artists, albums</label>
    <input id="q" name="q" type="search" maxlength="100" value="{{ request('q') }}" placeholder="Search songs, artists, albums"
           class="flex-1 min-w-0 rounded-md border border-line px-3 py-2 text-base">
    <x-button type="submit">Search</x-button>
</form>
