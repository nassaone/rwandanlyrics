@props(['song', 'artists' => '', 'body' => null])
@php
    $payload = $song->title.($artists ? ' - '.$artists : '')."\n\n".trim((string) $body)."\n\nLyrics from ".config('app.name').': '.url()->current();
    $filename = (\Illuminate\Support\Str::slug($song->title.' '.$artists) ?: 'lyrics').'.txt';
    $btn = 'inline-flex min-h-11 items-center gap-2 rounded-md border border-line bg-page px-4 py-2 text-sm font-medium hover:bg-surface';
@endphp
<div class="flex flex-wrap items-center gap-2" data-lyrics-toolbar
     x-data="{
        copied: false,
        text: @js($payload),
        async copy() {
            try {
                await navigator.clipboard.writeText(this.text);
            } catch (e) {
                const box = document.createElement('textarea');
                box.value = this.text;
                document.body.appendChild(box);
                box.select();
                document.execCommand('copy');
                box.remove();
            }
            this.copied = true;
            setTimeout(() => this.copied = false, 2000);
        },
        download() {
            const file = new Blob([this.text], { type: 'text/plain;charset=utf-8' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(file);
            link.download = @js($filename);
            document.body.appendChild(link);
            link.click();
            link.remove();
            setTimeout(() => URL.revokeObjectURL(link.href), 1000);
        },
     }">
    <x-share :title="$song->title" :text="'Lyrics of '.$song->title.($artists ? ' by '.$artists : '').' on '.config('app.name')" />

    @if ($body)
        <button type="button" @click="copy()" class="{{ $btn }}">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V6a2 2 0 0 1 2-2h9"/></svg>
            <span x-show="! copied">Copy</span>
            <span x-show="copied" style="display: none;" role="status">Copied</span>
        </button>
        <button type="button" @click="download()" class="{{ $btn }}">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12"/><path d="M7 11l5 5 5-5"/><path d="M5 21h14"/></svg>
            Download
        </button>
    @endif
</div>
