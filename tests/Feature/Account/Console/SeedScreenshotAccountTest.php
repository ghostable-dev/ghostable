<?php

declare(strict_types=1);

use App\Account\Models\User;
use App\Billing\Enums\BillingPolicy;
use App\Billing\Enums\Plan;
use App\Core\Models\Activity;
use App\Environment\Enums\EnvironmentKeyReshareRequestStatus;
use App\Environment\Models\EnvironmentKeyReshareRequest;
use App\Environment\Models\EnvironmentSecret;
use App\Organization\Enums\OrganizationRole;
use App\Organization\Models\Organization;
use Illuminate\Support\Facades\Hash;

beforeEach(function (): void {
    $this->artisan('migrate:fresh', ['--force' => true])->assertSuccessful();
    config()->set('audit_webhook_receiver.driver', 'null');
});

test('screenshot seed command creates the canonical northstar account', function (): void {
    $this->artisan('app:seed-screenshot-account', ['--force' => true])
        ->assertSuccessful();

    $organization = Organization::query()
        ->with([
            'users',
            'projects.environments',
            'invites',
            'integrations',
            'integrationClients',
            'auditWebhooks.deliveries',
        ])
        ->where('slug', 'northstar-labs')
        ->sole();

    expect($organization->name)->toBe('Northstar Labs');
    expect($organization->billing_policy)->toBe(BillingPolicy::MANUAL_OVERRIDE);
    expect($organization->plan_override)->toBe(Plan::ENTERPRISE);
    expect($organization->plan)->toBe(Plan::ENTERPRISE);

    $avery = User::query()->where('email', 'avery@northstar.test')->sole();
    expect(Hash::check('password', $avery->password))->toBeTrue();

    $membersByEmail = $organization->users->keyBy('email');

    expect($organization->users)->toHaveCount(7);
    expect($membersByEmail['morgan@northstar.test']->pivot->role)->toBe(OrganizationRole::ADMIN->value);
    expect($membersByEmail['riley@northstar.test']->pivot->role)->toBe(OrganizationRole::DEVELOPER->value);
    expect($membersByEmail['sam@northstar.test']->pivot->role)->toBe(OrganizationRole::DEVELOPER->value);
    expect($membersByEmail['jordan@northstar.test']->pivot->role)->toBe(OrganizationRole::DEVELOPER_READ_ONLY->value);
    expect($membersByEmail['casey@northstar.test']->pivot->role)->toBe(OrganizationRole::BILLING_ONLY->value);
    expect($membersByEmail['taylor@northstar.test']->pivot->role)->toBe(OrganizationRole::AUDITOR->value);

    expect($organization->projects)->toHaveCount(3);
    expect($organization->projects->flatMap->environments)->toHaveCount(7);
    expect($organization->invites)->toHaveCount(2);
    expect($organization->integrations->pluck('key')->sort()->values()->all())
        ->toBe(['drata', 'slack', 'vanta']);
    expect($organization->integrationClients)->toHaveCount(1);
    expect($organization->auditWebhooks)->toHaveCount(1);
    expect($organization->auditWebhooks->first()?->deliveries)->toHaveCount(3);

    expect($organization->users->pluck('email')->every(
        static fn (string $email): bool => str_ends_with($email, '@northstar.test')
    ))->toBeTrue();
    expect($organization->invites->pluck('email')->every(
        static fn (string $email): bool => str_ends_with($email, '@northstar.test')
    ))->toBeTrue();
    expect($organization->name)->not->toContain('Ghostable');
    expect($organization->users->pluck('name')->join(' '))->not->toContain('Ghostable');
});

test('screenshot seed command resets the database before rebuilding fixtures', function (): void {
    $otherUser = $this->createUser('Unaffected User', 'unaffected@example.com');
    $otherOrganization = $this->createOrganization('Unrelated Org', $otherUser);

    $this->artisan('app:seed-screenshot-account', ['--force' => true])
        ->assertSuccessful();
    $this->artisan('app:seed-screenshot-account', ['--force' => true])
        ->assertSuccessful();

    expect(Organization::query()->where('slug', 'northstar-labs')->count())->toBe(1);
    expect(User::query()->where('email', 'avery@northstar.test')->count())->toBe(1);
    expect(User::query()->where('email', 'like', '%@northstar.test')->count())->toBe(7);
    expect(Organization::query()->whereKey($otherOrganization->id)->exists())->toBeFalse();
    expect(User::query()->whereKey($otherUser->id)->exists())->toBeFalse();
    expect(EnvironmentKeyReshareRequest::query()->count())->toBe(1);
});

test('flagship environment contains rich screenshot data', function (): void {
    $this->artisan('app:seed-screenshot-account', ['--force' => true])
        ->assertSuccessful();

    $organization = Organization::query()->where('slug', 'northstar-labs')->sole();
    $project = $organization->projects()->where('name', 'Control Plane')->sole();
    $environment = $project->environments()->where('name', 'production')->sole();

    $databaseUrl = EnvironmentSecret::query()
        ->where('environment_id', $environment->id)
        ->where('name', 'DATABASE_URL')
        ->sole();

    $featureFlag = EnvironmentSecret::query()
        ->where('environment_id', $environment->id)
        ->where('name', 'FEATURE_PAYMENTS_ENABLED')
        ->sole();

    expect($environment->keys()->count())->toBe(2);
    expect($environment->deploymentTokens()->count())->toBe(2);
    expect($environment->envSecrets()->count())->toBeGreaterThanOrEqual(6);
    expect($databaseUrl->versions()->count())->toBe(3);
    expect($featureFlag->versions()->count())->toBe(3);
    expect($featureFlag->version)->toBe(3);
    expect(Activity::query()->forEnvironment($environment)->count())->toBeGreaterThan(0);
    expect(
        Activity::query()
            ->forEnvironment($environment)
            ->where('event', 'rollback')
            ->exists()
    )->toBeTrue();

    $pendingRequest = EnvironmentKeyReshareRequest::query()
        ->where('organization_id', $organization->id)
        ->where('status', EnvironmentKeyReshareRequestStatus::Pending)
        ->sole();

    expect($pendingRequest->environment->name)->toBe('qa');
    expect($pendingRequest->targetUser->email)->toBe('jordan@northstar.test');
});
