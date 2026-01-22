<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_authorization_codes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('integration_client_id')->index();
            $table->uuid('organization_id')->index();
            $table->uuid('user_id')->nullable()->index();
            $table->string('code_hash', 64);
            $table->json('scopes')->nullable();
            $table->text('redirect_uri');
            $table->string('state')->nullable();
            $table->text('code_challenge')->nullable();
            $table->string('code_challenge_method', 10)->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamp('consumed_at')->nullable()->index();
            $table->timestamps();

            $table->foreign('integration_client_id')
                ->references('id')
                ->on('integration_clients')
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
        Schema::dropIfExists('integration_authorization_codes');
    }
};
