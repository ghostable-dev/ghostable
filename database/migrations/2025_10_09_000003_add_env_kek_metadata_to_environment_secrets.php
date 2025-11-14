<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('environment_secrets', function (Blueprint $table) {
            $table->unsignedInteger('env_kek_version')->default(1)->after('client_sig');
            $table->string('env_kek_fingerprint')->nullable()->after('env_kek_version');
            $table->json('metadata')->nullable()->after('env_kek_fingerprint');

            $table->string('alg', 64)->default('xchacha20-poly1305')->change();
            $table->boolean('is_commented')->default(false)->change();
            $table->unsignedInteger('line_bytes')->nullable()->change();
        });

        Schema::table('environment_secrets', function (Blueprint $table) {
            $table->dropIndex('environment_secrets_environment_id_is_vapor_secret_index');
        });

        Schema::table('environment_secrets', function (Blueprint $table) {
            $table->dropColumn('is_vapor_secret');
        });

        Schema::table('environment_secret_versions', function (Blueprint $table) {
            $table->unsignedInteger('env_kek_version')->default(1)->after('client_sig');
            $table->string('env_kek_fingerprint')->nullable()->after('env_kek_version');
            $table->json('metadata')->nullable()->after('env_kek_fingerprint');

            $table->string('alg', 64)->default('xchacha20-poly1305')->change();
            $table->boolean('is_commented')->default(false)->change();
            $table->unsignedInteger('line_bytes')->nullable()->change();
        });

        Schema::table('environment_secret_versions', function (Blueprint $table) {
            $table->dropColumn('is_vapor_secret');
        });
    }

    public function down(): void
    {
        Schema::table('environment_secret_versions', function (Blueprint $table) {
            $table->boolean('is_vapor_secret')->default(false)->after('is_commented');
        });

        Schema::table('environment_secret_versions', function (Blueprint $table) {
            $table->dropColumn([
                'env_kek_version',
                'env_kek_fingerprint',
                'metadata',
            ]);
        });

        Schema::table('environment_secrets', function (Blueprint $table) {
            $table->boolean('is_vapor_secret')->default(false)->after('metadata');
            $table->index(['environment_id', 'is_vapor_secret']);
        });

        Schema::table('environment_secrets', function (Blueprint $table) {
            $table->dropColumn([
                'env_kek_version',
                'env_kek_fingerprint',
                'metadata',
            ]);
        });
    }
};
