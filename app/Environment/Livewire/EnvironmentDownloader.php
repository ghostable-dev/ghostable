<?php

namespace App\Environment\Livewire;

use App\Environment\Actions\LogEnvironmentDownloaded;
use App\Environment\Actions\RenderEnvFile;
use App\Environment\Enums\EnvFileFormat;
use App\Environment\Models\Environment;
use App\Organization\Enums\OrganizationPermission;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

class EnvironmentDownloader extends EnvironmentComponent
{
    /**
     * Events
     */
    public const LAUNCH = 'environment-downloader:launch';

    public const DOWNLOADED = 'environment-downloader:downloaded';

    /**
     * Indicates whether the modal is currently visible.
     */
    public bool $showing = false;

    public EnvFileFormat $fileFormat;

    public function mount(Environment $environment): void
    {
        parent::mount($environment);

        $this->fileFormat = $environment->file_format;
    }

    /**
     * Livewire event listener to launch the environment modal.
     */
    #[On(self::LAUNCH)]
    public function launchModal(): void
    {
        $this->fileFormat = $this->environment->file_format;
        $this->showing = true;
    }

    #[Computed]
    public function fileFormatOptions(): array
    {
        return EnvFileFormat::selectOptions();
    }

    public function download()
    {
        $this->authorize('perform', [$this->environment, OrganizationPermission::ViewVariables]);

        $content = resolve(RenderEnvFile::class)->handle(env: $this->environment, format: $this->fileFormat);

        app(LogEnvironmentDownloaded::class)->handle(
            environment: $this->environment,
            user: Auth::user(),
            source: 'ui',
        );

        $filename = 'environment-'.str($this->environment->name)->slug().'.env';
        $this->showing = false;

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $filename);
    }

    public function render()
    {
        return <<<'BLADE'
            <flux:modal wire:model="showing" class="md:w-lg">
                <div class="space-y-6">
                    <div class="space-y-4">
                        <flux:heading size="lg">Download Environment File</flux:heading>
                        <flux:select label="File Format" wire:model="fileFormat">
                            @foreach ($this->fileFormatOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </flux:select>
                        <div class="flex gap-2">
                            <flux:spacer />
                            <flux:modal.close>
                                <flux:button variant="ghost">Cancel</flux:button>
                            </flux:modal.close>
                            <flux:button variant="primary" wire:click="download">
                                Download
                            </flux:button>
                        </div>
                    </div>
                </div>
            </flux:modal>
        BLADE;
    }
}
