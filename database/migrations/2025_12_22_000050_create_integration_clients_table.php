<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_clients', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('key', 64)->index();
            $table->string('client_id', 191)->unique();
            $table->string('client_secret_hash');
            $table->json('redirect_uris')->nullable();
            $table->json('default_scopes')->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->uuid('owner_organization_id')->nullable()->index();
            $table->string('publish_status', 32)->default('published')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('owner_organization_id')
                ->references('id')
                ->on('organizations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_clients');
    }
};
