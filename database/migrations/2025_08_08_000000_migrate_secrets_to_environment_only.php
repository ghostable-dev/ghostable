<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('secrets', 'environment_id')) {
            Schema::table('secrets', function (Blueprint $table) {
                $table->foreignUuid('environment_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('environments')
                    ->cascadeOnDelete();
            });
        }

        if (Schema::hasColumn('secrets', 'owner_id') && Schema::hasColumn('secrets', 'owner_type')) {
            DB::statement("UPDATE secrets SET environment_id = owner_id WHERE owner_type = 'environment'");

            $driver = DB::connection()->getDriverName();

            if ($driver === 'pgsql') {
                DB::statement("UPDATE secrets SET metadata = jsonb_set(COALESCE(metadata::jsonb, '{}'::jsonb), '{project_id}', to_jsonb(owner_id)) WHERE owner_type = 'project'");
            } elseif ($driver === 'mysql') {
                DB::statement("UPDATE secrets SET metadata = JSON_SET(COALESCE(metadata, JSON_OBJECT()), '$.project_id', owner_id) WHERE owner_type = 'project'");
            } elseif ($driver === 'sqlite') {
                DB::statement("UPDATE secrets SET metadata = json_set(COALESCE(metadata, json('{}')), '$.project_id', owner_id) WHERE owner_type = 'project'");
            }

            Schema::table('secrets', function (Blueprint $table) {
                $table->dropMorphs('owner');
            });
        }
    }

    public function down(): void
    {
        Schema::table('secrets', function (Blueprint $table) {
            $table->uuidMorphs('owner');
            $table->dropConstrainedForeignId('environment_id');
        });
    }
};
