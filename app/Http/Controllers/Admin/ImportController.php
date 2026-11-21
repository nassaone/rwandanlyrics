<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CsvImporter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportController extends Controller
{
    private const STARTER_ARTISTS = [
        'Meddy', 'The Ben', 'Bruce Melodie', 'Knowless Butera', 'Riderman', 'Kenny Sol', 'Chriss Eazy',
        'Juno Kizigenza', 'Platini P', 'Bwiza', 'Ariel Wayz', 'Nel Ngabo', 'Yvan Buravan', 'Israel Mbonyi',
        'Mani Martin', 'Social Mula', 'Alyn Sano',
    ];

    public function __construct(private CsvImporter $importer) {}

    public function index(): View
    {
        return view('admin.import.index', ['maxRows' => CsvImporter::MAX_ROWS]);
    }

    public function preview(Request $request): View|RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'max:1024', 'mimes:csv,txt']]);

        $contents = (string) file_get_contents($request->file('file')->getRealPath());
        if (! mb_check_encoding($contents, 'UTF-8')) {
            return back()->withErrors(['file' => 'Save the file as CSV UTF-8 and try again.']);
        }

        try {
            $plan = $this->importer->plan($contents);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        $token = $request->user()->id.'-'.Str::uuid();
        Storage::disk('local')->put("imports/{$token}.csv", $contents);

        return view('admin.import.preview', compact('plan', 'token'));
    }

    public function commit(Request $request): RedirectResponse
    {
        $data = $request->validate(['token' => ['required', 'regex:/^\d+-[0-9a-f-]{36}$/']]);
        abort_unless(Str::startsWith($data['token'], $request->user()->id.'-'), 403);

        $path = "imports/{$data['token']}.csv";
        abort_unless(Storage::disk('local')->exists($path), 404);

        $result = $this->importer->execute(Storage::disk('local')->get($path), $request->user());
        Storage::disk('local')->delete($path);

        return redirect()->route('admin.import.index')->with('status', sprintf(
            'Import finished: %d artists, %d songs and %d genres were created as drafts. %d rows were skipped and %d had errors.',
            $result['artists'], $result['songs'], $result['genres'], $result['skipped'], $result['errors'],
        ));
    }

    public function template(string $kind): StreamedResponse
    {
        $rows = $kind === 'artists'
            ? array_merge([['artist', 'country']], array_map(fn ($n) => [$n, 'RW'], self::STARTER_ARTISTS))
            : [['artist', 'title', 'youtube_url', 'album', 'genre', 'language', 'release_date', 'featured_artists', 'lyrics']];

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            foreach ($rows as $row) {
                fputcsv($out, $row, ',', '"', '');
            }
            fclose($out);
        }, $kind.'-template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
