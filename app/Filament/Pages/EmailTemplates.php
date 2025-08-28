<?php

namespace App\Filament\Pages;

use App\Account\Models\User;
use App\Environment\Models\Environment;
use App\Environment\Notifications\EnvironmentCreatedNotification;
use App\Environment\Notifications\EnvironmentDeletedNotification;
use App\Environment\Variable\Models\EnvironmentVariable;
use App\Environment\Variable\Notifications\VariableUpdatedNotification;
use App\Organization\Models\Invite;
use App\Organization\Models\Organization;
use App\Organization\Notifications\AccessChangeNotification;
use App\Organization\Notifications\InviteNotification;
use App\Organization\Notifications\MemberInvitedNotification;
use App\Organization\Notifications\MemberJoinedNotification;
use App\Organization\Notifications\MemberRemovedNotification;
use App\Organization\Notifications\OrganizationSettingsChangedNotification;
use App\Project\Models\Project;
use App\Project\Notifications\ProjectCreatedNotification;
use App\Project\Notifications\ProjectDeletedNotification;
use App\Project\Notifications\ProjectSettingsChangedNotification;
use App\Secret\Models\Secret;
use App\Secret\Notifications\SecretUpdatedNotification;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use UnitEnum;

class EmailTemplates extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected string $view = 'filament.pages.email-templates';

    public string $notificationClass = ProjectCreatedNotification::class;

    #[Computed(persist: true)]
    public function notifications(): array
    {
        return [
            ProjectCreatedNotification::class,
            ProjectDeletedNotification::class,
            ProjectSettingsChangedNotification::class,
            MemberInvitedNotification::class,
            MemberJoinedNotification::class,
            MemberRemovedNotification::class,
            AccessChangeNotification::class,
            OrganizationSettingsChangedNotification::class,
            InviteNotification::class,
            EnvironmentCreatedNotification::class,
            EnvironmentDeletedNotification::class,
            VariableUpdatedNotification::class,
            SecretUpdatedNotification::class,
        ];
    }

    #[Computed(persist: true)]
    public function notificationOptions(): array
    {
        return collect($this->notifications)
            ->mapWithKeys(fn ($class) => [
                $class => Str::of(class_basename($class))->headline(),
            ])
            ->toArray();
    }

    #[Computed(persist: true)]
    public function user(): User
    {
        return User::factory()->make([
            'name' => 'Example User',
            'email' => 'user@example.com',
        ]);
    }

    private function sampleOrganization(): Organization
    {
        $organization = Organization::factory()->make([
            'name' => 'Acme Inc',
        ]);

        $organization->setRelation('owner', $this->user);

        return $organization;
    }

    private function sampleProject(): Project
    {
        $organization = $this->sampleOrganization();
        $project = Project::factory()->forOrganization($organization)->make([
            'name' => 'Demo Project',
        ]);

        $project->setRelation('organization', $organization);

        return $project;
    }

    private function sampleEnvironment(): Environment
    {
        $project = $this->sampleProject();
        $environment = Environment::factory()->forProject($project)->make([
            'name' => 'Production',
        ]);

        $environment->setRelation('project', $project);

        return $environment;
    }

    private function sampleInvite(): Invite
    {
        $invite = new Invite([
            'email' => 'invitee@example.com',
        ]);

        $invite->setRelation('organization', $this->sampleOrganization());
        $invite->setRelation('user', $this->user);

        return $invite;
    }

    private function sampleEnvironmentVariable(): EnvironmentVariable
    {
        $environment = $this->sampleEnvironment();
        $variable = new EnvironmentVariable([
            'key' => 'APP_KEY',
        ]);

        $variable->setRelation('environment', $environment);

        return $variable;
    }

    private function sampleSecret(): Secret
    {
        return new Secret([
            'name' => 'API_TOKEN',
        ]);
    }

    #[Computed(persist: false)]
    public function html(): ?string
    {
        $notification = match ($this->notificationClass) {
            ProjectCreatedNotification::class => new ProjectCreatedNotification($this->sampleProject()),
            ProjectDeletedNotification::class => new ProjectDeletedNotification($this->sampleProject()),
            ProjectSettingsChangedNotification::class => new ProjectSettingsChangedNotification($this->sampleProject()),
            MemberInvitedNotification::class => new MemberInvitedNotification($this->sampleInvite()),
            MemberJoinedNotification::class => new MemberJoinedNotification($this->sampleInvite()),
            MemberRemovedNotification::class => new MemberRemovedNotification($this->sampleOrganization(), $this->user),
            AccessChangeNotification::class => new AccessChangeNotification($this->sampleOrganization(), $this->user),
            OrganizationSettingsChangedNotification::class => new OrganizationSettingsChangedNotification($this->sampleOrganization()),
            InviteNotification::class => new InviteNotification($this->sampleInvite()),
            EnvironmentCreatedNotification::class => new EnvironmentCreatedNotification($this->sampleEnvironment()),
            EnvironmentDeletedNotification::class => new EnvironmentDeletedNotification($this->sampleEnvironment()),
            VariableUpdatedNotification::class => new VariableUpdatedNotification($this->sampleEnvironmentVariable()),
            SecretUpdatedNotification::class => new SecretUpdatedNotification($this->sampleSecret()),
            default => null,
        };

        return $notification?->toMail($this->user)->render();
    }
}
