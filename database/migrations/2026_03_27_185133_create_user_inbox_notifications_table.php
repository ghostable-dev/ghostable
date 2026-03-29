<?php

declare(strict_types=1);

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
        Schema::create('user_inbox_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignUuid('environment_id')->nullable()->constrained('environments')->nullOnDelete();
            $table->foreignUuid('environment_secret_id')
                ->nullable()
                ->constrained('environment_secrets')
                ->nullOnDelete();
            $table->string('event', 120);
            $table->string('reference_type', 120)->nullable();
            $table->string('reference_id', 64)->nullable();
            $table->text('description');
            $table->json('payload')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(
                ['user_id', 'organization_id', 'read_at', 'created_at'],
                'uin_user_org_read_created_idx'
            );
            $table->index(['reference_type', 'reference_id'], 'uin_reference_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_inbox_notifications');
    }
};
