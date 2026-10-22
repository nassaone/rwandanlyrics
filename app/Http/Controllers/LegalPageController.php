<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\View\View;

class LegalPageController extends Controller
{
    /** page key => [title, markdown file, meta description] */
    private const PAGES = [
        'privacy' => ['Privacy Policy', 'privacy', 'How Rwandanlyrics handles personal information, cookies and your privacy choices.'],
        'copyright' => ['Copyright & Content Rights Policy', 'copyright', 'How Rwandanlyrics treats copyright, lyrics ownership, permissions and rights complaints.'],
        'takedown' => ['Copyright Takedown Requests', 'takedown', 'How rights holders can ask Rwandanlyrics to review or remove material.'],
        'corrections' => ['Report Incorrect Lyrics', 'corrections', 'How to report errors in lyrics, titles, artist names or credits.'],
    ];

    public function __invoke(string $page): View
    {
        [$title, $file, $description] = self::PAGES[$page] ?? abort(404);

        $text = str_replace(
            ['%COPYRIGHT_EMAIL%', '%CORRECTIONS_EMAIL%', '%PRIVACY_EMAIL%', '%SITE_URL%'],
            [config('rwandanlyrics.contact.copyright'), config('rwandanlyrics.contact.corrections'), config('rwandanlyrics.contact.privacy'), rtrim((string) config('app.url'), '/')],
            file_get_contents(resource_path("policies/{$file}.md")),
        );

        $body = Str::markdown($text, ['html_input' => 'strip', 'allow_unsafe_links' => false]);

        return view('legal.show', compact('title', 'description', 'body'));
    }
}
