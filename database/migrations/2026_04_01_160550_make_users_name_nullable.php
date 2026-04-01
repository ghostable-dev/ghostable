<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            DB::statement('
                CREATE TABLE users_temp (
                    id TEXT PRIMARY KEY,
                    name TEXT NULL,
                    email TEXT NOT NULL,
                    email_verified_at DATETIME,
                    status TEXT NOT NULL DEFAULT \'active\',
                    password TEXT NOT NULL,
                    two_factor_secret TEXT,
                    two_factor_recovery_codes TEXT,
                    two_factor_confirmed_at DATETIME,
                    remember_token TEXT,
                    notifications TEXT,
                    last_login DATETIME,
                    timezone TEXT NOT NULL DEFAULT \'UTC\',
                    created_at DATETIME,
                    updated_at DATETIME,
                    deleted_at DATETIME,
                    UNIQUE (email)
                )
            ');

            DB::statement('
                INSERT INTO users_temp (
                    id, name, email, email_verified_at, status, password,
                    two_factor_secret, two_factor_recovery_codes, two_factor_confirmed_at,
                    remember_token, notifications, last_login, timezone,
                    created_at, updated_at, deleted_at
                )
                SELECT
                    id, name, email, email_verified_at, status, password,
                    two_factor_secret, two_factor_recovery_codes, two_factor_confirmed_at,
                    remember_token, notifications, last_login, timezone,
                    created_at, updated_at, deleted_at
                FROM users
            ');

            DB::statement('DROP TABLE users');
            DB::statement('ALTER TABLE users_temp RENAME TO users');

            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('name', 255)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            DB::statement('
                CREATE TABLE users_temp (
                    id TEXT PRIMARY KEY,
                    name TEXT NOT NULL,
                    email TEXT NOT NULL,
                    email_verified_at DATETIME,
                    status TEXT NOT NULL DEFAULT \'active\',
                    password TEXT NOT NULL,
                    two_factor_secret TEXT,
                    two_factor_recovery_codes TEXT,
                    two_factor_confirmed_at DATETIME,
                    remember_token TEXT,
                    notifications TEXT,
                    last_login DATETIME,
                    timezone TEXT NOT NULL DEFAULT \'UTC\',
                    created_at DATETIME,
                    updated_at DATETIME,
                    deleted_at DATETIME,
                    UNIQUE (email)
                )
            ');

            DB::statement('
                INSERT INTO users_temp (
                    id, name, email, email_verified_at, status, password,
                    two_factor_secret, two_factor_recovery_codes, two_factor_confirmed_at,
                    remember_token, notifications, last_login, timezone,
                    created_at, updated_at, deleted_at
                )
                SELECT
                    id, COALESCE(name, email), email, email_verified_at, status, password,
                    two_factor_secret, two_factor_recovery_codes, two_factor_confirmed_at,
                    remember_token, notifications, last_login, timezone,
                    created_at, updated_at, deleted_at
                FROM users
            ');

            DB::statement('DROP TABLE users');
            DB::statement('ALTER TABLE users_temp RENAME TO users');

            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('name', 255)->nullable(false)->change();
        });
    }
};
