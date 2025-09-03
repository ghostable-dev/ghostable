<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->softDeletes();
            $table->index(['status', 'posted_at']);
        });

        Schema::table('environment_variables', function (Blueprint $table) {
            $table->unique(['environment_id', 'key', 'deleted_at']);
        });

        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropIndex(['status', 'posted_at']);
        });

        Schema::table('environment_variables', function (Blueprint $table) {
            $table->dropUnique(['environment_id', 'key', 'deleted_at']);
        });

        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropIndex(['expires_at']);
        });
    }
};
