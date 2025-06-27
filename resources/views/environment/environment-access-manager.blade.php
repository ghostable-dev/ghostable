<section class="space-y-12">
    
    {{-- Toggle environment restricted access --}}
    <div>
        <div class="mb-4">
            <flux:heading size="lg">{{ __('Access Restrictions') }}</flux:heading>
            <flux:subheading>
                {{ __('When enabled, team members will not be able to access this environment unless they are explicitly granted access in Access Control.') }}
            </flux:subheading>
        </div>
        <form class="my-6 w-full space-y-6">
            <flux:switch 
                wire:model.live="is_restricted" 
                x-on:change="$flux.modal('confirm-restricted-access').show()" />
        </form>
        <flux:modal 
            x-data="{
                is_restricted: $wire.entangle('is_restricted')
            }"
            name="confirm-restricted-access" 
            class="min-w-[22rem]"
            @cancel="cancelIsRestrictedChange"
            :dismissible="false">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        <span x-show="is_restricted">{{ __('Restrict Access?') }}</span>
                        <span x-show="!is_restricted">{{ __('Disable Restricted Access?') }}</span>
                    </flux:heading>
                    <flux:text class="mt-2">
                        <span x-show="is_restricted">
                            <p>Team member roles will no longer apply to this environment.</p>
                            <p>Only explicit permission overrides will grant non-admins access.</p>
                        </span>
                        <span x-show="!is_restricted">
                            <p>This will re-enable access to the environment based on team member roles.</p>
                            <p>Explicit permission overrides will still apply, but will no longer be required.</p>
                        </span>
                    </flux:text>
                </div>
                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:button variant="ghost" wire:click="cancelIsRestrictedChange">Cancel</flux:button>
                    <x-auth.confirms-password wire:then="updateIsRestricted">
                        <span x-show="is_restricted">
                            <flux:button variant="danger">Yes, Restrict Access</flux:button>
                        </span>
                        <span x-show="!is_restricted">
                            <flux:button variant="primary">Yes, Disable Restriction</flux:button>
                        </span>
                    </x-auth.confirms-password>
                </div>
            </div>
        </flux:modal>
    </div>
    
    @if($this->environment->is_restricted)
        <div>
        
            {{-- Overrides table --}}
            <div class="mb-4 flex">
                <div>
                    <flux:heading size="lg">{{ __('Overrides') }}</flux:heading>
                    <flux:subheading>{{ __('Team members who haven’t joined yet.') }}</flux:subheading>
                </div>
                <flux:spacer/>
                @if($this->overrides->isNotEmpty())
                    <flux:modal.trigger name="add-override">
                        <flux:button variant="primary">
                            Add Override
                        </flux:button>
                    </flux:modal.trigger>
                @endif
            </div>
            
            {{-- Add override modal --}}
            <flux:modal name="add-override">
                <form wire:submit="createOverride" class="space-y-6">
                    <div>
                        <flux:heading size="lg">Add Permission Override</flux:heading>
                        <flux:text class="mt-2">Grant specific access to a team member on this environment, regardless of their team role. Use this to fine-tune access for non-admin users.</flux:text>
                    </div>
                    <div>
                        <flux:select 
                            label="Team Member"
                            variant="listbox" 
                            searchable 
                            placeholder="Select member..."
                            wire:model.live="userId">
                            @foreach($this->members as $member)
                                <flux:select.option 
                                    value="{{ $member->id }}"
                                    wire:key="member-{{ $member->id }}">
                                        {{ $member->email }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                    <div>
                        <flux:select 
                            label="Permission" 
                            variant="listbox" 
                            searchable 
                            placeholder="Select permission..."
                            wire:model.live="permission">
                            @foreach($this->assignablePermissions as $permission)
                                <flux:select.option 
                                    value="{{ $permission->value }}"
                                    wire:key="permission-{{ $permission->value }}">
                                        {{ $permission->label() }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                    <div class="flex gap-2">
                        <flux:spacer />
                        <flux:modal.close>
                            <flux:button variant="ghost">Cancel</flux:button>
                        </flux:modal.close>
                        <flux:button type="submit" variant="primary">
                            Create Override
                        </flux:button>
                    </div>
                </form>
            </flux:modal>
            
            @if($this->overrides->isNotEmpty())
                <flux:table :paginate="$this->overrides">
                    <flux:table.columns>
                        <flux:table.column>Member</flux:table.column>
                        <flux:table.column>Permission</flux:table.column>
                        <flux:table.column></flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach($this->overrides as $override)
                            <flux:table.row wire:key="override-{{ $override->id }}">
                                <flux:table.cell class="flex items-center gap-3">
                                    <flux:profile
                                        :initials="$override->user->initials()"
                                        :chevron="false"
                                        circle/>
                                    <span>
                                        <b class="block text-black dark:text-white">
                                            {{ $override->user->name }}
                                        </b>
                                        {{ $override->user->email }}
                                    </span>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge size="sm">
                                        {{ $override->permission->label() }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell inset="top bottom" size="sm" align="right">
                                    <flux:link 
                                        wire:click="confirmOverrideRemoval('{{ $override->id }}')"  
                                        variant="danger">
                                        Remove
                                    </flux:link>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
                
                {{-- Remove override modal --}}
                <flux:modal name="confirm-override-removal" class="md:w-lg">
                    <form wire:submit="removeOverride" class="space-y-6">
                        <div>
                            <flux:heading size="lg">Remove Permission Override</flux:heading>
                            <flux:text class="mt-2">
                                Are you sure you want to remove the
                                <flux:text class="inline" variant="strong">
                                    “{{ $this->overrideToRemove?->permission->label() }}”
                                </flux:text>
                                permission from
                                <flux:text class="inline" variant="strong">
                                    {{ $this->overrideToRemove?->user->email }}
                                </flux:text>
                                ?
                            </flux:text>
                        </div>
                        <div class="flex gap-2">
                            <flux:spacer />
                            <flux:modal.close>
                                <flux:button variant="ghost">Cancel</flux:button>
                            </flux:modal.close>
                            <flux:button type="submit" variant="danger">
                                Remove Override
                            </flux:button>
                        </div>
                    </form>
                </flux:modal>
            
            @else
            
                {{-- Overrides empty state --}}
                <div class="space-y-6">
                    <div>
                        <flux:subheading>You have no overrides yet. Once you do, they’ll show up here.</flux:subheading>
                    </div>
                    <flux:modal.trigger name="add-override">
                        <flux:button variant="primary">
                            Add Override
                        </flux:button>
                    </flux:modal.trigger>
                </div>
            
            @endif
            
        </div>
    @endif
        
</section>