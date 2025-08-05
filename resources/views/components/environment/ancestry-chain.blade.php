@props([
    'env',
    'chain' => $env->ancestryChain(), // ordered: root → current
])

<flux:dropdown hover position="bottom" align="start" offset="-8" gap="8">
    <button type="button" class="flex items-center text-blue-500 hover:text-blue-600">
        <x-icon.git-branch class="size-4" />
    </button>

    @if(count($chain) > 1)
        <flux:popover class="rounded-lg p-4 shadow-xl w-72">
            <div class="flow-root">
                <ul role="list" class="-mb-8">
                    @foreach($chain as $index => $item)
                        <li>
                            <div class="relative pb-8">
                                @unless($loop->last)
                                    <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                @endunless

                                <div class="relative flex space-x-3">
                                    <div>
                                        <span @class([
                                            'flex size-6 items-center justify-center rounded-full ring-4 ring-white',
                                            'bg-blue-500 text-white' => $loop->last,
                                            'bg-gray-400 text-white' => !$loop->last,
                                        ])>
                                            <x-icon.git-branch class="size-4" />
                                        </span>
                                    </div>
                                    <div class="flex min-w-0 flex-1 items-center space-x-2 pt-0.5">
                                        <span @class([
                                            'text-sm font-semibold text-blue-600' => $loop->last,
                                            'text-sm font-medium text-gray-800' => !$loop->last,
                                        ])>
                                            {{ $item->name }}
                                        </span>
                                        {{-- Optional metadata like updated_at or variable count --}}
                                    </div>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </flux:popover>
    @endif
</flux:dropdown>