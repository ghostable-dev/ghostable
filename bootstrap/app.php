<?php

use App\Api\Usage\Jobs\FoldUsageCounters;
use App\Auth\Console\Commands\PruneCliLoginSessionsCommand;
use App\Auth\Http\Middleware\EnsureUserIsActive;
use App\Environment\Console\Commands\ReconcileEnvironmentKeyReshareRequestsCommand;
use App\Integration\Integrations\Vanta\Jobs\SyncUsers as SyncVantaUsers;
use App\Messaging\Commands\RunSeriesCampaignCommand;
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
        $schedule->command(RunSeriesCampaignCommand::class, ['name' => 'onboarding'])->hourlyAt(7);
        $schedule->command(PruneCliLoginSessionsCommand::class)->everyFiveMinutes();
        if (filter_var((string) env('ENV_KEY_RESHARE_RECONCILE_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN)) {
            $schedule
                ->command(ReconcileEnvironmentKeyReshareRequestsCommand::class, ['--pending-only' => true, '--no-notify' => true])
                ->hourly()
                ->withoutOverlapping()
                ->onOneServer();
        }
        $schedule->job(new SyncVantaUsers(requirePaidPlan: true))->everyTwoHours();
    })
    ->withCommands([
        RunSeriesCampaignCommand::class,
        PruneCliLoginSessionsCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->throttleApi();
        $middleware->validateCsrfTokens(except: [
            'stripe/*',
        ]);
        $middleware->appendToGroup('web', EnsureUserIsActive::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
