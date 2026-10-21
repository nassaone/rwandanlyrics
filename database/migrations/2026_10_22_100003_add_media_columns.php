<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('songs', function (Blueprint $table) {
            $table->foreignId('cover_id')->nullable()->constrained('media')->nullOnDelete();
        });
        Schema::table('albums', function (Blueprint $table) {
            $table->foreignId('cover_id')->nullable()->constrained('media')->nullOnDelete();
        });
        Schema::table('artists', function (Blueprint $table) {
            $table->foreignId('image_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('banner_id')->nullable()->constrained('media')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('artists', function (Blueprint $table) {
            $table->dropConstrainedForeignId('banner_id');
            $table->dropConstrainedForeignId('image_id');
        });
        Schema::table('albums', fn (Blueprint $table) => $table->dropConstrainedForeignId('cover_id'));
        Schema::table('songs', fn (Blueprint $table) => $table->dropConstrainedForeignId('cover_id'));
    }
};
