<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuidMorphs('recipient'); // recipient_type, recipient_id (both indexed)

            // Cached email for fast lookups / provider webhooks (keep in sync on send)
            $table->string('recipient_email')->index();

            // Campaign identity
            $table->string('campaign_key', 96)->index();

            // Status rollup
            $table->string('status', 24)->index(); // queued|sent|suppressed|failed
            $table->string('reason', 128)->nullable(); // e.g. unsubscribed, missing-email

            // Provider tracking
            $table->string('provider', 32)->nullable();        // e.g. resend
            $table->string('provider_message_id', 128)->nullable()->index();

            // Timeline stamps
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            // Prevent duplicate sends per campaign+recipient
            $table->unique(
                ['campaign_key', 'recipient_type', 'recipient_id'],
                'uniq_campaign_recipient'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
