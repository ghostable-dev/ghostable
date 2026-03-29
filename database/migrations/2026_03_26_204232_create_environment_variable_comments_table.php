<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('environment_variable_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('environment_secret_id')
                ->constrained('environment_secrets')
                ->cascadeOnDelete();
            $table->text('ciphertext');
            $table->string('nonce', 255);
            $table->string('alg', 64)->default('xchacha20-poly1305');
            $table->json('aad');
            $table->json('claims')->nullable();
            $table->text('client_sig');
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(
                ['environment_secret_id', 'created_at'],
                'env_var_comments_secret_created_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('environment_variable_comments');
    }
};
