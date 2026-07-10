@props([
    'licenses' => collect(),
    'revealedLicenseKeys' => [],
])

<section class="space-y-3">
    <div>
        <flux:heading size="lg" level="2">Available Licenses</flux:heading>
        <flux:subheading>License keys are emailed to the purchaser and masked here after creation.</flux:subheading>
    </div>

    @if($licenses->isEmpty())
        <flux:callout icon="key" color="slate">
            <flux:callout.heading>No licenses yet</flux:callout.heading>
            <flux:callout.text>
                Select a license offer above to start Stripe checkout.
            </flux:callout.text>
        </flux:callout>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column>Plan</flux:table.column>
                <flux:table.column>License</flux:table.column>
                <flux:table.column></flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Seats</flux:table.column>
                <flux:table.column>Devices</flux:table.column>
                <flux:table.column>Updates</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach($licenses as $license)
                    @php
                        $revealedLicenseKey = $revealedLicenseKeys[$license->getKey()] ?? null;
                    @endphp

                    <flux:table.row wire:key="organization-license-{{ $license->id }}">
                        <flux:table.cell>{{ $license->plan->label() }}</flux:table.cell>
                        <flux:table.cell class="font-mono text-xs">
                            {{ $revealedLicenseKey ?? $license->maskedLicenseKey() }}
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($revealedLicenseKey)
                                <flux:button
                                    size="xs"
                                    variant="ghost"
                                    wire:click="hideLicenseKey('{{ $license->getKey() }}')">
                                    Hide
                                </flux:button>
                            @else
                                <flux:button
                                    size="xs"
                                    variant="filled"
                                    wire:click="revealLicenseKey('{{ $license->getKey() }}')">
                                    Reveal
                                </flux:button>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge color="{{ $license->isUsable() ? 'green' : 'zinc' }}">
                                {{ $license->status->label() }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ $license->seat_count }}</flux:table.cell>
                        <flux:table.cell>{{ $license->active_activations_count ?? 0 }} / {{ $license->activation_limit }}</flux:table.cell>
                        <flux:table.cell>
                            {{ $license->updates_until?->timezone(timezone())->format(DT_FORMAT) ?? 'N/A' }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</section>
