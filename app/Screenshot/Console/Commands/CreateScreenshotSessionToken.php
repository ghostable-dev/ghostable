<?php

declare(strict_types=1);

namespace App\Screenshot\Console\Commands;

use App\Account\Models\User;
use Illuminate\Console\Command;
use Throwable;

final class CreateScreenshotSessionToken extends Command
{
    protected $signature = 'app:screenshot-session-token
        {--email=avery@northstar.test : Screenshot account email address.}
        {--name=desktop-screenshot : Personal access token name to rotate.}
        {--json : Output the created token payload as JSON.}';

    protected $description = 'Create a fresh personal access token for the local screenshot account.';

    public function handle(): int
    {
        $email = (string) $this->option('email');
        $name = (string) $this->option('name');

        try {
            $user = User::query()->where('email', $email)->sole();
            $user->tokens()->where('name', $name)->delete();

            $plainTextToken = $user->createToken($name)->plainTextToken;
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $payload = [
            'email' => $user->email,
            'name' => $name,
            'token' => $plainTextToken,
        ];

        if ($this->option('json')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        }

        $this->line($plainTextToken);

        return self::SUCCESS;
    }
}
