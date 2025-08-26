<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_invites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('status', 30)->index();
            $table->foreignUuid('organization_id')->nullable();
            $table->foreignUuid('user_id')->nullable();
            $table->string('email');
            $table->string('role', 20)->nullable();
            $table->json('permissions')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['email', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_invites');
    }
};
