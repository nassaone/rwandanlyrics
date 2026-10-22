<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Short-lived dedup rows: one per visitor hash per page per day. Pruned after 2 days.
        Schema::create('view_events', function (Blueprint $table) {
            $table->id();
            $table->string('type', 10);
            $table->unsignedBigInteger('item_id');
            $table->char('visitor_hash', 64);
            $table->date('viewed_on');
            $table->unique(['type', 'item_id', 'visitor_hash', 'viewed_on'], 'view_events_unique');
            $table->index('viewed_on');
        });

        // Permanent aggregates. No personal data.
        Schema::create('daily_views', function (Blueprint $table) {
            $table->id();
            $table->string('type', 10);
            $table->unsignedBigInteger('item_id');
            $table->date('viewed_on');
            $table->unsignedInteger('views')->default(0);
            $table->unique(['type', 'item_id', 'viewed_on'], 'daily_views_unique');
            $table->index('viewed_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_views');
        Schema::dropIfExists('view_events');
    }
};
