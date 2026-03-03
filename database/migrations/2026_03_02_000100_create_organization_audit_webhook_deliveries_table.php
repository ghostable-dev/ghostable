<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_audit_webhook_deliveries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_audit_webhook_id');
            $table->uuid('organization_id');
            $table->string('event_id')->nullable();
            $table->string('event_type')->nullable();
            $table->string('status');
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->unsignedInteger('attempt_number')->default(1);
            $table->text('error_message')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->foreign('organization_audit_webhook_id', 'oawd_webhook_fk')
                ->references('id')
                ->on('organization_audit_webhooks')
                ->cascadeOnDelete();
            $table->foreign('organization_id', 'oawd_org_fk')
                ->references('id')
                ->on('organizations')
                ->cascadeOnDelete();

            $table->index(['organization_id', 'created_at'], 'oawd_org_created_idx');
            $table->index(['organization_audit_webhook_id', 'created_at'], 'oawd_webhook_created_idx');
            $table->index(['organization_id', 'status'], 'oawd_org_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_audit_webhook_deliveries');
    }
};
