<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('environments', function (Blueprint $table) {
            $table->string('file_format')->after('type')->default('alphabetical');
        });
    }

    public function down(): void
    {
        Schema::table('environments', function (Blueprint $table) {
            $table->dropColumn('file_format');
        });
    }
};
