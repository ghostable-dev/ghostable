@section('title', 'Terms of Service')

@push('meta')
<x-seo-meta
    title="Terms of Service"
    description="Terms of Service for ghostable.dev"
    :keywords="[]"/>
@endpush

@inject('carbon', '\Illuminate\Support\Carbon')

<x-layouts.legal 
    title="Terms of Service"
    :last-updated="$carbon::create(2025, 6, 23, 12, 0, 0)">
    <x-slot:document>
        {!! str(file_get_contents(resource_path('markdown/terms-of-service.md')))->markdown() !!}
    </x-slot>
</x-layouts.legal>