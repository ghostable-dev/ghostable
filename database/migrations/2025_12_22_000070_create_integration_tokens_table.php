<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_tokens', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('integration_client_id')->index();
            $table->uuid('integration_id')->index();
            $table->uuid('organization_id')->index();
            $table->uuid('user_id')->nullable()->index();
            $table->string('access_token_hash', 64);
            $table->timestamp('access_token_expires_at')->nullable()->index();
            $table->string('refresh_token_hash', 64);
            $table->timestamp('refresh_token_expires_at')->nullable()->index();
            $table->json('scopes')->nullable();
            $table->string('token_suffix', 16)->nullable()->index();
            $table->timestamp('last_used_at')->nullable()->index();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->timestamps();

            $table->foreign('integration_client_id')
                ->references('id')
                ->on('integration_clients')
                ->cascadeOnDelete();
            $table->foreign('integration_id')
                ->references('id')
                ->on('integrations')
                ->cascadeOnDelete();
            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->cascadeOnDelete();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_tokens');
    }
};
