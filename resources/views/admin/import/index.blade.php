<x-admin-layout title="Import">
    <div class="max-w-3xl space-y-6">
        <h1 class="text-2xl font-bold">Import from CSV</h1>

        @if (session('status'))
            <div class="rounded-lg border border-line bg-accent-50 p-4 text-sm">{{ session('status') }}</div>
        @endif

        <section class="rounded-xl border border-line bg-surface p-5 space-y-3">
            <h2 class="text-lg font-semibold">How it works</h2>
            <ol class="list-decimal space-y-1 pl-5 text-sm">
                <li>Download a template and fill it in with a spreadsheet app. Save it as CSV UTF-8.</li>
                <li>Upload it here. You see a preview first and nothing is saved yet.</li>
                <li>Press Import. Everything is created as a <strong>draft</strong>. Nothing is published.</li>
            </ol>
            <p class="text-sm text-muted">Columns: <code>artist</code> (required), <code>title</code>, <code>youtube_url</code>, <code>album</code>, <code>genre</code>, <code>language</code>, <code>release_date</code>, <code>featured_artists</code>, <code>lyrics</code>. Separate several genres or featured artists with <code>;</code>. A row with an artist and no title only creates the artist. Up to {{ $maxRows }} rows per file.</p>
            <p class="text-sm text-muted">Only fill the <code>lyrics</code> column for lyrics you are allowed to publish. Imported songs always start with rights status "unknown" and must be reviewed.</p>
            <p class="flex flex-wrap gap-3 text-sm">
                <a class="text-accent-600 hover:underline" href="{{ route('admin.import.template', 'songs') }}">Download song template</a>
                <a class="text-accent-600 hover:underline" href="{{ route('admin.import.template', 'artists') }}">Download starter list of Rwandan artists</a>
            </p>
        </section>

        <form method="post" action="{{ route('admin.import.preview') }}" enctype="multipart/form-data" class="rounded-xl border border-line bg-surface p-5 space-y-3">
            @csrf
            <label for="file" class="block font-medium">CSV file</label>
            <input id="file" name="file" type="file" accept=".csv,.txt,text/csv" required class="block w-full text-sm">
            @error('file')<p class="text-sm font-medium" role="alert">{{ $message }}</p>@enderror
            <button type="submit" class="inline-flex min-h-11 items-center rounded-full bg-accent-600 px-5 font-medium text-on-accent hover:bg-accent-700">Preview import</button>
        </form>
    </div>
</x-admin-layout>
