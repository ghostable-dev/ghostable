<div wire:poll.10s>
    @if($this->requiresDeviceLink)
        <x-cli-device-blocker />
    @elseif($this->showDeviceReminderBanner)
        @php
            $organization = auth()->user()?->currentOrganization();
        @endphp

        <x-cli-device-blocker
            :auto-open="false"
            :dismissible="true"
            :closable="true"
        />

        <div class="px-4 py-3 sm:px-6">
            <div
                x-data="{ dismissed: false }"
                x-show="!dismissed"
                x-transition
                class="mx-auto"
            >
                <div class="relative isolate overflow-hidden rounded-xl border border-emerald-100 bg-white/90 px-4 py-4 shadow-sm ring-1 ring-zinc-200/80 backdrop-blur sm:px-6 sm:py-5">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div class="flex items-start gap-3">
                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">Device link needed</span>
                            <div class="space-y-1">
                                <p class="text-sm text-zinc-600">
                                    You don’t have a linked device yet. Link one to push and pull environment variables securely with {{ $organization?->name ?? 'your workspace' }}.
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 md:ml-6">
                            <flux:button size="sm" variant="primary" x-on:click="$flux.modal('cli-device-blocker').show()">
                                Link device
                            </flux:button>
                            <flux:button size="sm" variant="ghost" x-on:click="dismissed = true">
                                Remind me later
                            </flux:button>
                        </div>
                    </div>
                    <div class="pointer-events-none absolute inset-0 -z-10 bg-gradient-to-r from-emerald-50/80 via-white to-blue-50/70"></div>
                </div>
            </div>
        </div>
    @endif
</div>
