<?php

namespace App\Organization\Livewire;

use App\Integration\Models\IntegrationClient;
use App\Organization\Models\Organization;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class OrganizationIntegrationsEdit extends Component
{
    use WithFileUploads;

    public IntegrationClient $integrationClient;

    public string $name = '';

    public string $key = '';

    public string $redirectUris = '';

    public array $defaultScopes = [];

    public string $landingPage = '';

    public string $description = '';

    public ?TemporaryUploadedFile $logo = null;

    public ?string $statusMessage = null;

    public string $statusLevel = 'info';

    #[Computed]
    public function organization(): Organization
    {
        return Auth::user()->currentOrganization();
    }

    #[Computed]
    public function canAccessIntegrations(): bool
    {
        return (bool) ($this->organization->features->integrations ?? false)
            || (bool) $this->organization->is_partner;
    }

    public function mount(string $client): void
    {
        abort_if($this->organization->usesDesktopLicensing(), 403);

        $this->integrationClient = IntegrationClient::query()
            ->where('owner_organization_id', $this->organization->id)
            ->whereKey($client)
            ->firstOrFail();

        $this->name = $this->integrationClient->name;
        $this->key = $this->integrationClient->key;
        $this->redirectUris = $this->stringifyList($this->integrationClient->redirect_uris ?? []);
        $this->defaultScopes = $this->integrationClient->default_scopes ?? [];
        $this->landingPage = $this->integrationClient->landing_page_url ?? '';
        $this->description = $this->integrationClient->description ?? '';
    }

    public function updateIntegrationClient(): void
    {
        $this->authorize('manageSettings', $this->organization);

        if (! $this->canAccessIntegrations()) {
            return;
        }

        $partnerRequired = (bool) $this->organization->is_partner;
        $allowedScopes = array_keys($this->availableScopes());
        $payload = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'key' => ['required', 'string', 'max:64', 'alpha_dash', Rule::in([$this->integrationClient->key])],
            'redirectUris' => ['required', 'string'],
            'defaultScopes' => ['required', 'array', 'min:1'],
            'defaultScopes.*' => ['string', Rule::in($allowedScopes)],
            'landingPage' => [Rule::requiredIf($partnerRequired), 'nullable', 'string', 'max:255', 'url'],
            'description' => ['required', 'string', 'max:2000'],
            'logo' => [
                Rule::requiredIf(empty($this->integrationClient->logo_path)),
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
                'dimensions:ratio=1/1,min_width=512,min_height=512',
            ],
        ]);

        $redirectUris = $this->parseList($payload['redirectUris'] ?? '');
        if (empty($redirectUris)) {
            $this->addError('redirectUris', 'At least one redirect URI is required.');

            return;
        }

        if (! $this->validateRedirectUris($redirectUris)) {
            return;
        }

        $this->integrationClient->forceFill([
            'name' => $payload['name'],
            'redirect_uris' => $redirectUris,
            'default_scopes' => $payload['defaultScopes'] ?? [],
            'landing_page_url' => ($payload['landingPage'] ?? '') ?: null,
            'description' => ($payload['description'] ?? '') ?: null,
        ])->save();

        if ($this->logo) {
            $logoPath = $this->storeLogo($this->integrationClient->id);

            $this->integrationClient->forceFill([
                'logo_path' => $logoPath,
            ])->save();
        }

        $this->statusMessage = 'Integration client updated.';
        $this->statusLevel = 'success';
    }

    protected function storeLogo(string $clientId): string
    {
        if ($this->integrationClient->logo_path) {
            Storage::disk('public')->delete($this->integrationClient->logo_path);
        }

        $extension = $this->logo?->getClientOriginalExtension() ?: 'png';
        $filename = Str::random(40).'.'.$extension;

        return $this->logo->storeAs("integrations/{$clientId}", $filename, 'public');
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

    protected function parseList(string $input): array
    {
        if (trim($input) === '') {
            return [];
        }

        $items = preg_split('/[\r\n,]+/', trim($input));

        return array_values(array_unique(array_filter(array_map('trim', $items), fn ($value) => $value !== '')));
    }

    protected function stringifyList(array $items): string
    {
        return implode(PHP_EOL, $items);
    }

    protected function validateRedirectUris(array $redirectUris): bool
    {
        foreach ($redirectUris as $uri) {
            if (! filter_var($uri, FILTER_VALIDATE_URL)) {
                $this->addError('redirectUris', 'Redirect URIs must be valid URLs.');

                return false;
            }

            $scheme = parse_url($uri, PHP_URL_SCHEME);
            if (! in_array($scheme, ['http', 'https'], true)) {
                $this->addError('redirectUris', 'Redirect URIs must use http or https.');

                return false;
            }

            $isLocalhost = $this->isLocalhostUrl($uri);
            if ($scheme !== 'https' && ! $isLocalhost) {
                $this->addError('redirectUris', 'Non-local redirect URIs must use https.');

                return false;
            }

            if ($this->integrationClient->publish_status === IntegrationClient::PUBLISH_STATUS_PUBLISHED
                && ($scheme !== 'https' || $isLocalhost)) {
                $this->addError('redirectUris', 'Published integrations require public https redirect URIs.');

                return false;
            }
        }

        return true;
    }

    protected function isLocalhostUrl(string $uri): bool
    {
        $host = parse_url($uri, PHP_URL_HOST);

        return in_array($host, ['localhost', '127.0.0.1', '::1'], true);
    }

    public function render()
    {
        return view('organization.organization-integrations-edit', [
            'availableScopes' => $this->availableScopes(),
        ]);
    }
}
