<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('environment_variables')) {
            return;
        }

        Schema::table('environment_variables', function (Blueprint $table) {
            $table->boolean('is_vapor_secret')->default(false)->after('value');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('environment_variables')) {
            return;
        }

        Schema::table('environment_variables', function (Blueprint $table) {
            $table->dropColumn('is_vapor_secret');
        });
    }
};
