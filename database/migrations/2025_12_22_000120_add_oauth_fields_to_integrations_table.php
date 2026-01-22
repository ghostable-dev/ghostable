<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('integrations', function (Blueprint $table): void {
            $table->string('direction', 32)->default('outgoing')->after('status');
            $table->uuid('integration_client_id')->nullable()->after('direction');
            $table->uuid('approved_by_user_id')->nullable()->after('integration_client_id');
            $table->timestamp('approved_at')->nullable()->after('approved_by_user_id');
            $table->timestamp('connected_at')->nullable()->after('approved_at');

            $table->index('direction');
            $table->index('integration_client_id');
            $table->index('approved_by_user_id');

            $table->foreign('integration_client_id')
                ->references('id')
                ->on('integration_clients')
                ->nullOnDelete();
            $table->foreign('approved_by_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('integrations', function (Blueprint $table): void {
            $table->dropForeign(['integration_client_id']);
            $table->dropForeign(['approved_by_user_id']);
            $table->dropIndex(['direction']);
            $table->dropIndex(['integration_client_id']);
            $table->dropIndex(['approved_by_user_id']);
            $table->dropColumn([
                'direction',
                'integration_client_id',
                'approved_by_user_id',
                'approved_at',
                'connected_at',
            ]);
        });
    }
};
