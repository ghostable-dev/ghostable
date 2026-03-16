<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('desktop_update_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 32);
            $table->string('source', 32);
            $table->string('channel', 16)->default('stable');
            $table->string('release_version', 64)->nullable();
            $table->string('release_short_version', 64)->nullable();
            $table->string('current_version', 64)->nullable();
            $table->string('from_version', 64)->nullable();
            $table->uuid('update_cycle_id')->nullable();
            $table->foreignUuid('device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('attributed')->default(false);
            $table->string('ip_address', 45)->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent', 1024)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('rolled_up_at')->nullable();
            $table->timestamp('ip_anonymized_at')->nullable();
            $table->timestamps();

            $table->index(['event_type', 'created_at'], 'due_event_created_idx');
            $table->index(['channel', 'release_version'], 'due_channel_version_idx');
            $table->index('update_cycle_id', 'due_cycle_idx');
            $table->index('rolled_up_at', 'due_rolled_up_idx');
            $table->index('ip_hash', 'due_ip_hash_idx');
            $table->index(['attributed', 'created_at'], 'due_attributed_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('desktop_update_events');
    }
};
