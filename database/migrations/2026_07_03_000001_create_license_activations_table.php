<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('license_activations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('license_id')->constrained('licenses')->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->string('activation_token_hash', 64)->unique();
            $table->string('machine_fingerprint_hash', 64)->index();
            $table->string('machine_name')->nullable();
            $table->string('platform')->index();
            $table->string('app_version')->nullable();
            $table->timestamp('last_validated_at')->nullable()->index();
            $table->timestamp('deactivated_at')->nullable()->index();
            $table->timestamps();

            $table->index(['license_id', 'machine_fingerprint_hash']);
            $table->index(['license_id', 'user_id']);
            $table->index(['license_id', 'device_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('license_activations');
    }
};
