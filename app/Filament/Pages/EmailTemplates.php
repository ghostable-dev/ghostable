<?php

namespace App\Filament\Pages;

use App\Account\Models\User;
use App\Auth\Notifications\ResetPasswordNotification;
use App\Auth\Notifications\VerifyEmailNotification;
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
            VerifyEmailNotification::class,
            ResetPasswordNotification::class,
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
        return User::where('email', 'rucci.joe@gmail.com')->firstOrFail();
    }

    private function sampleOrganization(): Organization
    {
        return $this->user()->organizations->first();
    }

    private function sampleProject(): Project
    {
        return $this->user()->organizations->first()->projects->first();
    }

    private function sampleEnvironment(): Environment
    {
        return $this->user()->organizations->first()->projects->first()->environments->first();
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
        return $this->sampleEnvironment()->variables->first();
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
            VerifyEmailNotification::class => new VerifyEmailNotification,
            ResetPasswordNotification::class => new ResetPasswordNotification('example-token'),
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
