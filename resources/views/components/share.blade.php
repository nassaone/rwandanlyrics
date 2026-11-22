@props(['title' => null, 'text' => null])
<div class="relative inline-block"
     x-data="{
        open: false,
        copied: false,
        url: @js(url()->current().'?ref=share'),
        title: @js($title ?? config('app.name')),
        text: @js($text ?? $title ?? config('app.name')),
        async share() {
            if (navigator.share) {
                try { await navigator.share({ title: this.title, text: this.text, url: this.url }); } catch (e) {}
                return;
            }
            this.open = ! this.open;
        },
        async copy() {
            try {
                await navigator.clipboard.writeText(this.url);
            } catch (e) {
                const box = document.createElement('textarea');
                box.value = this.url;
                document.body.appendChild(box);
                box.select();
                document.execCommand('copy');
                box.remove();
            }
            this.copied = true;
            setTimeout(() => { this.copied = false; this.open = false; }, 1800);
        },
     }"
     @keydown.escape.window="open = false"
     @click.outside="open = false">
    <button type="button" @click="share()" aria-haspopup="menu" :aria-expanded="open"
            class="inline-flex min-h-11 items-center gap-2 rounded-md border border-line bg-page px-4 py-2 text-sm font-medium hover:bg-surface">
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 12v7a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-7"/><path d="M16 6l-4-4-4 4"/><path d="M12 2v13"/></svg>
        Share
    </button>

    <div x-show="open" style="display: none;" x-transition.opacity.duration.150ms role="menu" aria-label="Share this page"
         class="absolute left-0 z-20 mt-2 w-60 rounded-xl border border-line bg-page p-1.5 shadow-lg">
        <a role="menuitem" target="_blank" rel="noopener noreferrer" :href="'https://wa.me/?text=' + encodeURIComponent(text + ' ' + url)"
           class="flex min-h-11 items-center rounded-lg px-3 text-sm hover:bg-accent-50">Share on WhatsApp</a>
        <a role="menuitem" target="_blank" rel="noopener noreferrer" :href="'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url)"
           class="flex min-h-11 items-center rounded-lg px-3 text-sm hover:bg-accent-50">Share on Facebook</a>
        <a role="menuitem" target="_blank" rel="noopener noreferrer" :href="'https://twitter.com/intent/tweet?text=' + encodeURIComponent(text) + '&url=' + encodeURIComponent(url)"
           class="flex min-h-11 items-center rounded-lg px-3 text-sm hover:bg-accent-50">Share on X</a>
        <a role="menuitem" target="_blank" rel="noopener noreferrer" :href="'https://t.me/share/url?url=' + encodeURIComponent(url) + '&text=' + encodeURIComponent(text)"
           class="flex min-h-11 items-center rounded-lg px-3 text-sm hover:bg-accent-50">Share on Telegram</a>
        <button type="button" role="menuitem" @click="copy()"
                class="flex min-h-11 w-full items-center rounded-lg px-3 text-left text-sm hover:bg-accent-50">
            <span x-show="! copied">Copy link</span>
            <span x-show="copied" style="display: none;" class="text-accent-600" role="status">Link copied</span>
        </button>
    </div>
</div>
