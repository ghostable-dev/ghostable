<?php

namespace App\Messaging\Middleware;

use App\Messaging\MessagingServiceProvider;
use Closure;
use Illuminate\Support\Facades\RateLimiter;

class ThrottleMailDelivery
{
    public function __construct(private readonly int $sleepMilliseconds = 200)
    {
    }

    public function handle($job, Closure $next)
    {
        $this->acquire(
            MessagingServiceProvider::MAIL_MINUTE_KEY,
            MessagingServiceProvider::MAILS_PER_MINUTE,
            60
        );

        $this->acquire(
            MessagingServiceProvider::MAIL_SECOND_KEY,
            MessagingServiceProvider::MAILS_PER_SECOND,
            1
        );

        return $next($job);
    }

    private function acquire(string $key, int $maxAttempts, int $decaySeconds): void
    {
        while (! RateLimiter::attempt($key, $maxAttempts, static fn () => true, $decaySeconds)) {
            $wait = RateLimiter::availableIn($key);
            $sleep = $wait > 0 ? $wait * 1000 : $this->sleepMilliseconds;

            usleep($sleep * 1000);
        }
    }
}
