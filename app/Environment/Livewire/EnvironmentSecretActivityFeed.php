<?php

namespace App\Environment\Livewire;

use App\Environment\Models\EnvironmentSecret;
use App\Environment\Resolvers\ResolveEnvironmentSecret;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Spatie\Activitylog\Models\Activity;

class EnvironmentSecretActivityFeed extends Component
{
    public bool $showing = false;

    public ?string $environmentSecretId = null;

    public const LAUNCH = 'secret-activity:launch';

    #[On(self::LAUNCH)]
    public function launch(EnvironmentSecret $secret): void
    {
        // $this->authorize('viewAuditLogs', $secret->environment->owningOrganization());

        $this->environmentSecretId = $secret->id;
        $this->showing = true;
    }

    #[Computed]
    public function secret(): ?EnvironmentSecret
    {
        if (! $this->environmentSecretId) {
            return null;
        }

        return ResolveEnvironmentSecret::onceWithContext($this->environmentSecretId);
    }

    #[Computed]
    public function activities()
    {
        if (! $this->secret) {
            return collect();
        }

        return Activity::query()
            ->where('subject_type', $this->secret->getMorphClass())
            ->where('subject_id', $this->secret->id)
            ->with('causer')
            ->latest()
            ->limit(50)
            ->get();
    }

    public function render()
    {
        return <<<'BLADE'
            <flux:modal variant="flyout" wire:model="showing" class="md:w-lg">
                <div class="space-y-6">
                    <flux:heading size="lg">Activity Feed</flux:heading>
                    <div class="flow-root">
                        <ul role="list" class="-mb-8">
                            @forelse ($this->activities as $activity)
                                <li>
                                    <div class="relative pb-8">
                                        @if(!$loop->last)
                                        <span class="absolute top-5 left-5 -ml-px h-full w-0.5 bg-gray-200"></span>
                                        @endif
                                        <div class="relative flex space-x-3">
                                            <div>
                                                <flux:profile circle 
                                                    :chevron="false"
                                                    size="xs" 
                                                    :initials="$activity->causer->initials()"/>
                                            </div>
                                            <div class="flex min-w-0 flex-1 align-middle justify-between space-x-4 pt-1.5">
                                                <div>
                                                    <flux:text variant="strong" size="sm">
                                                        {{ $activity->causer->email }}
                                                        </flux:text>
                                                    <flux:text size="sm" variant="subtle">
                                                        {{ $activity->description }}
                                                    </flux:text>
                                                </div>
                                                <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                                    {{ $activity->created_at->timezone(timezone())->diffForHumans() }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @empty
                                <p class="text-sm text-gray-500">No activity recorded yet.</p>
                            @endforelse
                        </ul>
                    </div>
                    <div class="flex gap-2 justify-end">
                        <flux:modal.close>
                            <flux:button variant="filled">Close</flux:button>
                        </flux:modal.close>
                    </div>
                </div>
            </flux:modal>
        BLADE;
    }
}
