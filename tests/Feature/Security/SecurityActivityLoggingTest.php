<?php

use App\Account\Models\User;
use App\Auth\Livewire\ForgotPassword;
use App\Auth\Livewire\Login;
use App\Auth\Livewire\ResetPassword;
use App\Auth\Notifications\ResetPasswordNotification;
use App\Billing\Enums\Plan;
use App\Core\Models\Activity;
use App\Organization\Actions\CreatePermissionOverride;
use App\Organization\Actions\UpdateOrganizationMemberRole;
use App\Organization\Enums\OrganizationPermission;
use App\Organization\Enums\OrganizationRole;
use App\Project\Livewire\ProjectAccessManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\RecoveryCode;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

function createTwoFactorUser(): array
{
    $provider = app(TwoFactorAuthenticationProvider::class);
    $secret = $provider->generateSecretKey();
    $code = app(Google2FA::class)->getCurrentOtp($secret);

    $user = User::factory()->create([
        'two_factor_secret' => encrypt($secret),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => encrypt(json_encode([RecoveryCode::generate()])),
    ]);

    $user->markEmailAsVerified();
    $user->save();

    return [$user, $code];
}

test('successful login logs activity', function () {
    $user = User::factory()->create();

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login');

    $activity = Activity::query()
        ->where('log_name', 'user')
        ->where('event', 'login')
        ->where('subject_id', $user->id)
        ->latest()
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->causer_id)->toBe((string) $user->id);
});

test('failed login logs activity', function () {
    $user = User::factory()->create();

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors('email');

    $activity = Activity::query()
        ->where('log_name', 'user')
        ->where('event', 'login_failed')
        ->where('subject_id', $user->id)
        ->latest()
        ->first();

    expect($activity)->not->toBeNull();
    expect(data_get($activity->properties, 'reason'))->toBe('invalid_credentials');
});

test('web two factor challenge is logged', function () {
    [$user] = createTwoFactorUser();

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login')
        ->assertRedirect(route('two-factor.login', absolute: false));

    $activity = Activity::query()
        ->where('log_name', 'user')
        ->where('event', 'mfa_challenge')
        ->where('subject_id', $user->id)
        ->latest()
        ->first();

    expect($activity)->not->toBeNull();
    expect(data_get($activity->properties, 'source'))->toBe('web');
});

test('web two factor failures are logged', function () {
    [$user] = createTwoFactorUser();

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login');

    $this->post('/two-factor-challenge', [
        'code' => '000000',
    ])->assertStatus(302)->assertSessionHasErrors();

    $activity = Activity::query()
        ->where('log_name', 'user')
        ->where('event', 'failed_mfa')
        ->where('subject_id', $user->id)
        ->latest()
        ->first();

    expect($activity)->not->toBeNull();
    expect(data_get($activity->properties, 'source'))->toBe('web');
});

test('web two factor successes are logged', function () {
    [$user, $code] = createTwoFactorUser();

    Livewire::test(Login::class)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login');

    $this->post('/two-factor-challenge', [
        'code' => $code,
    ])->assertRedirect('/dashboard');

    $activity = Activity::query()
        ->where('log_name', 'user')
        ->where('event', 'mfa_succeeded')
        ->where('subject_id', $user->id)
        ->latest()
        ->first();

    expect($activity)->not->toBeNull();
    expect(data_get($activity->properties, 'source'))->toBe('web');
});

test('password reset activity is logged', function () {
    Notification::fake();

    $user = User::factory()->create();

    Livewire::test(ForgotPassword::class)
        ->set('email', $user->email)
        ->call('sendPasswordResetLink');

    $requestActivity = Activity::query()
        ->where('log_name', 'user')
        ->where('event', 'password_reset_requested')
        ->where('subject_id', $user->id)
        ->latest()
        ->first();

    expect($requestActivity)->not->toBeNull();

    Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user) {
        Livewire::test(ResetPassword::class, ['token' => $notification->token])
            ->set('email', $user->email)
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('resetPassword')
            ->assertRedirect(route('login', absolute: false));

        return true;
    });

    $resetActivity = Activity::query()
        ->where('log_name', 'user')
        ->where('event', 'password_reset')
        ->where('subject_id', $user->id)
        ->latest()
        ->first();

    expect($resetActivity)->not->toBeNull();
});

