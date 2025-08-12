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
        Schema::table('environment_variable_rules', function (Blueprint $table) {
            $table->boolean('is_deleted')->after('description')->default(false)->index();
            $table->boolean('is_override')->after('description')->default(false)->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('environment_variable_rules', function (Blueprint $table) {
            $table->dropColumn(['is_deleted', 'is_override']);
        });
    }
};
