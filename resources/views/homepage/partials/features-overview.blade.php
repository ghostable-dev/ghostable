@php
$features = [
    [
        'title' => 'Security & Privacy',
        'description' => 'All secrets & environment files are encrypted in transit and at rest—keeping sensitive configs safe by default.',
        'icon' => 'lock-closed',
    ],
    [
        'title' => 'Automatic Validation',
        'description' => 'Instantly validate your .env files to catch errors before deployment—no manual checks required.',
        'icon' => 'check-circle',
    ],
    [
        'title' => 'Robust Versioning',
        'description' => 'Track every change with full environment history and rollback capability.',
        'icon' => 'clock',
    ],
    [
        'title' => 'Comprehensive Auditing',
        'description' => 'Detailed audit logs provide full transparency into environment file activities.',
        'icon' => 'eye',
    ],
    [
        'title' => 'Precision Sharing',
        'description' => 'Fine-grained team permissions ensure controlled access without confusion.',
        'icon' => 'users',
    ],
    [
        'title' => 'Seamless Deployments',
        'description' => 'Easily integrate into your existing CI/CD workflows to deploy configs securely.',
        'icon' => 'cloud-arrow-up',
    ],
];
@endphp

<div
    @class([
        'mx-auto container my-14 py-14'
        //'my-14 py-14 md:rounded-t-[1.5rem] lg:rounded-t-[3.25rem]',
        //'bg-gradient-to-r from-zinc-50 to-white'    
    ])>
    <div class="grid grid-cols-1 gap-10 sm:grid-cols-3 px-10">
        @foreach($features as $feature)
            <div 
                data-aos="animate__fadeIn"
                class="flex flex-col items-start space-y-4 animate__animated">
                <flux:icon name="{{ $feature['icon'] }}" class="text-brand" variant="solid"/>
                <flux:heading class="font-semibold dark" level="3" size="lg">
                    {{ $feature['title'] }}
                </flux:heading>
                <flux:subheading class="dark">
                    {{ $feature['description'] }}
                </flux:subheading>
            </div>
        @endforeacH
    </div>
</div>