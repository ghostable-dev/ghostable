<x-layouts.project-settings :project="$this->project">
    @perform($this->project, 'project:manage-settings')
        <div class="space-y-6 max-w-2xl">

            {{-- Project ID --}}
            <x-section>
                <x-slot:title>{{ __('Project ID') }}</x-slot:title>
                <x-slot:subheading>Your project's unique ID.</x-slot:subheading>
                <flux:input wire:model="projectId" readonly copyable/>
            </x-section>

            {{-- Project name/description editor --}}
            <x-section>
                <x-slot:title>{{ __('Settings') }}</x-slot:title>
                <x-slot:subheading>{{ __('Update this project\'s configuration.') }}</x-slot:subheading>
                <x-slot:actions>
                    @if($this->canEdit)
                        <div class="flex items-center justify-end gap-4">
                            <flux:button variant="primary" wire:click="updateProject" class="w-full">
                                {{ __('Save') }}
                            </flux:button>
                            <x-action-message class="me-3" on="project-updated">
                                {{ __('Saved.') }}
                            </x-action-message>
                        </div>
                    @endif
                </x-slot:actions>
                <form class="w-full space-y-6">
                    <flux:input wire:model="name" label="Name" :readonly="!$this->canEdit" required/>
                    <flux:textarea wire:model="description" label="Description" :readonly="!$this->canEdit"/>
                    <flux:select 
                        variant="listbox" 
                        label="Deployment Provider" 
                        wire:model="deployment_provider" 
                        description:trailing="This helps Ghostable enable provider-specific controls and integrations for your project."
                        required>
                        @foreach($this->deploymentProviders as $provider)
                            <flux:select.option value="{{ $provider->value }}">{{ $provider->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </form>
            </x-section>
            
            @if($this->project->is_legacy)
            <x-section>
                <x-slot:title>{{ __('Zero-Knowledge Storage') }}</x-slot:title>
                <x-slot:subheading>
                    {{ __('Ghostable can store your configuration using fully encrypted, zero-knowledge secrets. You can migrate any time — your existing data stays safe.') }}
                </x-slot:subheading>

                <x-slot:actions>
                    @if($this->canEdit)
                        <div class="flex items-center justify-end gap-4">
                            <flux:button variant="primary" wire:click="updateLegacy" class="w-full">
                                {{ __('Save') }}
                            </flux:button>
                            <x-action-message class="me-3" on="updated-updated">
                                {{ __('Saved.') }}
                            </x-action-message>
                        </div>
                    @endif
                </x-slot:actions>

                <form class="w-full space-y-6">
                    <flux:switch
                        wire:model.live="is_zero_knowledge"
                        label="Zero-Knowledge Mode"
                        help="Enable full client-side encryption for secrets storage (V2)."
                        :readonly="!$this->canEdit"
                        required
                    />

                    @if($this->project->is_legacy)
                        <div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                            <div class="flex items-start gap-2">
                                <p>
                                    This project is currently using the legacy variable store.
                                    Learn how to migrate to Zero-Knowledge (V2) in the
                                    <a href="https://docs.ghostable.dev/migration/zero-knowledge"
                                       target="_blank"
                                       class="underline font-medium text-amber-800 hover:text-amber-900">
                                       migration guide
                                    </a>.
                                </p>
                            </div>
                        </div>
                    @else
                        <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                            <div class="flex items-start gap-2">
                                <p>
                                    This project uses <strong>Zero-Knowledge Secrets (V2)</strong>.
                                    All encryption happens locally, and Ghostable never stores plaintext values.
                                </p>
                            </div>
                        </div>
                    @endif
                </form>
            </x-section>
            @endif

            {{-- Delete project callout --}}
            @can('delete', $this->project)
            <flux:separator variant="subtle" />
            <div>
                <flux:callout icon="trash" color="red" inline>
                    <flux:callout.heading>Danger Zone</flux:callout.heading>
                    <flux:callout.text>
                        Deleting this project will permanently remove all associated environments and variables.
                        This action cannot be undone.
                    </flux:callout.text>
                    <x-slot name="actions" class="@md:h-full m-0!">
                        <flux:modal.trigger name="confirm-project-deletion">
                            <flux:button variant="danger">Delete Project</flux:button>
                        </flux:modal.trigger>
                    </x-slot>
                </flux:callout>
            </div>
            @endcan

            <flux:modal name="confirm-project-deletion" focusable class="max-w-lg">
                <div class="space-y-6" x-data="{ confirmation: '' }">
                    <div>
                        <flux:heading size="lg">
                            {{ __('Delete Project') }}
                        </flux:heading>
                        <flux:subheading>
                            {{ __('This action will permanently delete the project and all of its environments and variables. This cannot be undone.') }}
                        </flux:subheading>
                    </div>
                    <div class="space-y-4">
                        <flux:text>
                            To confirm, please type
                            <flux:text class="inline font-bold" variant="strong">
                                {{ $this->project->name }}
                            </flux:text>
                        </flux:text>
                        <flux:input x-model="confirmation" placeholder="{{ $this->project->name }}"/>
                    </div>
                    <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                        <flux:modal.close>
                            <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                        </flux:modal.close>
                        <flux:button
                            variant="danger"
                            x-bind:disabled="confirmation !== '{{ $this->project->name }}'"
                            wire:click="deleteProject">
                            {{ __('Delete Project') }}
                        </flux:button>
                    </div>
                </div>
            </flux:modal>
        </div>
    @else
        <x-access-restricted/>
    @endperform
</x-layouts.project-settings>

