<?php

use App\Core\Http\Middleware\NormalizeLivewireNotificationsPayload;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

it('normalizes array-valued filament notification flags in livewire payloads', function (): void {
    $payload = [
        [
            'snapshot' => json_encode([
                'data' => [
                    'isFilamentNotificationsComponent' => ['1'],
                    'other' => 'value',
                ],
            ], JSON_THROW_ON_ERROR),
            'updates' => [],
            'calls' => [],
            'memo' => [],
        ],
    ];

    $request = Request::create(
        '/livewire-abcd1234/update',
        'POST',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_LIVEWIRE' => 'true',
        ],
        json_encode(['components' => $payload], JSON_THROW_ON_ERROR),
    );

    $middleware = new NormalizeLivewireNotificationsPayload;

    $response = $middleware->handle($request, fn (): Response => new Response('ok'));

    expect($response->getStatusCode())->toBe(200);

    $snapshot = json_decode($request->input('components')[0]['snapshot'], associative: true, flags: JSON_THROW_ON_ERROR);

    expect($snapshot['data']['isFilamentNotificationsComponent'])->toBeTrue();
    expect($snapshot['data']['other'])->toBe('value');
});

it('does not mutate livewire payload when filament notification flag is not an array', function (): void {
    $payload = [
        [
            'snapshot' => json_encode([
                'data' => [
                    'isFilamentNotificationsComponent' => false,
                ],
            ], JSON_THROW_ON_ERROR),
            'updates' => [],
            'calls' => [],
            'memo' => [],
        ],
    ];

    $request = Request::create(
        '/livewire-abcd1234/update',
        'POST',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_LIVEWIRE' => 'true',
        ],
        json_encode(['components' => $payload], JSON_THROW_ON_ERROR),
    );

    $middleware = new NormalizeLivewireNotificationsPayload;

    $response = $middleware->handle($request, fn (): Response => new Response('ok'));

    expect($response->getStatusCode())->toBe(200);

    $snapshot = json_decode($request->input('components')[0]['snapshot'], associative: true, flags: JSON_THROW_ON_ERROR);

    expect($snapshot['data']['isFilamentNotificationsComponent'])->toBeFalse();
});
