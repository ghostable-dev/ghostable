<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->json('notifications')->nullable()->after('owner_id');
        });
        Schema::table('projects', function (Blueprint $table) {
            $table->json('notifications')->nullable()->after('team_id');
        });
        Schema::table('environments', function (Blueprint $table) {
            $table->json('notifications')->nullable()->after('file_format');
        });
        Schema::table('secrets', function (Blueprint $table) {
            $table->json('notifications')->nullable()->after('created_by_id');
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
