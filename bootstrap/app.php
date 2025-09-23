<?php

use App\Api\Jobs\FoldUsageCounters;
use App\Messaging\Commands\RunSeriesCampaignCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule) {
        $schedule->job(FoldUsageCounters::class)->everyMinute();
        $schedule->command(RunSeriesCampaignCommand::class, ['name' => 'onboarding'])->hourlyAt(7);
    })
    ->withCommands([
        RunSeriesCampaignCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->throttleApi();
        $middleware->validateCsrfTokens(except: [
            'stripe/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
