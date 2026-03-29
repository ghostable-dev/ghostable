<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('environment_variable_version_change_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('environment_secret_version_id');
            $table->text('ciphertext');
            $table->string('nonce', 255);
            $table->string('alg', 64)->default('xchacha20-poly1305');
            $table->json('aad');
            $table->json('claims')->nullable();
            $table->text('client_sig');
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('environment_secret_version_id', 'env_var_ver_change_note_version_fk')
                ->references('id')
                ->on('environment_secret_versions')
                ->cascadeOnDelete();
            $table->unique('environment_secret_version_id', 'env_var_ver_change_note_version_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('environment_variable_version_change_notes');
    }
};
