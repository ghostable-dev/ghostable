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
        Schema::create('team_permission_overrides', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->uuidMorphs('target');
            $table->string('permission');
            $table->timestamps();
            $table->softDeletes();
            $table->index('deleted_at');

            $table->unique([
                'user_id',
                'target_id',
                'target_type',
                'permission',
                'deleted_at',
            ], 'unique_override');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_permission_overrides');
    }
};
