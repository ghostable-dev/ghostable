<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('environments')
            ->whereNotNull('base_id')
            ->whereIn('project_id', function ($query) {
                $query->select('id')
                    ->from('projects')
                    ->where('is_legacy', false);
            })
            ->update(['base_id' => null]);
    }

    public function down(): void
    {
        // No-op: base relationships for zero-knowledge projects are intentionally removed.
    }
};
