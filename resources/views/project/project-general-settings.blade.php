<x-layouts.project-settings :project="$this->project">
    @perform($this->project, 'project:manage-settings')
    
        {{-- Project ID --}}
        <div>
            <div class="mb-4">
                <flux:heading size="lg">{{ __('Project ID') }}</flux:heading>
                <flux:subheading>Your project's unique ID.</flux:subheading>
            </div>
            <div>
                <flux:input wire:model="projectId" readonly copyable/>
            </div>
        </div>
        
        {{-- Project name/description editor --}}
        <div>
            <div>
                <flux:heading size="lg">{{ __('Settings') }}</flux:heading>
                <flux:subheading>{{ __('Update this project\'s configuration.') }}</flux:subheading>
            </div>
            <form wire:submit="updateProject" class="my-6 w-full space-y-6">
                <flux:input wire:model="name" label="Name" :readonly="!$this->canEdit" required/>
                <flux:textarea wire:model="description" label="Description" :readonly="!$this->canEdit"/>
                <div class="flex items-center gap-4">
                    @if($this->canEdit)
                        <div class="flex items-center justify-end">
                            <flux:button variant="primary" type="submit" class="w-full">
                                {{ __('Save') }}
                            </flux:button>
                        </div>
                    @endif
                    <x-action-message class="me-3" on="project-updated">
                        {{ __('Saved.') }}
                    </x-action-message>
                </div>
            </form>
        </div>
        
        {{-- Delet project callout --}}
        @can('delete', $this->project)
        <flux:separator variant="subtle" />
        <div class="pt-6">
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
    
    @else
        <x-access-restricted/>
        
    @endperform
</x-layouts.project-settings>
