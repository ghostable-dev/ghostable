<?php

use App\Account\Models\User;
use App\Api\Usage\Models\ApiUsageDaily;
use App\Filament\Widgets\DashboardOverviewStats;
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

function dashboardCreateUserWithActivity(
    string $name,
    string $email,
    Carbon $createdAt,
    ?Carbon $lastLoginAt = null
): User {
    /** @var User $user */
    $user = User::query()->create([
        'name' => $name,
        'email' => $email,
        'password' => bcrypt('password'),
    ]);

    $user->forceFill([
        'created_at' => $createdAt,
        'updated_at' => $createdAt,
        'last_login' => $lastLoginAt,
    ])->saveQuietly();

    return $user;
}

function dashboardCreateOrganizationAt(string $name, Carbon $createdAt): Organization
{
    /** @var Organization $organization */
    $organization = Organization::query()->create([
        'name' => $name,
    ]);

    $organization->forceFill([
        'created_at' => $createdAt,
        'updated_at' => $createdAt,
    ])->saveQuietly();

    return $organization;
}

function createDashboardApiDailyCount(Organization $organization, string $date, int $count, string $token): void
{
    ApiUsageDaily::query()->create([
        'organization_id' => $organization->id,
        'token_id' => $token,
        'method' => 'GET',
        'endpoint' => '/v1/health',
        'date' => $date,
        'count' => $count,
    ]);
}

/**
 * @return array<string, array{value: string, description: string|null, chart: array<int, int>|null}>
 */
function dashboardOverviewStatsByLabel(array $stats): array
{
    return collect($stats)
        ->mapWithKeys(function (Stat $stat): array {
            $description = $stat->getDescription();
            $normalizedDescription = is_null($description) ? null : (string) $description;

            return [
                (string) $stat->getLabel() => [
                    'value' => (string) $stat->getValue(),
                    'description' => $normalizedDescription,
                    'chart' => $stat->getChart(),
                ],
            ];
        })
        ->all();
}

it('shows month-scoped overview metrics with last-month comparisons', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-03-20 10:30:00', 'UTC'));

    $organization = dashboardCreateOrganizationAt('Ghostable', Carbon::parse('2026-03-01 09:00:00', 'UTC'));
    dashboardCreateOrganizationAt('Ghostable Two', Carbon::parse('2026-03-12 09:00:00', 'UTC'));
    dashboardCreateOrganizationAt('Ghostable Previous', Carbon::parse('2026-02-10 09:00:00', 'UTC'));

    dashboardCreateUserWithActivity(
        'March Login A',
        'march-login-a@example.com',
        Carbon::parse('2026-03-02 09:00:00', 'UTC'),
        Carbon::parse('2026-03-05 12:00:00', 'UTC')
    );
    dashboardCreateUserWithActivity(
        'March Login B',
        'march-login-b@example.com',
        Carbon::parse('2026-03-18 10:00:00', 'UTC'),
        Carbon::parse('2026-03-19 13:00:00', 'UTC')
    );
    dashboardCreateUserWithActivity(
        'March No Login',
        'march-no-login@example.com',
        Carbon::parse('2026-03-20 08:00:00', 'UTC')
    );
    dashboardCreateUserWithActivity(
        'February Baseline',
        'feb-baseline@example.com',
        Carbon::parse('2026-02-10 08:00:00', 'UTC'),
        Carbon::parse('2026-02-12 11:00:00', 'UTC')
    );

    createDashboardApiDailyCount($organization, '2026-03-02', 70, 'token-1');
    createDashboardApiDailyCount($organization, '2026-03-19', 80, 'token-2');
    createDashboardApiDailyCount($organization, '2026-02-03', 40, 'token-3');
    createDashboardApiDailyCount($organization, '2026-02-15', 50, 'token-4');

    $widget = new class extends DashboardOverviewStats
    {
        public function exposedStats(): array
        {
            return $this->getStats();
        }
    };

    $stats = dashboardOverviewStatsByLabel($widget->exposedStats());

    expect($stats['New Users (This month)']['value'])->toBe('3')
        ->and($stats['Logins (This month)']['value'])->toBe('2')
        ->and($stats['Organizations (This month)']['value'])->toBe('2')
        ->and($stats['API Calls (This month)']['value'])->toBe('150');

    expect($stats['New Users (This month)']['description'])->toBe('Up 200% vs last month')
        ->and($stats['Logins (This month)']['description'])->toBe('Up 100% vs last month')
        ->and($stats['Organizations (This month)']['description'])->toBe('Up 100% vs last month')
        ->and($stats['API Calls (This month)']['description'])->toBe('Up 67% vs last month');

    expect($stats['New Users (This month)']['chart'])->toHaveCount(20)
        ->and($stats['Logins (This month)']['chart'])->toHaveCount(20)
        ->and($stats['Organizations (This month)']['chart'])->toHaveCount(20)
        ->and($stats['API Calls (This month)']['chart'])->toHaveCount(20);
});