test('cli two factor challenge is logged', function () {
    [$user] = createTwoFactorUser();

    $this->postJson('/api/v2/cli/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertOk();

    $activity = Activity::query()
        ->where('log_name', 'user')
        ->where('event', 'mfa_challenge')
        ->where('subject_id', $user->id)
        ->latest()
        ->first();

    expect($activity)->not->toBeNull();
    expect(data_get($activity->properties, 'source'))->toBe('cli');
});

test('cli two factor failures are logged', function () {
    [$user] = createTwoFactorUser();

    $this->postJson('/api/v2/cli/login', [
        'email' => $user->email,
        'password' => 'password',
        'code' => '000000',
    ])->assertStatus(422);

    $activity = Activity::query()
        ->where('log_name', 'user')
        ->where('event', 'failed_mfa')
        ->where('subject_id', $user->id)
        ->latest()
        ->first();

    expect($activity)->not->toBeNull();
    expect(data_get($activity->properties, 'source'))->toBe('cli');
});

test('cli two factor successes are logged', function () {
    [$user, $code] = createTwoFactorUser();

    $this->postJson('/api/v2/cli/login', [
        'email' => $user->email,
        'password' => 'password',
        'code' => $code,
    ])->assertOk();

    $activity = Activity::query()
        ->where('log_name', 'user')
        ->where('event', 'mfa_succeeded')
        ->where('subject_id', $user->id)
        ->latest()
        ->first();

    expect($activity)->not->toBeNull();
    expect(data_get($activity->properties, 'source'))->toBe('cli');
});

test('admin access is logged once per session', function () {
    $user = $this->createUser('Admin', 'rucci.joe@gmail.com');

    $this->actingAs($user);

    $this->get(route('filament.admin.pages.dashboard'))->assertOk();
    $this->get(route('filament.admin.pages.dashboard'))->assertOk();

    $count = Activity::query()
        ->where('log_name', 'user')
        ->where('event', 'admin_access')
        ->where('subject_id', $user->id)
        ->count();

    expect($count)->toBe(1);
});

test('role changes are logged', function () {
    $owner = $this->createUser('Owner', 'owner@ghostable.test');
    $member = $this->createUser('Member', 'member@ghostable.test');
    $organization = $this->createOrganization('Spooky Org', $owner, [$member], Plan::ENTERPRISE);

    UpdateOrganizationMemberRole::handle(
        member: $member,
        organization: $organization,
        role: OrganizationRole::ADMIN,
        actor: $owner
    );

    $activity = Activity::query()
        ->where('log_name', 'user')
        ->where('event', 'role_changed')
        ->where('subject_id', $member->id)
        ->latest()
        ->first();

    expect($activity)->not->toBeNull();
    expect(data_get($activity->properties, 'role.from'))->toBe(OrganizationRole::DEVELOPER->value);
    expect(data_get($activity->properties, 'role.to'))->toBe(OrganizationRole::ADMIN->value);
});

test('permission overrides are logged on grant and revoke', function () {
    $owner = $this->createUser('Owner', 'owner@ghostable.test');
    $member = $this->createUser('Member', 'member@ghostable.test');
    $organization = $this->createOrganization('Spooky Org', $owner, [$member], Plan::ENTERPRISE);
    $organization->features = $organization->features->withOverrides([
        'advanced_permissions' => true,
    ]);
    $organization->save();
    $project = $this->createProject('Proton Pack', $organization);
    $project->update(['is_restricted' => false]);

    expect(Gate::forUser($owner)->allows('manageAccessControls', $organization))->toBeTrue();

    app(CreatePermissionOverride::class)->handle(
        user: $member,
        target: $project,
        permission: OrganizationPermission::ManageProjectSettings,
        actor: $owner
    );

    $grantActivity = Activity::query()
        ->where('log_name', 'user')
        ->where('event', 'permission_override_granted')
        ->where('subject_id', $member->id)
        ->latest()
        ->first();

    expect($grantActivity)->not->toBeNull();
    expect(data_get($grantActivity->properties, 'permission'))->toBe(OrganizationPermission::ManageProjectSettings->value);

    $override = $project->permissionOverrides()->first();

    Livewire::actingAs($owner)
        ->test(ProjectAccessManager::class, ['project' => $project])
        ->set('overrideToRemoveId', $override->id)
        ->call('removeOverride');

    $revokeActivity = Activity::query()
        ->where('log_name', 'user')
        ->where('event', 'permission_override_revoked')
        ->where('subject_id', $member->id)
        ->latest()
        ->first();

    expect($revokeActivity)->not->toBeNull();
});
