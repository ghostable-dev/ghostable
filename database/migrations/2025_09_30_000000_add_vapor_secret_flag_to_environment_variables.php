<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('environment_variables', function (Blueprint $table) {
            if (! Schema::hasColumn('environment_variables', 'vapor_secret')) {
                $table->boolean('vapor_secret')->default(false)->after('delivery_mode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('environment_variables', function (Blueprint $table) {
            if (Schema::hasColumn('environment_variables', 'vapor_secret')) {
                $table->dropColumn('vapor_secret');
            }
        });
    }
};
