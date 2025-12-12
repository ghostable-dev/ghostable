<?php

declare(strict_types=1);

namespace App\Integration\Contracts;

use App\Integration\Models\Integration;
use App\Organization\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

interface OauthProviderHandler
{
    /**
     * Kick off the OAuth flow for the provider.
     */
    public function connect(Organization $organization, Integration $integration): RedirectResponse;

    /**
     * Handle the provider callback and persist credentials/settings.
     */
    public function handleCallback(Request $request, Organization $organization, Integration $integration): RedirectResponse;
}
