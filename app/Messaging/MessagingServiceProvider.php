<?php

namespace App\Messaging;

use App\Messaging\Campaigns\Broadcast\PostPublished;
use App\Messaging\Campaigns\Drip\CliSetupNudge;
use App\Messaging\Campaigns\Drip\CliSetupReminder;
use App\Messaging\Campaigns\Drip\InviteMembersNudge;
use App\Messaging\Campaigns\Drip\InviteMembersReminder;
use App\Messaging\Campaigns\Drip\OrganizationSetupNudge;
use App\Messaging\Campaigns\Drip\OrganizationSetupReminder;
use App\Messaging\Commands\RunBroadcastCampaignCommand;
use App\Messaging\Commands\RunSeriesCampaignCommand;
use App\Messaging\Listeners\MarkMessageAsSent;
use App\Messaging\Registry\CampaignRegistry;
use App\Messaging\Registry\SeriesRegistry;
use App\Messaging\Series\OnboardingSeries;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class MessagingServiceProvider extends ServiceProvider
{
    public const MAIL_GLOBAL_LIMITER = 'mail-global';

    protected $commands = [
        RunBroadcastCampaignCommand::class,
        RunSeriesCampaignCommand::class,
    ];

    protected $drips = [
        OrganizationSetupNudge::class,
        OrganizationSetupReminder::class,
        CliSetupNudge::class,
        CliSetupReminder::class,
        InviteMembersNudge::class,
        InviteMembersReminder::class,
    ];

    protected $broadcasts = [
        PostPublished::class,
    ];

    public function boot(): void
    {
        RateLimiter::for(self::MAIL_GLOBAL_LIMITER, fn () => [
            Limit::perMinute(100)->by(self::MAIL_GLOBAL_LIMITER),
        ]);

        Event::listen(MessageSent::class, MarkMessageAsSent::class);
    }

    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands($this->commands);
        }

        $this->registerCampaigns();

        $this->registerSeries();
    }

    protected function registerCampaigns(): void
    {
        $this->app->singleton(CampaignRegistry::class, fn () => tap(new CampaignRegistry, function (CampaignRegistry $r) {
            foreach ($this->drips as $cls) {
                $r->register($this->app->make($cls));
            }
            foreach ($this->broadcasts as $cls) {
                $r->registerBroadcast($cls);
            }
        }));
    }

    protected function registerSeries(): void
    {
        $this->app->singleton(SeriesRegistry::class, function () {
            $m = new SeriesRegistry;
            $m->register(OnboardingSeries::make());

            return $m;
        });
    }
}
