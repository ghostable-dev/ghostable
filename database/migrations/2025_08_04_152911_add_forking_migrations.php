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
        Schema::table('environments', function (Blueprint $table) {
            $table->foreignUuid('base_id')
                ->nullable()
                ->after('id')
                ->constrained('environments')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });
        
        Schema::table('environment_variables', function (Blueprint $table) {
            $table->boolean('is_deleted')->after('is_commented')->default(false)->index();
            $table->boolean('is_override')->after('is_commented')->default(false)->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('environments', function (Blueprint $table) {
            $table->dropColumn(['base_id']);
        });
        
        Schema::table('environment_variables', function (Blueprint $table) {
            $table->dropColumn(['is_deleted', 'is_override']);
        });
    }
};
