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
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class VantaOauthHandler implements OauthProviderHandler
{
    public function connect(Organization $organization, Integration $integration): RedirectResponse
    {
        $settings = $integration->settings instanceof VantaSettings
            ? $integration->settings
            : VantaSettings::defaults();
        $redirectUri = URL::route('integrations.oauth.callback', [
            'provider' => IntegrationKey::VANTA,
        ]);
        $state = Str::random(40);
        // Vanta expects source_id to be the application's account identifier.
        $sourceId = $organization->id;

        session()->put($this->stateKey($organization, $integration), $state);
        session()->put($this->sourceKey($organization, $integration), $sourceId !== '' ? $sourceId : null);

        $clientId = config('vanta.client_id');
        if (! $clientId) {
            Log::warning('Vanta OAuth missing client ID');

            throw new HttpException(400, 'Vanta client credentials missing');
        }

        $authorizeUrl = (string) config('vanta.authorize_url', 'https://api.vanta.com/oauth/authorize');
        $query = http_build_query(array_filter([
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => $settings->scope,
            'state' => $state,
            'source_id' => $sourceId !== '' ? $sourceId : null,
        ], fn ($value) => $value !== null));

        return redirect()->away(rtrim($authorizeUrl, '?').'?'.$query);
    }

    public function handleCallback(Request $request, Organization $organization, Integration $integration): RedirectResponse
    {
        $settings = $integration->settings instanceof VantaSettings
            ? $integration->settings
            : VantaSettings::defaults();
        $expectedState = session()->pull($this->stateKey($organization, $integration));
        $sourceId = session()->pull($this->sourceKey($organization, $integration));
        $state = $request->string('state')->toString();
        $code = $request->string('code')->toString();

        if (! $expectedState || ! hash_equals($expectedState, $state)) {
            throw new HttpException(400, 'Invalid OAuth state.');
        }

        if ($code === '') {
            throw new HttpException(400, 'Missing authorization code.');
        }

        $redirectUri = URL::route('integrations.oauth.callback', [
            'provider' => IntegrationKey::VANTA,
        ]);

        $payload = $this->exchangeAuthorizationCode($code, $redirectUri, $settings, $sourceId);

        $integration->forceFill([
            'status' => IntegrationStatus::Active,
            'secure_settings' => [
                'access_token' => $payload['access_token'] ?? null,
                'refresh_token' => $payload['refresh_token'] ?? null,
                'expires_at' => $payload['expires_at'] ?? null,
                'scope' => $payload['scope'] ?? $settings->scope,
                'source_id' => $sourceId,
            ],
            'connected_at' => Carbon::now(),
        ])->save();

        return redirect()->route('organization.settings.integrations')
            ->with('flash', ['message' => 'Vanta connected', 'level' => 'success']);
    }

    /**
     * Exchange authorization code for tokens.
     *
     * @return array{access_token?:string,refresh_token?:string,expires_at?:string,scope?:string}
     */
    public function exchangeAuthorizationCode(string $code, string $redirectUri, VantaSettings $settings, ?string $sourceId): array
    {
        $tokenUrl = config('vanta.token_url', 'https://api.vanta.com/oauth/token');
        $clientId = config('vanta.client_id');
        $clientSecret = config('vanta.client_secret');

        if (! $clientId || ! $clientSecret) {
            Log::warning('Vanta OAuth missing client credentials');

            throw new HttpException(400, 'Vanta client credentials missing');
        }

        $response = Http::asForm()->post($tokenUrl, [
            'grant_type' => 'authorization_code',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code' => $code,
            'redirect_uri' => $redirectUri,
            'source_id' => $sourceId,
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
            'refresh_token' => $payload['refresh_token'] ?? null,
            'expires_at' => $expiresIn ? Carbon::now()->addSeconds((int) $expiresIn)->toIso8601String() : null,
            'scope' => $payload['scope'] ?? null,
        ];
    }

    /**
     * Refresh an access token using the stored refresh token.
     *
     * @return array{access_token?:string,refresh_token?:string,expires_at?:string,scope?:string}
     */
    public function refreshAccessToken(Integration $integration): array
    {
        $refreshToken = $integration->secure_settings['refresh_token'] ?? null;
        $settings = $integration->settings instanceof VantaSettings
            ? $integration->settings
            : VantaSettings::defaults();

        if (! $refreshToken) {
            throw new HttpException(400, 'Vanta refresh token missing.');
        }

        $tokenUrl = config('vanta.token_url', 'https://api.vanta.com/oauth/token');
        $clientId = config('vanta.client_id');
        $clientSecret = config('vanta.client_secret');

        if (! $clientId || ! $clientSecret) {
            Log::warning('Vanta OAuth missing client credentials');

            throw new HttpException(400, 'Vanta client credentials missing');
        }

        $response = Http::asForm()->post($tokenUrl, [
            'grant_type' => 'refresh_token',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken,
            'scope' => $settings->scope,
        ]);

        if ($response->failed()) {
            Log::warning('Vanta token refresh failed', ['body' => $response->json()]);

            throw new HttpException(
                $response->status(),
                'Vanta token refresh failed: '.($response->json('error_description') ?? 'Unknown error')
            );
        }

        $payload = $response->json();
        $accessToken = $payload['access_token'] ?? null;
        $expiresIn = $payload['expires_in'] ?? null;

        return [
            'access_token' => $accessToken,
            'refresh_token' => $payload['refresh_token'] ?? $refreshToken,
            'expires_at' => $expiresIn ? Carbon::now()->addSeconds((int) $expiresIn)->toIso8601String() : null,
            'scope' => $payload['scope'] ?? null,
        ];
    }

    protected function stateKey(Organization $organization, Integration $integration): string
    {
        return "vanta.oauth.state.{$organization->id}.{$integration->id}";
    }

    protected function sourceKey(Organization $organization, Integration $integration): string
    {
        return "vanta.oauth.source.{$organization->id}.{$integration->id}";
    }
}
