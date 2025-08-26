@props([
    'invoices' => collect([]) 
])
<div>
    @if($invoices->count())
        <x-section>
            <x-slot:title>{{ __('Invoices') }}</x-slot:title>
            <x-slot:subheading>
                Download a copy of your organizations invoices.
            </x-slot:subheading>
            <flux:table class="min-w-full divide-y divide-gray-300">
                <flux:table.rows>
                    @foreach($invoices as $invoice)
                        <flux:table.row wire:key="invoice-{{ $invoice->id }}">
                            <flux:table.cell>
                            {{ $invoice->date(timezone())->format(DT_FORMAT) }}
                            </flux:table.cell>
                            <flux:table.cell>
                                {{ $invoice->total() }}
                            </flux:table.cell>
                            <flux:table.cell>
                                @if($invoice->isPaid())
                                    <flux:badge color="green">Paid</flux:badge>
                                @elseif($invoice->isVoid())
                                    <flux:badge>Void</flux:badge>
                                    <flux:badge color="red">Unpaid</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:button 
                                    wire:click.prevent="download('{{ $invoice->id }}')">
                                    <span wire:loading.remove wire:target="download('{{ $invoice->id }}')">Download</span>
                                    <span wire:loading wire:target="download('{{ $invoice->id }}')">Downloading...</span>
                                </flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        </x-section>
    @endif
</div>