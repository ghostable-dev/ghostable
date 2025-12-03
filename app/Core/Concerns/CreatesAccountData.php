<?php

namespace App\Core\Concerns;

use App\Account\Actions\RegisterUser;
use App\Account\Models\User;
use App\Billing\Enums\Plan;
use App\Crypto\Actions\RegisterDevice;
use App\Crypto\Models\Device;
use App\Environment\Actions\CreateEnv;
use App\Environment\Actions\CreateEnvironmentKey;
use App\Environment\Actions\StoreEnvironmentKeyEnvelope;
use App\Environment\Actions\StoreEnvironmentSecret;
use App\Environment\Actions\Token\CreateDeploymentToken;
use App\Environment\Actions\Token\CreateEnvToken;
use App\Environment\Enums\EnvironmentType;
use App\Environment\Models\DeploymentToken;
use App\Environment\Models\Environment;
use App\Environment\Models\EnvironmentKey;
use App\Environment\Variable\Registry\VariableRegistry;
use App\Organization\Actions\CreateInvite;
use App\Organization\Actions\CreateOrganization;
use App\Organization\Enums\OrganizationRole;
use App\Organization\Models\Organization;
use App\Project\Models\Project;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laravel\Sanctum\NewAccessToken;

trait CreatesAccountData
{
    protected function createUser(string $name, string $email): User
    {
        $user = app(RegisterUser::class)->handle([
            'name' => $name,
            'email' => $email,
            'password' => 'password',
        ]);

        $user->markEmailAsVerified();
        $user->save();

        return $user->fresh();
    }

    protected function createOrganization(
        string $name,
        User $owner,
        array $members = [],
        ?Plan $planOverride = null
    ): Organization {

        $organization = app(CreateOrganization::class)->handle(
            name: $name,
            owner: $owner,
            planOverride: $planOverride
        );

        foreach ($members as $member) {
            $member->organizationMembership()->assignToOrganization(organization: $organization, role: OrganizationRole::DEVELOPER);
        }

        return $organization->fresh();
    }

    protected function createInvite(
        Organization $organization,
        User $sender,
        string $email,
        OrganizationRole $role = OrganizationRole::DEVELOPER
    ): void {
        CreateInvite::handle(
            organization: $organization,
            user: $sender,
            email: $email,
            role: $role
        );
    }

    protected function createZeroKnowledgeProject(string $name, Organization $organization): Project
    {
        return Project::factory()
            ->forOrganization($organization)
            ->create([
                'name' => $name,
            ]);
    }

    protected function createProject(string $name, Organization $organization): Project
    {
        return Project::factory()
            ->forOrganization($organization)
            ->create([
                'name' => $name,
            ]);
    }

    protected function createEnvironment(
        string $name,
        EnvironmentType $type,
        Project $project
    ): Environment {
        return app(CreateEnv::class)->handle(
            name: $name,
            type: $type,
            project: $project
        );
    }

    protected function createSecrets(
        Environment $env,
        User $createdBy,
    ): NewAccessToken {
        return resolve(CreateEnvToken::class)->handle(
            name: 'test-token',
            environment: $env,
            user: $createdBy
        );
    }

    protected function createDevice(User $user, string $name, string $platform = 'macos'): Device
    {
        /** @var RegisterDevice $registerDevice */
        $registerDevice = resolve(RegisterDevice::class);

        $device = $registerDevice->handle(
            user: $user,
            publicKey: base64_encode(random_bytes(32)),
            publicSigningKey: base64_encode(random_bytes(32)),
            name: $name,
            platform: $platform,
        );

        $device->update([
            'app_version' => '1.'.random_int(0, 9).'.'.random_int(0, 9),
            'last_seen_at' => now()->subMinutes(random_int(1, 240)),
        ]);

        return $device->fresh();
    }

    protected function createEnvironmentKeyWithEnvelope(
        Environment $environment,
        Device $createdByDevice,
        array $recipients = []
    ): EnvironmentKey {
        /** @var CreateEnvironmentKey $creator */
        $creator = resolve(CreateEnvironmentKey::class);

        $environmentKey = $creator->handle(
            environment: $environment,
            fingerprint: hash('sha256', Str::random(32)),
            createdByDevice: $createdByDevice,
            version: 1,
        );

        $envelopeRecipients = $recipients ?: [
            [
                'id' => (string) $createdByDevice->id,
                'type' => 'device',
                'label' => $createdByDevice->name ?? 'Primary device',
            ],
        ];

        /** @var StoreEnvironmentKeyEnvelope $storeEnvelope */
        $storeEnvelope = resolve(StoreEnvironmentKeyEnvelope::class);

        $storeEnvelope->handle($environmentKey, [
            'ciphertext_b64' => base64_encode(random_bytes(64)),
            'nonce_b64' => base64_encode(random_bytes(24)),
            'alg' => 'xchacha20-poly1305',
            'version' => '1',
            'aad_b64' => base64_encode(json_encode([
                'env' => $environment->name,
                'project' => $environment->project->name,
            ], JSON_THROW_ON_ERROR)),
            'recipients' => $envelopeRecipients,
        ]);

        return $environmentKey->fresh(['envelope']);
    }

