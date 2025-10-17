<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('environment_secrets', 'is_override')) {
            Schema::table('environment_secrets', function (Blueprint $table) {
                $table->dropColumn('is_override');
            });
        }

        if (Schema::hasColumn('environment_secret_versions', 'is_override')) {
            Schema::table('environment_secret_versions', function (Blueprint $table) {
                $table->dropColumn('is_override');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('environment_secrets', 'is_override')) {
            Schema::table('environment_secrets', function (Blueprint $table) {
                $table->boolean('is_override')->default(false)->after('is_commented');
            });
        }

        if (! Schema::hasColumn('environment_secret_versions', 'is_override')) {
            Schema::table('environment_secret_versions', function (Blueprint $table) {
                $table->boolean('is_override')->default(false)->after('is_commented');
            });
        }
    }
};
