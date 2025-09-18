<?php

namespace App\Messaging;

use App\Messaging\Campaigns\CreateOrgOnboardingCampaign;
use App\Messaging\Commands\RunMessagingCampaign;
use App\Messaging\Commands\SendQueuedMessages;
use App\Messaging\Listeners\MarkMessageAsSent;
use App\Messaging\Registry\CampaignRegistry;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class MessagingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(MessageSent::class, MarkMessageAsSent::class);
    }

    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                RunMessagingCampaign::class,
                SendQueuedMessages::class,
            ]);
        }

        $this->app->singleton(CampaignRegistry::class, function () {
            $registry = new CampaignRegistry;
            $registry->register(new CreateOrgOnboardingCampaign);

            return $registry;
        });
    }
}
