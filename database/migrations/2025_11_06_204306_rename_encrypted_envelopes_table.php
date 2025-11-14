<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('encrypted_envelopes') && ! Schema::hasTable('envelopes')) {
            Schema::rename('encrypted_envelopes', 'envelopes');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('envelopes') && ! Schema::hasTable('encrypted_envelopes')) {
            Schema::rename('envelopes', 'encrypted_envelopes');
        }
    }
};
