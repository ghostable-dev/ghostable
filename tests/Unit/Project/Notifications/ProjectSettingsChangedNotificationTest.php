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

    expect($mail->subject)->toBe('Ghostable project settings updated')
        ->and($mail->view)->toBe('mail.project-settings-changed')
        ->and($mail->viewData)->toMatchArray([
            'project' => $project,
        ]);

    expect($notification->toSlack($notifiable))->toBe(
        'Project settings for the project Website changed in the Acme organization of Ghostable.'
    );
});
