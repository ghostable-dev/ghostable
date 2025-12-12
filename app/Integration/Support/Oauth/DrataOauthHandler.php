<?php

declare(strict_types=1);

namespace App\Integration\Support\Oauth;

use App\Integration\Contracts\OauthProviderHandler;
use App\Integration\Enums\IntegrationStatus;
use App\Integration\Models\Integration;
use App\Integration\Support\IntegrationKey;
use App\Organization\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class DrataOauthHandler implements OauthProviderHandler
{
    public function connect(Organization $organization, Integration $integration): RedirectResponse
    {
        // Placeholder: in a real flow, redirect to Drata's OAuth authorize URL.
        // For now we short-circuit back to our callback with a fake code.
        $callback = URL::route('integrations.oauth.callback', [
            'provider' => IntegrationKey::DRATA,
            'code' => 'demo-code',
        ]);

        return redirect()->to($callback);
    }

    public function handleCallback(Request $request, Organization $organization, Integration $integration): RedirectResponse
    {
        $integration->forceFill([
            'status' => IntegrationStatus::Active,
            'secure_settings' => [
                'access_token' => $request->string('code')->toString() ?: 'demo-token',
            ],
        ])->save();

        return redirect()->route('organization.settings.integrations')
            ->with('flash', ['message' => 'Drata connected', 'level' => 'success']);
    }
}
