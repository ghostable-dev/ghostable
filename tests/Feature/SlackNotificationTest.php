<?php

use App\Integration\Integrations\Slack\SlackChannel;
use App\Integration\Integrations\Slack\SlackClient;
use App\Integration\Integrations\Slack\SlackNotifiable;
use App\Organization\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

afterEach(function () {
    Mockery::close();
});

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
    $organization = Organization::factory()->create([
        'slack_enabled' => true,
        'slack_webhook_url' => 'https://hooks.slack.com/services/test',
    ]);

    $client = Mockery::mock(SlackClient::class);
    $client->expects()->sendWebhook('https://hooks.slack.com/services/test', 'notify')->once();

    $channel = new SlackChannel($client);

    $notifiable = new class {};

    $notification = new class($organization) extends Notification implements SlackNotifiable
    {
        public function __construct(public Organization $organization) {}

        public function toSlack(object $notifiable): string
        {
            return 'notify';
        }

        public function forOrganization(): Organization
        {
            return $this->organization;
        }
    };

    $channel->send($notifiable, $notification);
});
