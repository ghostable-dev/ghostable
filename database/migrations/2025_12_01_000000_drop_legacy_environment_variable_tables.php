<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('environment_variable_versions');
        Schema::dropIfExists('environment_variable_rules');
        Schema::dropIfExists('environment_variables');
    }

    public function down(): void
    {
        Schema::create('environment_variables', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('environment_id')->index();
            $table->string('key');
            $table->text('value');
            $table->boolean('is_commented')->default(false);
            $table->boolean('is_override')->default(false)->index();
            $table->boolean('is_deleted')->default(false)->index();
            $table->timestamp('last_updated_at')->nullable();
            $table->uuid('last_updated_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['environment_id', 'key', 'deleted_at'], 'env_vars_env_key_deleted_at_unique');
            $table->foreign('environment_id')->references('id')->on('environments')->onDelete('cascade');
            $table->foreign('last_updated_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('environment_variable_rules', function (Blueprint $table) {
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

            $table->unique(['environment_id', 'key']);
            $table->foreign('environment_id')->references('id')->on('environments')->onDelete('cascade');
        });

        Schema::create('environment_variable_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('environment_variable_id')->index();
            $table->string('key');
            $table->text('value');
            $table->boolean('is_commented')->default(false);
            $table->unsignedBigInteger('version');
            $table->uuid('changed_by')->nullable()->index();
            $table->timestamps();

            $table->unique(['environment_variable_id', 'version'], 'env_var_versions_env_id_version_unique');
            $table->foreign('environment_variable_id')->references('id')->on('environment_variables')->onDelete('cascade');
            $table->foreign('changed_by')->references('id')->on('users')->onDelete('set null');
        });
    }
};
