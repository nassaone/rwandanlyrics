<?php

namespace Database\Seeders;

use App\Enums\PublicationStatus;
use App\Enums\RightsStatus;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Genre;
use App\Models\Language;
use App\Models\Lyric;
use App\Models\Song;
use Illuminate\Database\Seeder;

/**
 * DEVELOPMENT ONLY. Fictional demo data with placeholder text — not real artists or lyrics.
 * Never run in production (DatabaseSeeder only calls it in the local environment).
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (Artist::query()->exists()) {
            return;
        }

        $languages = collect([['Kinyarwanda', 'rw'], ['English', 'en'], ['French', 'fr'], ['Swahili', 'sw']])
            ->map(fn ($l) => Language::firstOrCreate(['code' => $l[1]], ['name' => $l[0]]));

        $genres = collect(['Afrobeats', 'Afro-pop', 'R&B', 'Hip-hop', 'Gospel', 'Traditional'])
            ->map(fn ($name) => Genre::firstOrCreate(['name' => $name]));

        foreach (['A', 'B', 'C'] as $letter) {
            $artist = Artist::factory()->published()->create([
                'name' => "Demo Artist {$letter}",
                'bio' => 'Demo data. Fictional artist for development only.',
            ]);
            $artist->genres()->attach($genres->random()->id);

            $album = Album::factory()->published()->create(['title' => "Demo Album {$letter}"]);
            $album->artists()->attach($artist->id, ['is_primary' => true]);

            foreach ([1, 2, 3] as $n) {
                $song = Song::factory()->published()->create([
                    'title' => "Demo Song {$letter}{$n}",
                    'album_id' => $album->id,
                    'track_number' => $n,
                    'language_id' => $languages->random()->id,
                    'rights_holder' => 'Demo data',
                    'rights_status' => RightsStatus::Authorized,
                    'status' => PublicationStatus::Published,
                ]);
                $song->artists()->attach($artist->id, ['role' => 'primary', 'position' => 0]);
                $song->genres()->attach($genres->random()->id);
                Lyric::factory()->for($song)->create();
            }
        }
    }
}
