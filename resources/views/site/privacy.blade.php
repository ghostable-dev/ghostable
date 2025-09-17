@push('meta')
    <x-seo-meta
        title="Privacy Policy"
        description="Privacy Policy for ghostable.dev"
        :keywords="[]"/>
@endpush

@inject('carbon', '\Illuminate\Support\Carbon')

<x-layouts.legal 
    title="Privacy Policy"
    canonical="{{ route('privacy') }}"
    :last-updated="$carbon::create(2025, 6, 23, 12, 0, 0)">
    <x-slot:document>
        {!! str(file_get_contents(resource_path('markdown/privacy-policy.md')))->markdown() !!}
    </x-slot>
</x-layouts.legal>