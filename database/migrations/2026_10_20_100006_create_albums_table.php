<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('albums', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('type', 10)->default('album');
            $table->date('release_date')->nullable();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 320)->nullable();
            $table->boolean('noindex')->default(false);
            $table->timestamps();

            $table->index(['status', 'published_at']);
        });

        Schema::create('album_artist', function (Blueprint $table) {
            $table->id();
            $table->foreignId('album_id')->constrained()->cascadeOnDelete();
            $table->foreignId('artist_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(true);

            $table->unique(['album_id', 'artist_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('album_artist');
        Schema::dropIfExists('albums');
    }
};
