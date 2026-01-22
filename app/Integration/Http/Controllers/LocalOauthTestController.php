<?php

declare(strict_types=1);

namespace App\Integration\Http\Controllers;

use App\Core\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LocalOauthTestController extends Controller
{
    public function show(Request $request): View
    {
        $session = $request->session()->get('oauth_test', []);

        return view('integrations.local-oauth-test', [
            'clientId' => $session['client_id'] ?? '',
            'clientSecret' => $session['client_secret'] ?? '',
            'redirectUri' => $session['redirect_uri'] ?? route('local.oauth-test.callback'),
            'selectedScopes' => $session['scopes'] ?? ['organization.read'],
            'availableScopes' => $this->availableScopes(),
            'tokenResponse' => $session['token_response'] ?? null,
            'organizationResponse' => $session['organization_response'] ?? null,
            'error' => $session['error'] ?? null,
        ]);
    }

    public function start(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'client_id' => ['required', 'string'],
            'client_secret' => ['required', 'string'],
            'redirect_uri' => ['required', 'url'],
            'scopes' => ['required', 'array', 'min:1'],
            'scopes.*' => ['string'],
        ]);

        $state = Str::random(32);

        $request->session()->put('oauth_test', [
            'client_id' => $payload['client_id'],
            'client_secret' => $payload['client_secret'],
            'redirect_uri' => $payload['redirect_uri'],
            'scopes' => $payload['scopes'],
            'state' => $state,
        ]);

        return redirect()->route('integrations.oauth.authorize', [
            'client_id' => $payload['client_id'],
            'redirect_uri' => $payload['redirect_uri'],
            'response_type' => 'code',
            'scope' => implode(' ', $payload['scopes']),
            'state' => $state,
        ]);
    }

    public function callback(Request $request): RedirectResponse
    {
        $session = $request->session()->get('oauth_test', []);
        $code = $request->string('code')->toString();
        $state = $request->string('state')->toString();

        $request->session()->forget('oauth_test.token_response');
        $request->session()->forget('oauth_test.organization_response');
        $request->session()->forget('oauth_test.error');

        if (($session['state'] ?? null) && $state !== ($session['state'] ?? null)) {
            $request->session()->put('oauth_test.error', 'State mismatch.');

            return redirect()->route('local.oauth-test.show');
        }

        if ($code === '') {
            $request->session()->put('oauth_test.error', 'Missing authorization code.');

            return redirect()->route('local.oauth-test.show');
        }

        $tokenResponse = Http::asForm()->post(route('integrations.oauth.token'), [
            'grant_type' => 'authorization_code',
            'client_id' => $session['client_id'] ?? '',
            'client_secret' => $session['client_secret'] ?? '',
            'code' => $code,
            'redirect_uri' => $session['redirect_uri'] ?? '',
        ]);

        $request->session()->put('oauth_test.token_response', [
            'status' => $tokenResponse->status(),
            'body' => $tokenResponse->json(),
        ]);

        $accessToken = $tokenResponse->json('access_token');
        if ($accessToken) {
            $organizationResponse = Http::withToken($accessToken)
                ->get(url('/api/integrations/v1/organization'));

            $request->session()->put('oauth_test.organization_response', [
                'status' => $organizationResponse->status(),
                'body' => $organizationResponse->json(),
            ]);
        }

        return redirect()->route('local.oauth-test.show');
    }

    protected function availableScopes(): array
    {
        return [
            'organization.read' => 'Organization metadata',
            'members.read' => 'Members list',
            'projects.read' => 'Projects list',
            'audits.read' => 'Audit logs',
        ];
    }
}
