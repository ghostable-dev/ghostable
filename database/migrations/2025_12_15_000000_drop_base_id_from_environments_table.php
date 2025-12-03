<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('environments', 'base_id')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('environments', function (Blueprint $table) {
            // MySQL will drop the supporting index automatically with dropForeign.
            $table->dropForeign('environments_base_id_foreign');
            $table->dropColumn('base_id');
        });
    }

    public function down(): void
    {
        // No-op: base environments are no longer supported.
    }
};
