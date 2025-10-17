<x-layouts.environment-settings :environment="$this->environment">
    
    <div class="space-y-6 max-w-2xl">
        
        @perform($this->environment, 'env:manage-settings')
            
            {{-- Environment ID --}}
            <x-section>
                <x-slot:title>{{ __('Environment ID') }}</x-slot:title>
                <x-slot:subheading>
                    Your environment's unique ID.
                    <div class="max-w-2xl">
                        
                    </div>
                </x-slot:subheading>
                <flux:input wire:model="environmentId" readonly copyable/>
            </x-section>
            
            @if($this->environment->project->is_legacy)
                {{-- Base Environment --}}
                <x-section>
                    <x-slot:title>{{ __('Base Enviromment') }}</x-slot:title>
                    <x-slot:subheading>
                        <div class="max-w-2xl">
                            The enviromment for which this environment will
                            inherit it's variables and validation from.
                        </div>
                    </x-slot:subheading>
                    <flux:select wire:model.live="base_id" :readonly="!$this->canEdit">
                        <flux:select.option wire:key="base-none" value="">None (standalone)</flux:select.option>
                        @foreach($this->baseOptions as $env)
                            <flux:select.option wire:key="base-{{ $env->id }}" value="{{ $env->id }}">
                                {{ $env->name }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </x-section>

                {{-- Base change confirmation modal --}}
                <flux:modal name="confirm-base-change" focusable class="max-w-lg">
                    <div class="space-y-6">
                        <div>
                            <flux:heading size="lg">{{ __('Change Base Environment') }}</flux:heading>
                            <flux:subheading>{{ __('Changing the base will update inherited variables and validation rules
                            for this environment.') }}</flux:subheading>
                        </div>
                        <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                            <flux:modal.close>
                                <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                            </flux:modal.close>
                            <flux:button variant="danger" wire:click="updateBaseEnvironment">{{ __('Change') }}</flux:button>
                        </div>
                    </div>
                </flux:modal>
            @endif

            {{-- Environment name/type editor --}}
            <x-section>
                <x-slot:title>{{ __('General Settings') }}</x-slot:title>
                <x-slot:subheading>
                    <div class="max-w-2xl">
                        {{ __('Update this environment\'s configuration.') }}
                    </div>
                </x-slot:subheading>
                <x-slot:actions>
                    @if($this->canEdit)
                        <div class="flex items-center justify-end">
                            <flux:button variant="primary" wire:click="updateEnvironment" class="w-full">
                                {{ __('Save') }}
                            </flux:button>
                        </div>
                    @endif
                </x-slot:actions>
                <form class="w-full space-y-6">
                    <flux:input wire:model="name" label="Name" :readonly="!$this->canEdit" required/>
                    <flux:select label="Type" wire:model="type" :readonly="!$this->canEdit" required>
                        @foreach($this->typeOptions as $key => $option)
                            <flux:select.option wire:key="type-{{ $key }}" value="{{ $key }}">
                                {{ $option }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select label="File Format" wire:model="fileFormat" :readonly="!$this->canEdit" required>
                        @foreach($this->formatOptions as $key => $option)
                            <flux:select.option wire:key="format-{{ $key }}" value="{{ $key }}">
                                {{ $option }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </form>
            </x-section>
            
            {{-- Delete environment callout --}}
            @perform($this->environment->project, 'env:delete')
                <flux:separator variant="subtle" />
                <div>
                    <flux:callout icon="trash" color="red" inline>
                        <flux:callout.heading>Danger Zone</flux:callout.heading>
                        <flux:callout.text>
                            Deleting this environment will permanently remove all associated variables.
                            This action cannot be undone.
                        </flux:callout.text>
                        <x-slot name="actions" class="@md:h-full m-0!">
                            <flux:modal.trigger name="confirm-environment-deletion">
                                <flux:button variant="danger">Delete Environment</flux:button>
                            </flux:modal.trigger>
                        </x-slot>
                    </flux:callout>
                </div>
            @endperform
            
            <flux:modal name="confirm-environment-deletion" focusable class="max-w-lg">
                <div class="space-y-6" x-data="{ confirmation: '' }">
                    <div>
                        <flux:heading size="lg">
                            {{ __('Delete Environment') }}
                        </flux:heading>
                        <flux:subheading>
                            {{ __('This action will permanently delete the environment and all of its variables. This cannot be undone.') }}
                        </flux:subheading>
                        @if ($this->environment->derived->isNotEmpty())
                            <div class="mt-4 space-y-2">
                                <flux:text>
                                    {{ __('The following environments inherit from this one and will become standalone:') }}
                                </flux:text>
                                <ul class="list-disc list-inside">
                                    @foreach ($this->environment->derived as $env)
                                        <li>{{ $env->name }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                    <div class="space-y-4">
                        <flux:text>
                            To confirm, please type
                            <flux:text class="inline font-bold" variant="strong">
                                {{ $this->environment->name }}
                            </flux:text>
                        </flux:text>
                        <flux:input x-model="confirmation" placeholder="{{ $this->environment->name }}"/>
                    </div>
                    <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                        <flux:modal.close>
                            <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                        </flux:modal.close>
                        <flux:button
                            variant="danger"
                            x-bind:disabled="confirmation !== '{{ $this->environment->name }}'"
                            wire:click="deleteEnvironment">
                            {{ __('Delete Environment') }}
                        </flux:button>
                    </div>
                </div>
            </flux:modal>
            
        @else
            <x-access-restricted/>
        @endperform
    
    </div>
    
</x-layouts.environment-settings>