<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('artists', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('normalized_name')->unique();
            $table->string('slug')->unique();
            $table->text('bio')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('website')->nullable();
            $table->json('social_links')->nullable();
            $table->string('status', 20)->default('draft');
            $table->string('verification_status', 20)->default('unverified');
            $table->timestamp('published_at')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 320)->nullable();
            $table->boolean('noindex')->default(false);
            $table->timestamps();

            $table->index(['status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artists');
    }
};
