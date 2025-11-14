<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('environment_keys', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('environment_id')->index();
            $table->unsignedInteger('version')->default(1);
            $table->string('fingerprint', 64)->unique();
            $table->uuid('created_by_device_id')->nullable()->index();
            $table->timestamp('rotated_at')->nullable();
            $table->timestamps();

            $table->foreign('environment_id')
                ->references('id')
                ->on('environments')
                ->cascadeOnDelete();

            $table->foreign('created_by_device_id')
                ->references('id')
                ->on('devices')
                ->nullOnDelete();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('environment_keys');
    }
};
