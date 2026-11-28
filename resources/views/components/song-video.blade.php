@props(['song'])
<section aria-label="Video">
    <div class="aspect-video overflow-hidden rounded-xl border border-line bg-surface">
        <iframe class="size-full" src="https://www.youtube-nocookie.com/embed/{{ $song->youtube_id }}?rel=0" title="{{ $song->title }} on YouTube" loading="lazy" allow="encrypted-media; picture-in-picture; fullscreen" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
    </div>
    <p class="mt-2 text-sm"><a href="{{ \App\Support\YouTube::watchUrl($song->youtube_id) }}" target="_blank" rel="noopener noreferrer" class="text-accent-600 hover:underline">Open on YouTube</a></p>
</section>
