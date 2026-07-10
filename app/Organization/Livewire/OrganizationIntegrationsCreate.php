<?php

namespace App\Organization\Livewire;

use App\Integration\Models\IntegrationClient;
use App\Organization\Models\Organization;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class OrganizationIntegrationsCreate extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $key = '';

    public string $redirectUris = '';

    public array $defaultScopes = ['organization.read'];

    public string $landingPage = '';

    public string $description = '';

    public ?TemporaryUploadedFile $logo = null;

    public ?string $clientId = null;

    public ?string $clientSecret = null;

    public ?string $statusMessage = null;

    public string $statusLevel = 'info';

    public function mount(): void
    {
        abort_if($this->organization->usesDesktopLicensing(), 403);
    }

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

    public function updatedName(): void
    {
        if ($this->key === '') {
            $this->key = Str::slug($this->name);
        }
    }

    public function createIntegrationClient(): void
    {
        $this->authorize('manageSettings', $this->organization);

        if (! $this->canAccessIntegrations()) {
            return;
        }

        $partnerRequired = (bool) $this->organization->is_partner;
        $allowedScopes = array_keys($this->availableScopes());
        $payload = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'key' => ['required', 'string', 'max:64', 'alpha_dash', Rule::unique('integration_clients', 'key')],
            'redirectUris' => ['required', 'string'],
            'defaultScopes' => ['required', 'array', 'min:1'],
            'defaultScopes.*' => ['string', Rule::in($allowedScopes)],
            'landingPage' => [Rule::requiredIf($partnerRequired), 'nullable', 'string', 'max:255', 'url'],
            'description' => ['required', 'string', 'max:2000'],
            'logo' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
                'dimensions:ratio=1/1,min_width=512,min_height=512',
            ],
        ]);

        $clientId = Str::random(32);
        $clientSecret = Str::random(64);

        $redirectUris = $this->parseList($payload['redirectUris'] ?? '');
        if (empty($redirectUris)) {
            $this->addError('redirectUris', 'At least one redirect URI is required.');

            return;
        }

        if (! $this->validateRedirectUris($redirectUris)) {
            return;
        }
        $scopes = $payload['defaultScopes'] ?? [];

        $client = IntegrationClient::query()->create([
            'name' => $payload['name'],
            'key' => $payload['key'],
            'client_id' => $clientId,
            'client_secret_hash' => Hash::make($clientSecret),
            'redirect_uris' => $redirectUris ?: null,
            'default_scopes' => $scopes ?: null,
            'status' => 'active',
            'owner_organization_id' => $this->organization->id,
            'publish_status' => IntegrationClient::PUBLISH_STATUS_DRAFT,
            'landing_page_url' => ($payload['landingPage'] ?? '') ?: null,
            'description' => ($payload['description'] ?? '') ?: null,
        ]);

        if ($this->logo) {
            $logoPath = $this->storeLogo($client->id);

            $client->forceFill([
                'logo_path' => $logoPath,
            ])->save();
        }

        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->statusMessage = 'Integration client created.';
        $this->statusLevel = 'success';
    }

    protected function storeLogo(string $clientId): string
    {
        $extension = $this->logo?->getClientOriginalExtension() ?: 'png';
        $filename = Str::random(40).'.'.$extension;

        return $this->logo->storeAs("integrations/{$clientId}", $filename, 'public');
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

            if ($scheme !== 'https' && ! $this->isLocalhostUrl($uri)) {
                $this->addError('redirectUris', 'Non-local redirect URIs must use https.');

                return false;
            }
        }

        return true;
    }

    protected function parseList(string $input): array
    {
        if (trim($input) === '') {
            return [];
        }

        $items = preg_split('/[\r\n,]+/', trim($input));

        return array_values(array_unique(array_filter(array_map('trim', $items), fn ($value) => $value !== '')));
    }

    protected function isLocalhostUrl(string $uri): bool
    {
        $host = parse_url($uri, PHP_URL_HOST);

        return in_array($host, ['localhost', '127.0.0.1', '::1'], true);
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

    public function render()
    {
        return view('organization.organization-integrations-create', [
            'availableScopes' => $this->availableScopes(),
        ]);
    }
}
