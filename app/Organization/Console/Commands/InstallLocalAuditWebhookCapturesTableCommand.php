<?php

declare(strict_types=1);

namespace App\Organization\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

final class InstallLocalAuditWebhookCapturesTableCommand extends Command
{
    protected $signature = 'local:audit-webhooks:install-captures-table';

    protected $description = 'Create the local audit webhook captures table when receiver driver is database.';

    public function handle(): int
    {
        $driver = strtolower(trim((string) config('audit_webhook_receiver.driver', 'null')));

        if ($driver !== 'database') {
            $this->info(sprintf(
                'Skipped. AUDIT_WEBHOOK_RECEIVER_DRIVER is "%s" (table only required for "database").',
                $driver === '' ? 'null' : $driver,
            ));

            return self::SUCCESS;
        }

        if (Schema::hasTable('local_audit_webhook_captures')) {
            $this->info('local_audit_webhook_captures table already exists.');

            return self::SUCCESS;
        }

        Schema::create('local_audit_webhook_captures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamp('received_at');
            $table->string('event_id')->nullable();
            $table->string('event_type')->nullable();
            $table->string('organization_id')->nullable();
            $table->string('http_method', 16);
            $table->text('request_url');
            $table->json('headers_json');
            $table->json('payload_json')->nullable();
            $table->longText('payload_raw')->nullable();
            $table->string('signature_header')->nullable();
            $table->string('timestamp_header')->nullable();
            $table->string('mode', 16)->default('ok');
            $table->unsignedSmallInteger('response_status')->default(202);
            $table->timestamps();

            $table->index('received_at', 'lawc_received_at_idx');
            $table->index(['event_type', 'received_at'], 'lawc_event_received_idx');
            $table->index(['organization_id', 'received_at'], 'lawc_org_received_idx');
        });

        $this->info('Created local_audit_webhook_captures table.');

        return self::SUCCESS;
    }
}
