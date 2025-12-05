@inject('learn', '\App\Learn\LearnRepository')
@php
    $guide = $learn->findBySlug('env-naming-conventions');
    $guideTitle = $guide['title'] ?? 'ENV Variable Naming Conventions & Best Practices';
    $guideDescription = $guide['description'] ?? 'A concise guide to consistent, portable, collision-free environment variable naming that works across languages and teams.';
    $guideKeywords = $guide['keywords'] ?? [];
    $guideImage = $guide['image'] ?? null;
    $tableOfContents = [
        ['href' => '#introduction', 'label' => 'Why naming conventions matter'],
        ['href' => '#conventions', 'label' => 'Recommended conventions'],
        ['href' => '#pitfalls', 'label' => 'Common pitfalls'],
        ['href' => '#team-standard', 'label' => 'Suggested team standard'],
        ['href' => '#examples', 'label' => 'Good vs. bad examples'],
        ['href' => '#ghostable', 'label' => 'How this works with Ghostable'],
        ['href' => '#conclusion', 'label' => 'Conclusion'],
        ['href' => '#faq', 'label' => 'FAQ'],
    ];
    $faqItems = [
        [
            'question' => 'Can I use lowercase or camelCase for env keys?',
            'answer' => 'Stick with uppercase and underscores. It is the most portable and avoids surprises in shells, Docker, and CI systems.',
            'expanded' => true,
        ],
        [
            'question' => 'Is it OK to include dots or dashes in env keys?',
            'answer' => 'Avoid them. Use only letters, digits, and underscores to prevent parsing issues across platforms.',
            'expanded' => true,
        ],
        [
            'question' => 'Do key names need to change between dev, staging, and production?',
            'answer' => 'Keep key names identical across environments; change only the values. This prevents drift and reduces deploy-time mistakes.',
            'expanded' => true,
        ],
        [
            'question' => 'How do I prevent collisions across services?',
            'answer' => 'Use clear prefixes/namespaces (e.g., BILLING_DB_HOST, ANALYTICS_API_KEY) so different services remain distinct.',
            'expanded' => true,
        ],
        [
            'question' => 'Where should I document env keys?',
            'answer' => 'Use a committed template like .env.example plus README or secret-store metadata describing purpose, format, and sensitivity.',
            'expanded' => true,
        ],
    ];
@endphp

@push('meta')
    <x-seo-meta
        title="{{ $guideTitle }}"
        description="{{ $guideDescription }}"
        :keywords="$guideKeywords"
        :image="$guideImage"
    />
@endpush

