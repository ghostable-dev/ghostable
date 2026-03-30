<?php

use App\Core\Models\Activity;
use App\Filament\Resources\Core\ApiActivity\ApiActivityResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('scopes records to api-related activity sources', function (): void {
    activity('variable')->event('push')->withProperties(['source' => 'cli'])->log('CLI pushed environment');
    activity('variable')->event('commented')->withProperties(['source' => 'desktop'])->log('Desktop commented variable');
    activity('variable')->event('environment_key_created')->withProperties(['source' => 'api'])->log('API created environment key');
    activity('variable')->event('deploy')->withProperties(['source' => 'deploy-api'])->log('Deploy API pulled environment');
    activity('user')->event('login')->withProperties(['source' => 'web'])->log('Web login');
    activity('notifications')->event('sent')->withProperties(['source' => 'cli'])->log('Notification dispatch');

    $descriptions = ApiActivityResource::applyApiScopeTo(Activity::query())
        ->pluck('description')
        ->all();

    expect($descriptions)
        ->toHaveCount(4)
        ->toContain('CLI pushed environment')
        ->toContain('Desktop commented variable')
        ->toContain('API created environment key')
        ->toContain('Deploy API pulled environment')
        ->not->toContain('Web login')
        ->not->toContain('Notification dispatch');
});

it('filters api activity by source', function (): void {
    activity('variable')->event('push')->withProperties(['source' => 'cli'])->log('CLI pushed environment');
    activity('variable')->event('deleted')->withProperties(['source' => 'desktop'])->log('Desktop deleted variable');

    $records = ApiActivityResource::applyApiScopeTo(Activity::query(), 'cli')->get();

    expect($records)->toHaveCount(1);
    expect(data_get($records->first()->properties, 'source'))->toBe('cli');
});

it('maps desktop source badges to the blue info color', function (): void {
    expect(ApiActivityResource::sourceColor('desktop'))->toBe('info')
        ->and(ApiActivityResource::sourceColor('cli'))->toBe('warning')
        ->and(ApiActivityResource::sourceColor('deploy-api'))->toBe('danger');
});

it('formats occurred on timestamps in the requested timezone', function (): void {
    $formatted = ApiActivityResource::formatOccurredOn(
        Carbon::parse('2026-03-20 18:00:00', 'UTC'),
        'America/Los_Angeles'
    );

    expect($formatted)->toBe('Mar 20, 2026 11:00 AM PDT');
});
