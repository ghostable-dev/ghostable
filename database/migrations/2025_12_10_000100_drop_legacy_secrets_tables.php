<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('secret_versions')) {
            Schema::drop('secret_versions');
        }

        if (Schema::hasTable('secrets')) {
            Schema::drop('secrets');
        }
    }

    public function down(): void
    {
        // Recreate lightweight placeholders to support rollback; structure is legacy/unused.
        if (! Schema::hasTable('secrets')) {
            Schema::create('secrets', function (Blueprint $table): void {
                $table->id();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('secret_versions')) {
            Schema::create('secret_versions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('secret_id')->nullable()->constrained('secrets')->nullOnDelete();
                $table->timestamps();
            });
        }
    }
};
