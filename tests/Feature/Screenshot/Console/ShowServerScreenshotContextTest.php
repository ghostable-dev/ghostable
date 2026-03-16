<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

beforeEach(function (): void {
    config()->set('audit_webhook_receiver.driver', 'null');
});

test('screenshot context command returns seeded aliases and route templates', function (): void {
    Artisan::call('app:seed-screenshot-account', ['--force' => true]);
    Artisan::call('app:screenshot-context', ['--json' => true]);

    $context = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

    expect($context['version'])->toBe(1);
    expect($context['platform'])->toBe('server');
    expect($context['output_root'])->toBe('storage/app/screenshots/server');

    expect($context['aliases'])->toHaveKeys([
        'organization.northstar',
        'project.control-plane',
        'project.customer-api',
        'project.marketing-site',
        'environment.control-plane.production',
        'environment.control-plane.staging',
        'environment.control-plane.qa',
        'environment.customer-api.production',
        'environment.customer-api.staging',
        'environment.marketing-site.production',
        'environment.marketing-site.preview',
        'integration_client.northstar-compliance-gateway',
    ]);

    expect($context['aliases']['environment.control-plane.production']['urls']['variables'])
        ->toContain('/environments/')
        ->toEndWith('/variables');

    expect($context['aliases']['project.control-plane']['urls']['environments'])
        ->toContain('/projects/')
        ->toEndWith('/environments');

    expect($context['aliases']['integration_client.northstar-compliance-gateway']['urls']['edit'])
        ->toContain('/organization/settings/integrations/')
        ->toEndWith('/edit');

    expect($context['route_templates'])->toHaveKeys([
        'dashboard',
        'organization.settings.members',
        'organization.settings.notifications',
        'organization.settings.integrations',
        'project.environments',
        'environment.variables',
    ]);

    expect($context['route_templates']['environment.variables']['parameters'])->toBe(['environment']);
    expect($context['route_templates']['project.environments']['parameters'])->toBe(['project']);
});
