<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('environment_variable_promotion_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('project_id');
            $table->uuid('source_environment_id');
            $table->uuid('target_environment_id');
            $table->uuid('request_device_id');
            $table->uuid('requested_by_user_id');
            $table->uuid('resolved_by_user_id')->nullable();
            $table->string('status', 32);
            $table->boolean('include_values')->default(false);
            $table->unsignedInteger('target_key_version')->nullable();
            $table->json('entries');
            $table->string('idempotency_key', 128)->nullable();
            $table->string('entries_hash', 64);
            $table->string('rejected_reason', 500)->nullable();
            $table->string('cancel_reason', 500)->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['requested_by_user_id', 'source_environment_id', 'target_environment_id', 'idempotency_key'],
                'evpr_user_source_target_idempotency_unique'
            );
            $table->index('organization_id', 'evpr_org_idx');
            $table->index('project_id', 'evpr_project_idx');
            $table->index('source_environment_id', 'evpr_source_env_idx');
            $table->index('target_environment_id', 'evpr_target_env_idx');
            $table->index('request_device_id', 'evpr_request_device_idx');
            $table->index('requested_by_user_id', 'evpr_requested_by_idx');
            $table->index('resolved_by_user_id', 'evpr_resolved_by_idx');
            $table->index('status', 'evpr_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('environment_variable_promotion_requests');
    }
};
