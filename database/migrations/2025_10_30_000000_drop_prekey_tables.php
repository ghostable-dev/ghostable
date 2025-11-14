<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('one_time_prekeys');
        Schema::dropIfExists('signed_prekeys');
    }

    public function down(): void
    {
        Schema::create('signed_prekeys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('device_id')
                ->references('id')
                ->on('devices')
                ->onDelete('cascade');

            $table->text('public_key');
            $table->text('signature_from_signing_key');
            $table->string('signer_kid')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('revoked')->default(false);
        });

        Schema::create('one_time_prekeys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('device_id')
                ->references('id')
                ->on('devices')
                ->onDelete('cascade');

            $table->text('public_key');
            $table->timestamp('created_at')->useCurrent();

            $table->timestamp('consumed_at')->nullable();
            $table->nullableUuidMorphs('consumable');
            $table->timestamp('expires_at')->nullable();
            $table->boolean('revoked')->default(false);
        });
    }
};
