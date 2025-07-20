<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('secrets', function (Blueprint $table) {
            $table->foreignUuid('last_updated_by')
                ->after('metadata')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('last_updated_at')
                ->after('metadata')
                ->nullable();
        });

        Schema::create('secret_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('secret_id')
                ->constrained('secrets')
                ->cascadeOnDelete();
            $table->string('name');
            $table->string('type');
            $table->text('value_encrypted');
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('version');
            $table->foreignUuid('changed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->unique(['secret_id', 'version'], 'secret_versions_secret_id_version_unique');
        });
    }

    public function down(): void
    {
        Schema::table('secrets', function (Blueprint $table) {
            $table->dropColumn(['last_updated_by', 'last_updated_at']);
        });

        Schema::dropIfExists('secret_versions');
    }
};
