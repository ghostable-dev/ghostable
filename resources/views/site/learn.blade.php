@inject('learn', '\App\Learn\LearnRepository')

@push('meta')
    <x-seo-meta
        title="Learn Ghostable"
        description="Evergreen guides, playbooks, and examples for secure environment and secrets management with Ghostable."
        :keywords="[
            'ghostable learn',
            'secrets management guides',
            'environment variables best practices',
            'devops security',
            'configuration management'
        ]"/>
@endpush

<x-layouts.guest title="Learn" canonical="{{ route('learn.index') }}">
    <div class="bg-white">
        <div class="px-6 lg:px-8 pt-16 pb-20">
            <div class="mx-auto max-w-2xl lg:max-w-6xl">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="space-y-3">
                        <h1 class="text-4xl font-medium tracking-tighter text-gray-950 sm:text-6xl text-pretty">Learn</h1>
                        <p class="max-w-3xl text-2xl font-medium text-gray-500">
                            Tools and resources to help you get started faster with environment and configuration management—including Ghostable and broader best practices.
                        </p>
                    </div>
                    <flux:button href="https://docs.ghostable.dev" target="_blank" variant="primary">
                        View docs
                    </flux:button>
                </div>

                <div class="mt-14 mb-6 border-t border-gray-200"></div>

                
                @php
                    $guidesCollection = isset($activeTag) ? $learn->tagged($activeTag) : $learn->all();
                    $guides = $guidesCollection->map(function ($guide) {
                        return [
                            'title' => $guide['title'],
                            'description' => $guide['description'],
                            'href' => $guide['href'],
                            'cta' => 'Read the guide',
                            'image' => $guide['image'] ?? null,
                            'image_alt' => $guide['image_alt'] ?? null,
                            'tags' => $guide['tags'],
                        ];
                    });
                @endphp

                @if(isset($activeTag))
                    <div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700">
                        <span class="font-semibold text-gray-900">Filtered by tag:</span>
                        <flux:badge variant="soft" color="slate" class="uppercase tracking-[0.08em]">{{ $activeTag }}</flux:badge>
                        <flux:link variant="ghost" href="{{ route('learn.index') }}" class="text-sm font-semibold text-gray-900">
                            Clear filter
                        </flux:link>
                    </div>
                @endif

                <div class="mx-auto max-w-2xl lg:max-w-6xl">
                    <x-site.resource-section
                        id="guides"
                        label="Guides"
                        title="Guides"
                        description="Curated playbooks and examples on env vars, secrets, and config hygiene—practical patterns you can copy into your workflow."
                        :items="$guides"
                        class="mt-4"
                    />
                </div>
            </div>
        </div>

        <livewire:account.livewire.mailing-list-signup-form/>
    </div>
</x-layouts.guest>
