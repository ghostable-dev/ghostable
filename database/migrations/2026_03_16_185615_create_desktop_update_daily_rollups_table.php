<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('desktop_update_daily_rollups', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('event_type', 32);
            $table->string('channel', 16)->default('stable');
            $table->string('release_version', 64)->default('');
            $table->string('release_short_version', 64)->default('');
            $table->boolean('attributed')->default(false);
            $table->unsignedBigInteger('total_events')->default(0);
            $table->unsignedBigInteger('unique_ip_hashes')->default(0);
            $table->unsignedBigInteger('unique_devices')->default(0);
            $table->unsignedBigInteger('unique_users')->default(0);
            $table->timestamps();

            $table->unique(
                ['date', 'event_type', 'channel', 'release_version', 'attributed'],
                'dur_daily_rollup_unique'
            );
            $table->index(['event_type', 'date'], 'dur_event_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('desktop_update_daily_rollups');
    }
};
