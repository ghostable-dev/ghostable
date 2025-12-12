<?php

declare(strict_types=1);

namespace App\Integration\Support\Oauth;

use App\Integration\Contracts\OauthProviderHandler;
use App\Integration\Entities\VantaSettings;
use App\Integration\Enums\IntegrationStatus;
use App\Integration\Models\Integration;
use App\Integration\Support\IntegrationKey;
use App\Organization\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpKernel\Exception\HttpException;

class VantaOauthHandler implements OauthProviderHandler
{
    public function connect(Organization $organization, Integration $integration): RedirectResponse
    {
        // For Vanta, we use client_credentials and do not require a browser redirect.
        // Redirect back to callback with a flag to trigger token exchange.
        $callback = URL::route('integrations.oauth.callback', [
            'provider' => IntegrationKey::VANTA,
            'exchange' => 1,
        ]);

        return redirect()->to($callback);
    }

    public function handleCallback(Request $request, Organization $organization, Integration $integration): RedirectResponse
    {
        $settings = $integration->settings instanceof VantaSettings
            ? $integration->settings
            : VantaSettings::defaults();

        $payload = $this->exchangeToken($settings);

        $integration->forceFill([
            'status' => IntegrationStatus::Active,
            'secure_settings' => [
                'access_token' => $payload['access_token'] ?? null,
                'expires_at' => $payload['expires_at'] ?? null,
                'scope' => $settings->scope,
            ],
        ])->save();

        return redirect()->route('organization.settings.integrations')
            ->with('flash', ['message' => 'Vanta connected', 'level' => 'success']);
    }

    /**
     * Perform client_credentials token exchange and return payload.
     *
     * @return array{access_token?:string,expires_at?:string}
     */
    public function exchangeToken(VantaSettings $settings): array
    {
        $tokenUrl = config('vanta.token_url', 'https://api.vanta.com/oauth/token');
        $clientId = config('vanta.client_id');
        $clientSecret = config('vanta.client_secret');
        $scope = $settings->scope ?? config('vanta.default_scope', 'connectors.self:read-resource connectors.self:write-resource');

        if (! $clientId || ! $clientSecret) {
            Log::warning('Vanta OAuth missing client credentials');

            throw new HttpException(400, 'Vanta client credentials missing');
        }

        $response = Http::asJson()->post($tokenUrl, [
            'grant_type' => 'client_credentials',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'scope' => $scope,
        ]);

        if ($response->failed()) {
            Log::warning('Vanta token exchange failed', ['body' => $response->json()]);

            throw new HttpException(
                $response->status(),
                'Vanta token exchange failed: '.($response->json('error_description') ?? 'Unknown error')
            );
        }

        $payload = $response->json();
        $accessToken = $payload['access_token'] ?? null;
        $expiresIn = $payload['expires_in'] ?? null;

        return [
            'access_token' => $accessToken,
            'expires_at' => $expiresIn ? Carbon::now()->addSeconds((int) $expiresIn)->toIso8601String() : null,
        ];
    }
}
