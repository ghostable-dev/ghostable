<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Current (latest) secrets
        Schema::create('environment_secrets', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('environment_id')
                ->constrained('environments')
                ->cascadeOnDelete();

            $table->string('name', 255)->index();
            $table->text('ciphertext');
            $table->string('nonce', 255);
            $table->string('alg', 32)->default('xchacha20-poly1305');
            $table->json('aad');
            $table->json('claims');
            $table->text('client_sig');

            $table->unsignedInteger('line_bytes')->nullable();
            $table->boolean('is_vapor_secret')->default(false);
            $table->boolean('is_commented')->default(false);

            $table->unsignedBigInteger('version')->default(1);

            $table->uuid('last_updated_by')->nullable();
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['environment_id', 'name', 'deleted_at']);
            $table->index(['environment_id', 'is_vapor_secret']);
        });

        // Version history (append-only)
        Schema::create('environment_secret_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('environment_secret_id')
                ->constrained('environment_secrets')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('version');          // snapshot number
            $table->string('name', 255);
            $table->string('alg', 32)->default('xchacha20-poly1305');
            $table->text('ciphertext');
            $table->string('nonce', 255);
            $table->json('aad');
            $table->json('claims');
            $table->text('client_sig');

            $table->unsignedInteger('line_bytes')->nullable();
            $table->boolean('is_vapor_secret')->default(false);
            $table->boolean('is_commented')->default(false);

            $table->uuid('changed_by')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['environment_secret_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('environment_secret_versions');
        Schema::dropIfExists('environment_secrets');
    }
};
