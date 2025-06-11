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
        Schema::table('environment_variables', function (Blueprint $table) {
            $table->foreignUuid('last_updated_by')
                ->after('is_commented')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('last_updated_at')
                ->after('is_commented')
                ->nullable();
        });

        Schema::create('environment_variable_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('environment_variable_id')
                ->constrained('environment_variables')
                ->cascadeOnDelete();
            $table->string('key');
            $table->text('value');
            $table->boolean('is_commented')->default(false);
            $table->unsignedBigInteger('version');
            $table->foreignUuid('changed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['environment_variable_id', 'version'],
                'var_versions_env_id_version_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('environment_variables', function (Blueprint $table) {
            $table->dropColumn([
                'last_updated_by', 'last_updated_at',
            ]);
        });

        Schema::dropIfExists('environment_variable_versions');
    }
};
