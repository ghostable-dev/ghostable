<?php

declare(strict_types=1);

namespace App\Integration\Entities;

use Spatie\LaravelData\Data;

class SlackSettings extends Data
{
    public readonly ?string $channel;

    public readonly bool $send_activity;

    public function __construct(?string $channel = null, bool $send_activity = true)
    {
        $this->channel = $channel ?? config('services.slack.notifications.channel');
        $this->send_activity = $send_activity;
    }

    public static function defaults(): self
    {
        return new self(
            channel: config('services.slack.notifications.channel'),
            send_activity: true,
        );
    }
}