    protected function createDeploymentToken(
        string $name,
        Environment $environment,
        ?User $createdBy = null,
        int $expiresAfter = 90
    ): DeploymentToken {
        /** @var CreateDeploymentToken $creator */
        $creator = resolve(CreateDeploymentToken::class);

        $publicKey = base64_encode(random_bytes(SODIUM_CRYPTO_BOX_PUBLICKEYBYTES));

        $result = $creator->handle(
            name: $name,
            environment: $environment,
            publicKey: $publicKey,
            user: $createdBy,
            expiresAfter: $expiresAfter,
        );

        return $result->token->fresh();
    }

    protected function createZeroKnowledgeVariables(
        Environment $env,
        int $amount = 5,
        ?User $createdBy = null,
        array $defaults = [
            'alg' => 'xchacha20poly1305',
            'is_vapor_secret' => false,
            'is_commented' => false,
            // Dev-only HMAC key used to make seed runs idempotent without leaking any real keys:
            'dev_hmac_key' => 'ghostable-dev-seed-hmac',
        ],
    ): void {
        /** @var StoreEnvironmentSecret $store */
        $store = resolve(StoreEnvironmentSecret::class);

        // Try to pull realistic keys + suggested values; fall back to synthetic keys if registry empty.
        $defs = collect(optional(resolve(VariableRegistry::class))->all() ?? []);

        for ($i = 0; $i < $amount; $i++) {
            if ($defs->isNotEmpty()) {
                $def = $defs->random();
                $name = $def->key();
                $plaintext = empty($def->suggestedValues())
                    ? Str::random(16)
                    : collect($def->suggestedValues())->random();
            } else {
                $name = 'SEED_'.Str::upper(Str::random(8));
                $plaintext = Str::random(16);
            }

            // ---- Fabricate a client-side style encrypted packet (DEV/DEMO ONLY) ----
            // We DO NOT do real encryption here—this is seed/demo data. Ciphertext/nonce are random bytes.
            $ciphertext = base64_encode(random_bytes(48));
            $nonce = base64_encode(random_bytes(24));
            $alg = $defaults['alg'] ?? 'xchacha20poly1305';

            // AAD matches your client contract: org / project / env / name
            $aad = [
                'org' => $env->project->organization->slug
                    ?? (string) $env->project->organization_id,
                'project' => $env->project->slug
                    ?? (string) $env->project_id,
                'env' => $env->slug,
                'name' => $name,
            ];

            // Claims include a DEV-only HMAC so repeated runs are no-ops unless plaintext changes.
            $claims = [
                'hmac' => hash_hmac(
                    'sha256',
                    $plaintext,
                    (string) ($defaults['dev_hmac_key'] ?? 'ghostable-dev-seed-hmac')
                ),
                'meta' => [
                    'value_length' => strlen($plaintext),
                    'is_vapor_secret' => (bool) ($defaults['is_vapor_secret'] ?? false),
                    'is_commented' => (bool) ($defaults['is_commented'] ?? false),
                ],
            ];

            // Minimal client signature marker (demo). Your action doesn’t validate it, so any string is fine.
            $clientSig = 'dev-seed';

            $packet = [
                'name' => $name,
                'ciphertext' => $ciphertext,
                'nonce' => $nonce,
                'alg' => $alg,
                'aad' => $aad,
                'claims' => $claims,
                'client_sig' => $clientSig,
                // Optional knobs you can pass per-call if you want to override flags/length:
                // 'line_bytes'     => strlen($plaintext),
                // 'is_vapor_secret'=> true/false,
                // 'is_commented'   => true/false,
                // 'if_version'     => int|null,
            ];

            // Mirror defaults into packet only if explicitly provided in $defaults (keeps action’s normalization).
            foreach (['line_bytes', 'is_vapor_secret', 'is_commented', 'if_version'] as $k) {
                if (Arr::has($defaults, $k)) {
                    $packet[$k] = $defaults[$k];
                }
            }

            // Persist via your new action (handles upsert, meta normalization, and versioning).
            $store->handle($env, $packet, $createdBy);
        }
    }
}
