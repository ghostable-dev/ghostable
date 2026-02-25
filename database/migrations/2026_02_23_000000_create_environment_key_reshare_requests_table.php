<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('environment_key_reshare_requests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->index();
            $table->uuid('project_id')->index();
            $table->uuid('environment_id')->index();
            $table->unsignedInteger('required_key_version');
            $table->uuid('target_user_id')->index();
            $table->uuid('target_device_id')->index();
            $table->string('status', 32)->index();
            $table->string('trigger_source', 32)->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->uuid('resolved_by_user_id')->nullable()->index();
            $table->string('cancel_reason')->nullable();
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['environment_id', 'target_device_id', 'required_key_version'],
                'ekrr_environment_device_version_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('environment_key_reshare_requests');
    }
};
