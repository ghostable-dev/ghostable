<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_usage_hourly', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('organization_id');
            $table->foreignUuid('token_id');
            $table->string('method', 8)->nullable();
            $table->string('endpoint', 191);
            $table->nullableUuidMorphs('resource'); // -> resource_type, resource_id (UUID), indexed
            $table->timestamp('hour');
            $table->unsignedBigInteger('count')->default(0);
            $table->timestamps();

            $table->unique(
                ['organization_id', 'token_id', 'method', 'endpoint', 'resource_type', 'resource_id', 'hour'],
                'au_hourly_uq'
            );
        });

        Schema::create('api_usage_daily', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('organization_id');
            $table->foreignUuid('token_id');
            $table->string('method', 8)->nullable();
            $table->string('endpoint', 191);
            $table->nullableUuidMorphs('resource'); // same here
            $table->date('date');
            $table->unsignedBigInteger('count')->default(0);
            $table->timestamps();

            $table->unique(
                ['organization_id', 'token_id', 'method', 'endpoint', 'resource_type', 'resource_id', 'date'],
                'au_daily_uq'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_usage_hourly');
        Schema::dropIfExists('api_usage_daily');
    }
};
