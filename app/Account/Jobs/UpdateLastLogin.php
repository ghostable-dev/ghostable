<?php

namespace App\Account\Jobs;

use App\Account\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateLastLogin implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $userId,
    ) {}

    public function handle(): void
    {
        $user = User::query()->whereKey($this->userId)->first();

        if (! $user) {
            return;
        }

        $user->forceFill([
            'last_login' => now(),
        ])->saveQuietly();
    }
}
