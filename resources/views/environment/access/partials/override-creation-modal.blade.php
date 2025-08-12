<flux:modal name="add-override">
    <form wire:submit="createOverride" class="space-y-6">
        <div>
            <flux:heading size="lg">Add Permission Override</flux:heading>
            <flux:text class="mt-2">Grant specific access to a team member on this 
            environment, regardless of their team role. Use this to fine-tune access 
            for non-admin users.</flux:text>
        </div>
        <div>
            <flux:select 
                label="Team Member"
                variant="listbox" 
                searchable 
                placeholder="Select member..."
                wire:model.live="userId"
                required>
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
                wire:model.live="permission"
                required>
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