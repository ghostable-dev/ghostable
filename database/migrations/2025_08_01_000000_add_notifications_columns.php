<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->json('notifications')->nullable();
        });
        Schema::table('projects', function (Blueprint $table) {
            $table->json('notifications')->nullable();
        });
        Schema::table('environments', function (Blueprint $table) {
            $table->json('notifications')->nullable();
        });
        Schema::table('secrets', function (Blueprint $table) {
            $table->json('notifications')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('notifications');
        });
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('notifications');
        });
        Schema::table('environments', function (Blueprint $table) {
            $table->dropColumn('notifications');
        });
        Schema::table('secrets', function (Blueprint $table) {
            $table->dropColumn('notifications');
        });
    }
};
