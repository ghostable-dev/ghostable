<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement(
                'ALTER TABLE devices MODIFY platform VARCHAR(64) NULL'
            );
        }
    }

    public function down(): void
    {
        // No-op: platform remains a nullable string.
    }
};
