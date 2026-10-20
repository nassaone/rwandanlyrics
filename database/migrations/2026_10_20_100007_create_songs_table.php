<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('songs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->foreignId('album_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('track_number')->nullable();
            $table->foreignId('language_id')->nullable()->constrained()->nullOnDelete();
            $table->date('release_date')->nullable();
            $table->text('description')->nullable();
            $table->json('external_links')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->string('verification_status', 20)->default('unverified');
            $table->string('rights_status', 20)->default('unknown');
            $table->string('rights_holder')->nullable();
            $table->text('rights_notes')->nullable();
            $table->string('rights_source')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 320)->nullable();
            $table->string('canonical_url', 500)->nullable();
            $table->boolean('noindex')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index('rights_status');
        });

        Schema::create('song_artist', function (Blueprint $table) {
            $table->id();
            $table->foreignId('song_id')->constrained()->cascadeOnDelete();
            $table->foreignId('artist_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20)->default('primary');
            $table->unsignedSmallInteger('position')->default(0);

            $table->unique(['song_id', 'artist_id', 'role']);
        });

        Schema::create('genre_song', function (Blueprint $table) {
            $table->id();
            $table->foreignId('genre_id')->constrained()->cascadeOnDelete();
            $table->foreignId('song_id')->constrained()->cascadeOnDelete();

            $table->unique(['genre_id', 'song_id']);
        });

        Schema::create('lyrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('song_id')->constrained()->cascadeOnDelete();
            $table->longText('body');
            $table->unsignedInteger('revision_number')->default(1);
            $table->boolean('is_current')->default(true);
            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['song_id', 'revision_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lyrics');
        Schema::dropIfExists('genre_song');
        Schema::dropIfExists('song_artist');
        Schema::dropIfExists('songs');
    }
};
