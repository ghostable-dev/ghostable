<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('environment_variable_rules');
    }

    public function down(): void
    {
        Schema::create('environment_variable_rules', function ($table) {
            $table->uuid('id')->primary();
            $table->uuid('environment_id')->index();
            $table->string('key');
            $table->string('description')->nullable();
            $table->boolean('is_override')->default(false)->index();
            $table->boolean('is_deleted')->default(false)->index();
            $table->boolean('is_required')->default(true);
            $table->string('type');
            $table->integer('min')->nullable();
            $table->integer('max')->nullable();
            $table->json('allowed_values')->nullable();
            $table->timestamps();

            $table->foreign('environment_id')
                ->references('id')
                ->on('environments')
                ->onDelete('cascade');

            $table->unique(['environment_id', 'key']);
        });
    }
};
