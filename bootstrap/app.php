<?php

use App\Api\Usage\Jobs\FoldUsageCounters;
use App\Auth\Console\Commands\PruneCliLoginSessionsCommand;
use App\Auth\Http\Middleware\EnsureUserIsActive;
use App\Console\Commands\FoldDesktopUpdateAnalyticsCommand;
use App\Console\Commands\PruneDesktopUpdateAnalyticsCommand;
use App\Environment\Console\Commands\ReconcileEnvironmentKeyReshareRequestsCommand;
use App\Integration\Integrations\Vanta\Jobs\SyncUsers as SyncVantaUsers;
use App\Messaging\Commands\RunSeriesCampaignCommand;
use App\Organization\Console\Commands\InstallLocalAuditWebhookCapturesTableCommand;
use App\Organization\Console\Commands\PruneLocalAuditWebhookCapturesCommand;
use App\Organization\Console\Commands\PruneOrganizationAuditWebhookDeliveriesCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        // api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule) {
        $schedule->job(FoldUsageCounters::class)->everyMinute();
        $schedule->command(FoldDesktopUpdateAnalyticsCommand::class)->everyFiveMinutes()->withoutOverlapping();
        $schedule->command(RunSeriesCampaignCommand::class, ['name' => 'onboarding'])->hourlyAt(7);
        $schedule->command(PruneCliLoginSessionsCommand::class)->everyFiveMinutes();
        $schedule->command(PruneDesktopUpdateAnalyticsCommand::class)->daily()->withoutOverlapping()->onOneServer();
        if (filter_var((string) env('ENV_KEY_RESHARE_RECONCILE_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN)) {
            $schedule
                ->command(ReconcileEnvironmentKeyReshareRequestsCommand::class, ['--pending-only', '--no-notify'])
                ->hourly()
                ->withoutOverlapping()
                ->onOneServer();
        }
        if (filter_var((string) env('AUDIT_WEBHOOK_DELIVERY_PRUNE_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN)) {
            $schedule
                ->command(PruneOrganizationAuditWebhookDeliveriesCommand::class, [
                    '--days' => max(1, (int) env('AUDIT_WEBHOOK_DELIVERY_RETENTION_DAYS', 30)),
                ])
                ->daily()
                ->withoutOverlapping()
                ->onOneServer();
        }
        if (config('audit_webhook_receiver.driver') === 'database') {
            $schedule
                ->command(PruneLocalAuditWebhookCapturesCommand::class, [
                    '--days' => max(1, (int) config('audit_webhook_receiver.retention_days', 14)),
                ])
                ->daily()
                ->withoutOverlapping()
                ->onOneServer();
        }
        $schedule->job(new SyncVantaUsers(requirePaidPlan: true))->everyTwoHours();
    })
    ->withCommands([
        RunSeriesCampaignCommand::class,
        PruneCliLoginSessionsCommand::class,
        PruneOrganizationAuditWebhookDeliveriesCommand::class,
        PruneLocalAuditWebhookCapturesCommand::class,
        InstallLocalAuditWebhookCapturesTableCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->throttleApi();
        $middleware->validateCsrfTokens(except: [
            'stripe/*',
            'local/audit-webhooks/ingest',
            'desktop/update-events',
        ]);
        $middleware->appendToGroup('web', EnsureUserIsActive::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
