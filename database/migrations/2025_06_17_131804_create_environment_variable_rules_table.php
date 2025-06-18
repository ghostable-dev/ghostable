<?php

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
        Schema::create('environment_variable_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('environment_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->string('rule');
            $table->string('description')->nullable();
            $table->timestamps();
            $table->unique(['environment_id', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('environment_variable_rules');
    }
};
