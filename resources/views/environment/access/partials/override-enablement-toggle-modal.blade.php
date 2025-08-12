<flux:modal 
    x-data="{ is_restricted: $wire.entangle('is_restricted') }"
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
            <flux:button variant="danger" wire:click="updateIsRestricted">
                <span x-show="is_restricted">
                    Yes, Restrict Access
                </span>
                <span x-show="!is_restricted">
                    Yes, Disable Restriction
                </span>
            </flux:button>
        </div>
    </div>
</flux:modal>