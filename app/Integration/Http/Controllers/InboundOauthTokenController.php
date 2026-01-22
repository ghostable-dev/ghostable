<?php

declare(strict_types=1);

namespace App\Integration\Http\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Integration\Enums\IntegrationDirection;
use App\Integration\Enums\IntegrationStatus;
use App\Integration\Models\Integration;
use App\Integration\Models\IntegrationAuthorizationCode;
use App\Integration\Models\IntegrationClient;
use App\Integration\Models\IntegrationToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class InboundOauthTokenController extends Controller
{
    public function token(Request $request): JsonResponse
    {
        $grantType = $request->string('grant_type')->toString();

        $payload = $request->validate([
            'grant_type' => ['required', Rule::in(['authorization_code', 'refresh_token'])],
            'client_id' => ['required', 'string'],
            'client_secret' => ['required', 'string'],
            'code' => ['required_if:grant_type,authorization_code', 'string'],
            'redirect_uri' => ['required_if:grant_type,authorization_code', 'string'],
            'code_verifier' => ['nullable', 'string'],
            'refresh_token' => ['required_if:grant_type,refresh_token', 'string'],
        ]);

        $client = $this->resolveClient($payload['client_id'], $payload['client_secret']);

        if ($grantType === 'authorization_code') {
            return $this->exchangeAuthorizationCode(
                $client,
                $payload['code'],
                $payload['redirect_uri'],
                $payload['code_verifier'] ?? null
            );
        }

        return $this->exchangeRefreshToken($client, $payload['refresh_token']);
    }

    public function revoke(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'client_id' => ['required', 'string'],
            'client_secret' => ['required', 'string'],
            'token' => ['required', 'string'],
        ]);

        $client = $this->resolveClient($payload['client_id'], $payload['client_secret']);
        $hash = hash('sha256', $payload['token']);

        $token = IntegrationToken::query()
            ->where('integration_client_id', $client->id)
            ->where(function ($query) use ($hash): void {
                $query->where('access_token_hash', $hash)
                    ->orWhere('refresh_token_hash', $hash);
            })
            ->first();

        if ($token && ! $token->revoked_at) {
            $token->forceFill(['revoked_at' => Carbon::now()])->save();
        }

        return response()->json(['revoked' => true]);
    }

    protected function exchangeAuthorizationCode(
        IntegrationClient $client,
        string $code,
        string $redirectUri,
        ?string $codeVerifier
    ): JsonResponse {
        $codeHash = hash('sha256', $code);

        $authorizationCode = IntegrationAuthorizationCode::query()
            ->where('integration_client_id', $client->id)
            ->where('code_hash', $codeHash)
            ->first();

        if (! $authorizationCode || $authorizationCode->consumed_at) {
            return $this->invalidGrant('Authorization code is invalid or already used.');
        }

        if ($authorizationCode->expires_at->isPast()) {
            return $this->invalidGrant('Authorization code has expired.');
        }

        if ($authorizationCode->redirect_uri !== $redirectUri) {
            return $this->invalidGrant('Redirect URI mismatch.');
        }

        if (! $this->verifyCodeChallenge($authorizationCode, $codeVerifier)) {
            return $this->invalidGrant('PKCE verification failed.');
        }

        $authorizationCode->forceFill(['consumed_at' => Carbon::now()])->save();

        $integration = Integration::withTrashed()->firstOrNew([
            'organization_id' => $authorizationCode->organization_id,
            'key' => $client->key,
            'direction' => IntegrationDirection::Incoming->value,
        ]);

        if ($integration->trashed()) {
            $integration->restore();
        }

        $integration->fill([
            'status' => IntegrationStatus::Active,
            'integration_client_id' => $client->id,
            'connected_at' => Carbon::now(),
        ])->save();

        $tokens = $this->issueTokens(
            $client,
            $integration,
            $authorizationCode->organization_id,
            $authorizationCode->user_id,
            $authorizationCode->scopes ?? []
        );

        return response()->json($tokens);
    }

    protected function exchangeRefreshToken(IntegrationClient $client, string $refreshToken): JsonResponse
    {
        $hash = hash('sha256', $refreshToken);

        $token = IntegrationToken::query()
            ->where('integration_client_id', $client->id)
            ->where('refresh_token_hash', $hash)
            ->first();

        if (! $token || $token->revoked_at) {
            return $this->invalidGrant('Refresh token is invalid or revoked.');
        }

        if ($token->refresh_token_expires_at && $token->refresh_token_expires_at->isPast()) {
            return $this->invalidGrant('Refresh token has expired.');
        }

        $tokens = $this->rotateTokens($token);

        return response()->json($tokens);
    }

    protected function issueTokens(
        IntegrationClient $client,
        Integration $integration,
        string $organizationId,
        ?string $userId,
        array $scopes
    ): array {
        $accessToken = $this->generateToken();
        $refreshToken = $this->generateToken();

        $accessTtl = (int) config('integrations.oauth.access_token_ttl', 3600);
        $refreshTtl = (int) config('integrations.oauth.refresh_token_ttl', 1209600);

        IntegrationToken::query()->create([
            'integration_client_id' => $client->id,
            'integration_id' => $integration->id,
            'organization_id' => $organizationId,
            'user_id' => $userId,
            'access_token_hash' => hash('sha256', $accessToken),
            'access_token_expires_at' => Carbon::now()->addSeconds($accessTtl),
            'refresh_token_hash' => hash('sha256', $refreshToken),
            'refresh_token_expires_at' => Carbon::now()->addSeconds($refreshTtl),
            'scopes' => $scopes,
            'token_suffix' => $this->tokenSuffix($accessToken),
        ]);

        return [
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => $accessTtl,
            'refresh_token' => $refreshToken,
            'scope' => implode(' ', $scopes),
        ];
    }

    protected function rotateTokens(IntegrationToken $token): array
    {
        $accessToken = $this->generateToken();
        $refreshToken = $this->generateToken();
        $accessTtl = (int) config('integrations.oauth.access_token_ttl', 3600);
        $refreshTtl = (int) config('integrations.oauth.refresh_token_ttl', 1209600);

        $token->forceFill([
            'access_token_hash' => hash('sha256', $accessToken),
            'access_token_expires_at' => Carbon::now()->addSeconds($accessTtl),
            'refresh_token_hash' => hash('sha256', $refreshToken),
            'refresh_token_expires_at' => Carbon::now()->addSeconds($refreshTtl),
            'token_suffix' => $this->tokenSuffix($accessToken),
        ])->save();

        return [
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => $accessTtl,
            'refresh_token' => $refreshToken,
            'scope' => implode(' ', $token->scopes ?? []),
        ];
    }

    protected function resolveClient(string $clientId, string $clientSecret): IntegrationClient
    {
        $client = IntegrationClient::query()
            ->where('client_id', $clientId)
            ->where('status', 'active')
            ->first();

        if (! $client || ! Hash::check($clientSecret, $client->client_secret_hash)) {
            abort(401, 'Invalid client credentials');
        }

        return $client;
    }

    protected function invalidGrant(string $message): JsonResponse
    {
        return response()->json([
            'error' => 'invalid_grant',
            'error_description' => $message,
        ], 400);
    }

    protected function verifyCodeChallenge(IntegrationAuthorizationCode $authorizationCode, ?string $verifier): bool
    {
        if (! $authorizationCode->code_challenge) {
            return true;
        }

        if (! $verifier) {
            return false;
        }

        $method = $authorizationCode->code_challenge_method ?? 'plain';

        if ($method === 'S256') {
            $digest = hash('sha256', $verifier, true);
            $computed = rtrim(strtr(base64_encode($digest), '+/', '-_'), '=');
        } else {
            $computed = $verifier;
        }

        return hash_equals($authorizationCode->code_challenge, $computed);
    }

    protected function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    protected function tokenSuffix(string $token): string
    {
        return Str::substr($token, -8);
    }
}
