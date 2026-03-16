<?php

declare(strict_types=1);

use App\Account\Models\User;
use Illuminate\Support\Facades\Artisan;

beforeEach(function (): void {
    config()->set('audit_webhook_receiver.driver', 'null');
});

test('screenshot session token command creates and rotates a named token for the northstar user', function (): void {
    Artisan::call('app:seed-screenshot-account', ['--force' => true]);

    Artisan::call('app:screenshot-session-token', [
        '--json' => true,
        '--email' => 'avery@northstar.test',
        '--name' => 'desktop-screenshot-device-link',
    ]);

    $firstPayload = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
    $avery = User::query()->where('email', 'avery@northstar.test')->sole();

    expect($firstPayload['email'])->toBe('avery@northstar.test');
    expect($firstPayload['name'])->toBe('desktop-screenshot-device-link');
    expect($firstPayload['token'])->toContain('|');
    expect($avery->tokens()->where('name', 'desktop-screenshot-device-link')->count())->toBe(1);

    Artisan::call('app:screenshot-session-token', [
        '--json' => true,
        '--email' => 'avery@northstar.test',
        '--name' => 'desktop-screenshot-device-link',
    ]);

    $secondPayload = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
    $avery->refresh();

    expect($secondPayload['token'])->not->toBe($firstPayload['token']);
    expect($avery->tokens()->where('name', 'desktop-screenshot-device-link')->count())->toBe(1);
});
