<?php

use App\Integration\Integrations\Slack\SlackChannel;
use App\Integration\Integrations\Slack\SlackClient;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Mockery;

it('sends a message via webhook', function () {
    Http::fake();

    $client = new SlackClient;
    $client->sendWebhook('https://hooks.slack.com/services/test', 'hello');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://hooks.slack.com/services/test'
            && $request['text'] === 'hello';
    });
});

it('sends a notification via the slack channel', function () {
    $client = Mockery::mock(SlackClient::class);
    $client->expects()->sendWebhook('https://hooks.slack.com/services/test', 'notify')->once();

    $channel = new SlackChannel($client);

    $notifiable = new class
    {
        use Notifiable;

        public function routeNotificationForSlack()
        {
            return 'https://hooks.slack.com/services/test';
        }
    };

    $notification = new class extends Notification
    {
        public function toSlack($notifiable)
        {
            return 'notify';
        }
    };

    $channel->send($notifiable, $notification);
});
