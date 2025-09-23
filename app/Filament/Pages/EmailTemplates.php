<?php

namespace App\Filament\Pages;

use App\Account\Models\User;
use App\Auth\Notifications\ResetPasswordNotification;
use App\Auth\Notifications\VerifyEmailNotification;
use App\Blog\Models\Post;
use App\Environment\Models\Environment;
use App\Environment\Notifications\EnvironmentCreatedNotification;
use App\Environment\Notifications\EnvironmentDeletedNotification;
use App\Environment\Variable\Models\EnvironmentVariable;
use App\Environment\Variable\Notifications\VariableUpdatedNotification;
use App\Messaging\Mail\Broadcast\PostPublishedMailable;
use App\Messaging\Mail\Drip\CliSetupNudgeMailable;
use App\Messaging\Mail\Drip\InviteMembersNudgeMailable;
use App\Messaging\Mail\Drip\OrganizationSetupNudgeMailable;
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
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use UnitEnum;

class EmailTemplates extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected string $view = 'filament.pages.email-templates';

    public string $notificationClass = PostPublishedMailable::class;

    public ?string $previewPostId = null;

    public bool $as_reminder = false;

    #[Computed(persist: true)]
    public function notifications(): array
    {
        return [
            'transactional' => [
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
            ],
            'broadcast' => [
                PostPublishedMailable::class,
            ],
            'drip' => [
                OrganizationSetupNudgeMailable::class,
                CliSetupNudgeMailable::class,
                InviteMembersNudgeMailable::class,
            ],
        ];
    }

    #[Computed(persist: true)]
    public function publishedPosts(): Collection
    {
        return Post::published()->get();
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

    private function samplePost(): Post
    {
        if ($this->previewPostId) {
            return Post::find($this->previewPostId);
        }

        return Post::first();
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
        return match ($this->notificationClass) {
            PostPublishedMailable::class => (new PostPublishedMailable($this->user(), $this->samplePost()))->render(),
            VerifyEmailNotification::class => (new VerifyEmailNotification)->toMail($this->user())->render(),
            ResetPasswordNotification::class => (new ResetPasswordNotification('example-token'))->toMail($this->user())->render(),
            ProjectCreatedNotification::class => (new ProjectCreatedNotification($this->sampleProject()))->toMail($this->user())->render(),
            ProjectDeletedNotification::class => (new ProjectDeletedNotification($this->sampleProject()))->toMail($this->user())->render(),
            ProjectSettingsChangedNotification::class => (new ProjectSettingsChangedNotification($this->sampleProject()))->toMail($this->user())->render(),
            MemberInvitedNotification::class => (new MemberInvitedNotification($this->sampleInvite()))->toMail($this->user())->render(),
            MemberJoinedNotification::class => (new MemberJoinedNotification($this->sampleInvite()))->toMail($this->user())->render(),
            MemberRemovedNotification::class => (new MemberRemovedNotification($this->sampleOrganization(), $this->user()))->toMail($this->user())->render(),
            AccessChangeNotification::class => (new AccessChangeNotification($this->sampleOrganization(), $this->user()))->toMail($this->user())->render(),
            OrganizationSettingsChangedNotification::class => (new OrganizationSettingsChangedNotification($this->sampleOrganization()))->toMail($this->user())->render(),
            InviteNotification::class => (new InviteNotification($this->sampleInvite()))->toMail($this->user())->render(),
            EnvironmentCreatedNotification::class => (new EnvironmentCreatedNotification($this->sampleEnvironment()))->toMail($this->user())->render(),
            EnvironmentDeletedNotification::class => (new EnvironmentDeletedNotification($this->sampleEnvironment()))->toMail($this->user())->render(),
            VariableUpdatedNotification::class => (new VariableUpdatedNotification($this->sampleEnvironmentVariable()))->toMail($this->user())->render(),
            SecretUpdatedNotification::class => (new SecretUpdatedNotification($this->sampleSecret()))->toMail($this->user())->render(),
            OrganizationSetupNudgeMailable::class => (new OrganizationSetupNudgeMailable($this->user(), $this->as_reminder))->render(),
            CliSetupNudgeMailable::class => (new CliSetupNudgeMailable($this->user(), $this->as_reminder))->render(),
            InviteMembersNudgeMailable::class => (new InviteMembersNudgeMailable($this->user(), $this->as_reminder))->render(),
            default => null,
        };
    }
}
