<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('slack_webhook_url')->nullable()->after('notifications');
            $table->boolean('slack_enabled')->default(false)->after('slack_webhook_url');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('slack_enabled');
            $table->dropColumn('slack_webhook_url');
        });
    }
};
