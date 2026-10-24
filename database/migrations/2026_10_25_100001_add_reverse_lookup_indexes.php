<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** table => list of column sets. Skipped when the table, a column or an equivalent index already exists. */
    private function indexes(): array
    {
        return [
            'song_artist' => [['artist_id']],
            'album_artist' => [['artist_id']],
            'genre_song' => [['genre_id']],
            'genre_artist' => [['genre_id']],
            'songs' => [['album_id'], ['language_id']],
            'lyrics' => [['song_id', 'is_current']],
        ];
    }

    public function up(): void
    {
        foreach ($this->indexes() as $table => $sets) {
            foreach ($sets as $columns) {
                if (Schema::hasTable($table) && Schema::hasColumns($table, $columns) && ! Schema::hasIndex($table, $columns)) {
                    Schema::table($table, fn (Blueprint $t) => $t->index($columns));
                }
            }
        }
    }

    public function down(): void
    {
        foreach ($this->indexes() as $table => $sets) {
            foreach ($sets as $columns) {
                $name = $table.'_'.implode('_', $columns).'_index';
                if (Schema::hasTable($table) && Schema::hasIndex($table, $name)) {
                    Schema::table($table, fn (Blueprint $t) => $t->dropIndex($name));
                }
            }
        }
    }
};
