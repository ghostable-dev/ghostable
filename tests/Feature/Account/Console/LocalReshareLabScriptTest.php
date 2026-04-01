<?php

declare(strict_types=1);

test('local reshare lab script uses realistic primary server fixtures', function (): void {
    $script = file_get_contents(base_path('scripts/local-reshare-lab.sh'));

    expect($script)->not->toBeFalse();
    expect($script)->toContain('PROJECT_NAME="${PROJECT_NAME:-Primary Server}"');
    expect($script)->toContain('APP_NAME="Primary Server"');
    expect($script)->toContain('APP_KEY=base64:');
    expect($script)->toContain('STRIPE_SECRET_KEY=');
    expect($script)->toContain('\App\Environment\Enums\EnvironmentType::PRODUCTION');
    expect($script)->toContain('\App\Environment\Enums\EnvironmentType::STAGING');
    expect($script)->not->toContain('LOCAL_ACCESS_SECRET');
    expect($script)->not->toContain('Key Re-share Lab');
    expect($script)->not->toContain('local-access-key');
});
