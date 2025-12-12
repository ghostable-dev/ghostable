<?php

declare(strict_types=1);

namespace App\Integration\Http\Controllers;

use App\Integration\Contracts\OauthProviderHandler;
use App\Integration\Enums\IntegrationStatus;
use App\Integration\Models\Integration;
use App\Integration\Support\IntegrationKey;
use App\Integration\Support\IntegrationSettingsRegistry;
use App\Organization\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OauthIntegrationController extends Controller
{
    /**
     * Map providers to their handlers.
     *
     * @var array<string, class-string<OauthProviderHandler>>
     */
    protected array $handlers = [
        IntegrationKey::DRATA => \App\Integration\Support\Oauth\DrataOauthHandler::class,
        IntegrationKey::VANTA => \App\Integration\Support\Oauth\VantaOauthHandler::class,
    ];

    public function connect(Request $request, string $provider): RedirectResponse
    {
        $organization = $this->currentOrganization();
        $handler = $this->resolveHandler($provider);

        Gate::authorize('manageSettings', $organization);

        $integration = $this->ensureIntegration($organization, $provider, IntegrationStatus::Pending);

        return $handler->connect($organization, $integration);
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        $organization = $this->currentOrganization();
        $handler = $this->resolveHandler($provider);

        Gate::authorize('manageSettings', $organization);

        $integration = $organization->integrations()
            ->withTrashed()
            ->where('key', $provider)
            ->firstOrFail();

        if ($integration->trashed()) {
            $integration->restore();
        }

        return $handler->handleCallback($request, $organization, $integration);
    }

    protected function currentOrganization(): Organization
    {
        $org = Auth::user()?->currentOrganization();

        if (! $org) {
            throw new NotFoundHttpException('Organization not found');
        }

        return $org;
    }

    protected function resolveHandler(string $provider): OauthProviderHandler
    {
        $handler = $this->handlers[$provider] ?? null;

        if (! $handler) {
            throw new NotFoundHttpException('Provider not supported');
        }

        return app($handler);
    }

    protected function ensureIntegration(Organization $organization, string $key, IntegrationStatus $status): Integration
    {
        $integration = Integration::withTrashed()
            ->firstOrNew([
                'organization_id' => $organization->id,
                'key' => $key,
            ]);

        if ($integration->trashed()) {
            $integration->restore();
        }

        $integration->fill([
            'settings' => $this->defaultSettingsFor($key),
            'status' => $status,
        ])->save();

        return $integration;
    }

    protected function defaultSettingsFor(string $key): mixed
    {
        $dataClass = IntegrationSettingsRegistry::resolve($key);

        if (! $dataClass) {
            return [];
        }

        if (method_exists($dataClass, 'defaults')) {
            return $dataClass::defaults();
        }

        return $dataClass::from([]);
    }
}
