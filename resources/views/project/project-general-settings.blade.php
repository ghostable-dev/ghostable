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
                        label="Language" 
                        wire:model.live="stack.language" 
                        placeholder="Select language..."
                        description:trailing="We’ll tailor recommendations based on your stack."
                        :disabled="!$this->canEdit"
                        required>
                        @foreach($this->languageOptions as $option)
                            <flux:select.option value="{{ $option->value }}">{{ $option->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    @if($this->stack['language'])
                        <flux:select 
                            variant="listbox" 
                            label="Framework" 
                            wire:model.live="stack.framework" 
                            placeholder="Select framework..."
                            description:trailing="Choose the framework or runtime that best matches your project."
                            :disabled="!$this->canEdit"
                            required>
                            @foreach($this->frameworkOptions as $option)
                                <flux:select.option value="{{ $option->value }}">{{ $option->label() }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    @endif

                    @if($this->stack['framework'])
                        <flux:select 
                            variant="listbox" 
                            label="Provider" 
                            wire:model.live="stack.platform" 
                            placeholder="Select provider..."
                            description:trailing="Tell us where this project is running so we can enable the right integrations."
                            :disabled="!$this->canEdit"
                            required>
                            @foreach($this->platformOptions as $option)
                                <flux:select.option value="{{ $option->value }}">{{ $option->label() }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    @endif
                </form>
            </x-section>

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
