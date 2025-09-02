<?php

use App\Account\Models\User;
use App\Organization\Models\Organization;
use App\Project\Models\Project;
use App\Project\Notifications\ProjectSettingsChangedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('routes via mail and slack and builds messages', function () {
    $org = Organization::factory()->create(['name' => 'Acme']);
    $project = Project::factory()->forOrganization($org)->create(['name' => 'Website']);
    $notification = new ProjectSettingsChangedNotification($project);
    $notifiable = User::factory()->make(['name' => 'Alice']);

    expect($notification->via($notifiable))->toBe(['mail', 'slack'])
        ->and($notification->forOrganization()->is($org))->toBeTrue();

    $mail = $notification->toMail($notifiable);

    expect($mail->subject)->toBe('Project settings changed')
        ->and($mail->greeting)->toBe($notifiable->greeting())
        ->and($mail->introLines)->toBe([
            'Project settings for the "Website" project has been updated in the "Acme" organization.',
            'You are receiving this alert because you are an administrator of this organization.',
        ]);

    expect($notification->toSlack($notifiable))->toBe(
        'Project settings for the "Website" project has been updated in the "Acme" organization.'
    );
});
