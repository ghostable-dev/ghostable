<?php

use App\Account\Models\User;
use App\Filament\Widgets\OrganizationStats;
use App\Filament\Widgets\UserStats;
use App\Organization\Models\Organization;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('app.timezone', 'UTC');
});

afterEach(function (): void {
    Carbon::setTestNow();
});

function statValuesByLabel(array $stats): array
{
    return collect($stats)
        ->mapWithKeys(fn (Stat $stat) => [(string) $stat->getLabel() => $stat->getValue()])
        ->all();
}

function createOrganizationAt(string $name, ?User $owner, Carbon $timestamp): Organization
{
    /** @var Organization $organization */
    $organization = Organization::query()->create([
        'name' => $name,
    ]);

    $organization->forceFill([
        'owner_id' => $owner?->id,
        'created_at' => $timestamp,
        'updated_at' => $timestamp,
    ])->saveQuietly();

    return $organization;
}

function createUserAt(string $name, string $email, Carbon $timestamp, ?Carbon $verifiedAt = null): User
{
    /** @var User $user */
    $user = User::query()->create([
        'name' => $name,
        'email' => $email,
        'password' => bcrypt('password'),
    ]);

    $user->forceFill([
        'email_verified_at' => $verifiedAt,
        'created_at' => $timestamp,
        'updated_at' => $timestamp,
    ])->saveQuietly();

    return $user;
}

it('updates organization stats when range changes', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-03-20 10:30:00', 'UTC'));

    $ownerA = createUserAt('Owner A', 'owner-a@example.com', Carbon::parse('2026-03-01 09:00:00', 'UTC'));
    $ownerB = createUserAt('Owner B', 'owner-b@example.com', Carbon::parse('2026-03-20 09:00:00', 'UTC'));

    createOrganizationAt('Org Month', $ownerA, Carbon::parse('2026-03-01 12:00:00', 'UTC'));
    createOrganizationAt('Org Month Ownerless', null, Carbon::parse('2026-03-10 08:00:00', 'UTC'));
    createOrganizationAt('Org Today', $ownerB, Carbon::parse('2026-03-20 09:10:00', 'UTC'));

    $widget = new class extends OrganizationStats
    {
        public function exposedStats(): array
        {
            return $this->getStats();
        }
    };

    $monthStats = statValuesByLabel($widget->exposedStats());

    expect($monthStats['Organizations (This month)'])->toBe(3)
        ->and($monthStats['Owners (This month)'])->toBe(2)
        ->and($monthStats['Without Owner (This month)'])->toBe(1);

    $widget->syncActivityRange('today');

    $todayStats = statValuesByLabel($widget->exposedStats());

    expect($todayStats['Organizations (Today)'])->toBe(1)
        ->and($todayStats['Owners (Today)'])->toBe(1)
        ->and($todayStats['Without Owner (Today)'])->toBe(0);
});

it('updates user stats when range changes', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-03-20 10:30:00', 'UTC'));

    createUserAt(
        'Monthly Verified',
        'monthly-verified@example.com',
        Carbon::parse('2026-03-02 10:00:00', 'UTC'),
        Carbon::parse('2026-03-02 10:05:00', 'UTC')
    );

    createUserAt(
        'Today Verified',
        'today-verified@example.com',
        Carbon::parse('2026-03-20 09:00:00', 'UTC'),
        Carbon::parse('2026-03-20 09:05:00', 'UTC')
    );

    createUserAt(
        'Today Unverified',
        'today-unverified@example.com',
        Carbon::parse('2026-03-20 09:15:00', 'UTC')
    );

    createUserAt(
        'Previous Month User',
        'previous-month@example.com',
        Carbon::parse('2026-02-20 09:15:00', 'UTC')
    );

    $widget = new class extends UserStats
    {
        public function exposedStats(): array
        {
            return $this->getStats();
        }
    };

    $monthStats = statValuesByLabel($widget->exposedStats());

    expect($monthStats['Users (This month)'])->toBe(3)
        ->and($monthStats['Verified (This month)'])->toBe(2)
        ->and($monthStats['Unverified (This month)'])->toBe(1);

    $widget->syncActivityRange('today');

    $todayStats = statValuesByLabel($widget->exposedStats());

    expect($todayStats['Users (Today)'])->toBe(2)
        ->and($todayStats['Verified (Today)'])->toBe(1)
        ->and($todayStats['Unverified (Today)'])->toBe(1);
});
