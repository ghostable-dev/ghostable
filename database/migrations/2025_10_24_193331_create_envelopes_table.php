<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('envelopes', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('owner_type');
            $table->uuid('owner_id');

            $table->string('alg', 64)->default('xchacha20-poly1305');
            $table->text('nonce_b64');
            $table->longText('ciphertext_b64');
            $table->text('aad_b64')->nullable();

            $table->json('recipients')->nullable();

            $table->string('version', 32)->default('1');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('revoked_at')->nullable();

            $table->index(['owner_type', 'owner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('envelopes');
    }
};
