<?php

declare(strict_types=1);

namespace App\Integration\Http\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Integration\Enums\IntegrationDirection;
use App\Integration\Enums\IntegrationStatus;
use App\Integration\Models\Integration;
use App\Integration\Models\IntegrationAuthorizationCode;
use App\Integration\Models\IntegrationClient;
use App\Organization\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InboundOauthController extends Controller
{
    public function showAuthorize(Request $request): View
    {
        $payload = $this->validateAuthorizeRequest($request, requireOrganization: false);
        $client = $this->resolveClient($payload['client_id']);
        $this->ensureRedirectUriAllowed($client, $payload['redirect_uri']);

        if (! empty($payload['organization_id'])) {
            $organization = Organization::query()->find($payload['organization_id']);

            if ($organization) {
                $this->ensureClientAvailableForOrganization($client, $organization);
            }
        }

        $scopes = $this->resolveScopes($client, $payload['scope'] ?? null);
        $user = $request->user();
        $organizations = $user?->organizations()
            ->select('organizations.id', 'organizations.name', 'organizations.slug')
            ->orderBy('organizations.name')
            ->get() ?? collect();
        $selectedOrganizationId = $payload['organization_id'] ?? $user?->currentOrganization()?->id;

        return view('integrations.oauth.authorize', [
            'client' => $client,
            'scopes' => $scopes,
            'scopeString' => implode(' ', $scopes),
            'redirectUri' => $payload['redirect_uri'],
            'state' => $payload['state'] ?? null,
            'responseType' => $payload['response_type'],
            'codeChallenge' => $payload['code_challenge'] ?? null,
            'codeChallengeMethod' => $payload['code_challenge_method'] ?? null,
            'organizations' => $organizations,
            'selectedOrganizationId' => $selectedOrganizationId,
        ]);
    }

    public function approve(Request $request): RedirectResponse
    {
        $payload = $this->validateAuthorizeRequest($request, requireOrganization: true);
        $client = $this->resolveClient($payload['client_id']);
        $this->ensureRedirectUriAllowed($client, $payload['redirect_uri']);

        if ($request->string('action')->toString() === 'deny') {
            return $this->deny($payload['redirect_uri'], $payload['state'] ?? null);
        }

        $organization = Organization::query()->findOrFail($payload['organization_id']);
        $this->authorize('manageSettings', $organization);
        $this->ensureClientAvailableForOrganization($client, $organization);

        $scopes = $this->resolveScopes($client, $payload['scope'] ?? null);
        $code = $this->generateToken();
        $expiresAt = Carbon::now()->addSeconds((int) config('integrations.oauth.code_ttl', 600));

        IntegrationAuthorizationCode::query()->create([
            'integration_client_id' => $client->id,
            'organization_id' => $organization->id,
            'user_id' => $request->user()->id,
            'code_hash' => hash('sha256', $code),
            'scopes' => $scopes,
            'redirect_uri' => $payload['redirect_uri'],
            'state' => $payload['state'] ?? null,
            'code_challenge' => $payload['code_challenge'] ?? null,
            'code_challenge_method' => $payload['code_challenge_method'] ?? null,
            'expires_at' => $expiresAt,
        ]);

        $integration = Integration::withTrashed()->firstOrNew([
            'organization_id' => $organization->id,
            'key' => $client->key,
            'direction' => IntegrationDirection::Incoming->value,
        ]);

        if ($integration->trashed()) {
            $integration->restore();
        }

        $integration->fill([
            'status' => IntegrationStatus::Pending,
            'integration_client_id' => $client->id,
            'approved_by_user_id' => $request->user()->id,
            'approved_at' => Carbon::now(),
        ])->save();

        $redirect = $this->appendQueryParams($payload['redirect_uri'], [
            'code' => $code,
            'state' => $payload['state'] ?? null,
        ]);

        return redirect()->away($redirect);
    }

    protected function validateAuthorizeRequest(Request $request, bool $requireOrganization): array
    {
        $organizationRule = $requireOrganization ? ['required', 'uuid'] : ['nullable', 'uuid'];

        return $request->validate([
            'client_id' => ['required', 'string'],
            'redirect_uri' => ['required', 'string'],
            'response_type' => ['required', Rule::in(['code'])],
            'scope' => ['nullable', 'string'],
            'state' => ['nullable', 'string'],
            'organization_id' => $organizationRule,
            'code_challenge' => ['nullable', 'string'],
            'code_challenge_method' => ['nullable', Rule::in(['plain', 'S256'])],
        ]);
    }

    protected function resolveClient(string $clientId): IntegrationClient
    {
        $client = IntegrationClient::query()
            ->where('client_id', $clientId)
            ->where('status', 'active')
            ->first();

        if (! $client) {
            throw new HttpException(404, 'Client not found');
        }

        return $client;
    }

    protected function ensureRedirectUriAllowed(IntegrationClient $client, string $redirectUri): void
    {
        $allowed = $client->redirect_uris ?? [];

        if (! in_array($redirectUri, $allowed, true)) {
            throw new HttpException(400, 'Redirect URI not allowed');
        }

        $scheme = parse_url($redirectUri, PHP_URL_SCHEME);
        $isLocalhost = $this->isLocalhostUrl($redirectUri);

        if ($scheme !== 'https' && ! $isLocalhost) {
            throw new HttpException(400, 'Redirect URI must use https unless localhost');
        }

        if ($client->publish_status === IntegrationClient::PUBLISH_STATUS_PUBLISHED
            && ($scheme !== 'https' || $isLocalhost)) {
            throw new HttpException(400, 'Published integrations require a public https redirect URI');
        }
    }

    protected function ensureClientAvailableForOrganization(IntegrationClient $client, Organization $organization): void
    {
        if ($client->owner_organization_id === $organization->id) {
            return;
        }

        if ($client->publish_status !== IntegrationClient::PUBLISH_STATUS_PUBLISHED) {
            throw new HttpException(403, 'Integration is not available for this organization');
        }
    }

    protected function resolveScopes(IntegrationClient $client, ?string $scopeString): array
    {
        $requested = $this->splitScopes($scopeString);
        $defaultScopes = $client->default_scopes ?? [];

        if (empty($requested)) {
            $requested = $defaultScopes;
        }

        if (! empty($defaultScopes)) {
            $diff = array_diff($requested, $defaultScopes);
            if (! empty($diff)) {
                throw new HttpException(400, 'Invalid scope requested');
            }
        }

        return array_values(array_unique($requested));
    }

    protected function splitScopes(?string $scopeString): array
    {
        if (! $scopeString) {
            return [];
        }

        return array_values(array_filter(Arr::wrap(preg_split('/\s+/', trim($scopeString)))));
    }

    protected function appendQueryParams(string $uri, array $params): string
    {
        $query = http_build_query(array_filter($params, fn ($value) => $value !== null));
        $separator = str_contains($uri, '?') ? '&' : '?';

        return $uri.$separator.$query;
    }

    protected function isLocalhostUrl(string $uri): bool
    {
        $host = parse_url($uri, PHP_URL_HOST);

        return in_array($host, ['localhost', '127.0.0.1', '::1'], true);
    }

    protected function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    protected function deny(string $redirectUri, ?string $state): RedirectResponse
    {
        $redirect = $this->appendQueryParams($redirectUri, [
            'error' => 'access_denied',
            'state' => $state,
        ]);

        return redirect()->away($redirect);
    }
}