<x-layouts.guest title="{{ $guideTitle }}" canonical="{{ route('learn.env-naming-conventions') }}">
    <div class="bg-white">
        <div class="px-6 lg:px-8 pt-16 pb-20">
            <div class="mx-auto max-w-6xl">
                <div class="lg:grid lg:grid-cols-[minmax(0,3fr)_320px] lg:items-start lg:gap-12">
                    <div class="space-y-8 max-w-3xl">
                        <header class="space-y-4">
                            <flux:breadcrumbs class="pb-2">
                                <flux:breadcrumbs.item href="{{ route('learn.index') }}" separator="slash">Learn</flux:breadcrumbs.item>
                                <flux:breadcrumbs.item separator="slash">ENV Naming Conventions</flux:breadcrumbs.item>
                            </flux:breadcrumbs>
                            <h1 class="text-4xl font-medium tracking-tighter text-gray-950 sm:text-6xl text-pretty">
                                {{ $guideTitle }}
                            </h1>
                            <p class="text-xl font-medium text-gray-600">
                                {{ $guideDescription }}
                            </p>
                        </header>

                        <x-site.on-this-page :items="$tableOfContents" variant="mobile" class="lg:hidden" />

                        <article class="prose prose-lg prose-slate max-w-none">
                            <div class="space-y-8">
                                <p>Environment variables are a shared language across services, platforms, and teams. Clear, consistent naming keeps configs portable, avoids collisions, and prevents painful “it works locally” bugs. This guide gives you pragmatic naming rules you can adopt as a team standard, plus examples and pitfalls to avoid.</p>
                                <p>If you follow the <flux:link href="https://12factor.net/config">12-factor “Config” principle</flux:link>, sane naming is what makes that portability real across languages, shells, and CI/CD runners.</p>

                                <hr>

                                <h2 id="introduction">Why naming conventions matter</h2>
                                <ul>
                                    <li>ENV variables are often global and reused across services; sloppy naming causes collisions and misreads.</li>
                                    <li>Consistency improves readability for humans and for automation (CI/CD, scripts, secret stores).</li>
                                    <li>Portability depends on sticking to character sets that shells and containers handle reliably.</li>
                                </ul>

                                <hr>

                                <h2 id="conventions">Recommended conventions</h2>
                                <h3>Use UPPERCASE + underscores</h3>
                                <p>Stick to SCREAMING_SNAKE_CASE: <code>DB_HOST</code>, <code>API_KEY</code>, <code>REDIS_URL</code>. It stands out from code variables and aligns with OS and shell conventions.</p>

                                <h3>Only letters, digits, and underscore</h3>
                                <p>Avoid dots, dashes, and spaces. Portable keys look like <code>PAYMENTS_WEBHOOK_SECRET</code>, not <code>payments.webhook-secret</code>.</p>

                                <h3>Meaningful, descriptive names</h3>
                                <p>Be explicit: <code>MAILER_SMTP_HOST</code> beats <code>SMTP1</code>. Add prefixes when multiple services coexist: <code>ANALYTICS_API_KEY</code>, <code>BILLING_DB_HOST</code>.</p>

                                <h3>Namespace third-party or shared packages</h3>
                                <p>If you publish packages or shared tooling that require env vars, prefix them with a vendor or package name to avoid collisions (<code>ACME_FEATURE_FLAG_KEY</code>, <code>ACME_SEARCH_ENDPOINT</code>). Document these expectations so host apps know what to set.</p>

                                <h3>Keep names consistent across environments</h3>
                                <p>The key stays the same in dev, staging, and production; only the value changes. This avoids deploy-time mistakes and config drift and pairs well with the <flux:link href="{{ route('learn.laravel-multi-environment-secrets') }}">Laravel multi-environment secrets strategy</flux:link> outlined in our companion guide.</p>

                                <h3>Start with a letter or underscore</h3>
                                <p>Some platforms misbehave with digit-starting names. Keep it simple: <code>APP_ENV</code> not <code>1ST_APP_ENV</code>.</p>

                                <h3>Document purpose and format</h3>
                                <p>Use <code>.env.example</code>, README, or secret-store metadata to describe what each key does, valid formats, and whether it is sensitive. Tools like <flux:link href="{{ route('home') }}">Ghostable</flux:link> keep that metadata in sync with the actual secrets your team manages.</p>

                                <hr>

                                <h2 id="pitfalls">Common pitfalls</h2>
                                <ul>
                                    <li>Mixing styles (<code>db_host</code>, <code>DATABASE_HOST</code>, <code>DbHost</code>) and causing confusion.</li>
                                    <li>Using dashes, dots, or spaces that break on some shells or make logs ambiguous.</li>
                                    <li>Encoding environment names into keys (<code>PROD_DB_HOST</code> vs <code>DEV_DB_HOST</code>) instead of keeping one key and varying values.</li>
                                    <li>Skipping documentation, leaving teammates guessing or copying secrets around.</li>
                                </ul>

                                <hr>

                                <h2 id="team-standard">Suggested team standard</h2>
                                <ol>
                                    <li>All env keys are UPPERCASE.</li>
                                    <li>Words separated by underscores only.</li>
                                    <li>Allowed characters: A-Z, 0-9, underscore; nothing else.</li>
                                    <li>Names are descriptive and prefixed when needed (<code>SERVICE_DB_HOST</code>, <code>PAYMENT_GATEWAY_API_KEY</code>).</li>
                                    <li>Key names are identical across environments; values differ by environment.</li>
                                    <li>Do not start names with digits.</li>
                                    <li>Document every key: purpose, format, sensitivity, environment usage.</li>
                                    <li>Maintain a central reference (.env.example, docs, or secret-store metadata).</li>
                                </ol>

                                <hr>

                                <h2 id="examples">Good vs. bad examples</h2>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Good</th>
                                            <th>Bad</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><code>APP_ENV</code></td>
                                            <td><code>app-env</code></td>
                                            <td>Avoid dashes; use underscores.</td>
                                        </tr>
                                        <tr>
                                            <td><code>DATABASE_URL</code></td>
                                            <td><code>database.url</code></td>
                                            <td>Dots can break in shells and tooling.</td>
                                        </tr>
                                        <tr>
                                            <td><code>PAYMENTS_WEBHOOK_SECRET</code></td>
                                            <td><code>WEBHOOK1</code></td>
                                            <td>Be descriptive; avoid opaque names.</td>
                                        </tr>
                                        <tr>
                                            <td><code>BILLING_DB_HOST</code></td>
                                            <td><code>PROD_DB_HOST</code></td>
                                            <td>Prefix by service, not environment.</td>
                                        </tr>
                                        <tr>
                                            <td><code>ANALYTICS_API_KEY</code></td>
                                            <td><code>123APIKEY</code></td>
                                            <td>Do not start with digits.</td>
                                        </tr>
                                    </tbody>
                                </table>

                                <hr>

                                <h2 id="ghostable">How this works with Ghostable</h2>
                                <ul>
                                    <li><strong>Consistent keys:</strong> A single naming standard keeps secrets aligned across services and environments.</li>
                                    <li><strong>Collision avoidance:</strong> Prefixing and descriptive names prevent accidental overwrites in shared stores.</li>
                                    <li><strong>Documentation:</strong> Pair .env.example with Ghostable metadata so teammates know purpose, format, and sensitivity.</li>
                                    <li><strong>Cross-stack portability:</strong> Uppercase + underscores works for PHP, Node, Python, Ruby, and CI/CD tooling alike.</li>
                                </ul>

                                <hr>

                                <h2 id="conclusion">Conclusion</h2>
                                <p>Pick a clear naming convention, enforce it, and document it. Uppercase, underscores, descriptive prefixes, and consistent keys across environments will make your configs portable, safer, and easier to automate—whether you are shipping Laravel, Node, or mixed stacks. For a concrete template of how to document keys, see our <flux:link href="{{ route('learn.laravel-env-example') }}">Laravel .env.example guide</flux:link>.</p>
                            </div>

                            <x-site.faq id="faq" class="mt-12" :items="$faqItems" />
                        </article>
                    </div>

                    <aside class="hidden lg:block space-y-4 lg:sticky lg:top-24">
                        @if(!empty($guide['tags']))
                            <x-site.tag-list :tags="$guide['tags']" variant="card" />
                        @endif

                        <x-site.on-this-page :items="$tableOfContents" />
                    </aside>
                </div>

                <div class="flex flex-wrap gap-3 pt-4">
                    <flux:button variant="primary" href="{{ route('learn.index') }}" icon="chevron-left">
                        Back to Learn
                    </flux:button>
                </div>
            </div>
        </div>
    </div>

    <livewire:account.livewire.mailing-list-signup-form/>
</x-layouts.guest>
