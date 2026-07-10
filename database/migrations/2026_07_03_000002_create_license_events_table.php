<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('license_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('license_id')->nullable()->constrained('licenses')->cascadeOnDelete();
            $table->foreignUuid('license_activation_id')->nullable()->constrained('license_activations')->cascadeOnDelete();
            $table->string('type')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('license_events');
    }
};
