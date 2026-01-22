<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('integration_clients', function (Blueprint $table): void {
            $table->string('landing_page_url')->nullable()->after('publish_status');
            $table->text('description')->nullable()->after('landing_page_url');
            $table->string('logo_path')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('integration_clients', function (Blueprint $table): void {
            $table->dropColumn(['landing_page_url', 'description', 'logo_path']);
        });
    }
};
