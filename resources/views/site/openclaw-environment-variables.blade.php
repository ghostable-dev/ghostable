@php
    $title = 'OpenClaw Environment Variables: Setup, Secrets, and Best Practices';
    $description = 'A practical guide to OpenClaw environment variables, .env setup, secret handling, local workflows, team access, and safer deployment with Ghostable.';
    $keywords = [
        'openclaw environment variables',
        'openclaw env setup',
        'openclaw secrets management',
        'openclaw env file',
        'openclaw security',
    ];
    $tableOfContents = [
        ['href' => '#what-openclaw-env-vars-are', 'label' => 'What OpenClaw env vars are'],
        ['href' => '#env-setup', 'label' => 'OpenClaw .env setup'],
        ['href' => '#secret-risks', 'label' => 'Where secrets get risky'],
        ['href' => '#team-workflows', 'label' => 'Local and team workflows'],
        ['href' => '#how-ghostable-helps', 'label' => 'How Ghostable helps'],
        ['href' => '#faq', 'label' => 'FAQ'],
    ];
    $faqItems = [
        [
            'question' => 'Where should OpenClaw environment variables live?',
            'answer' => 'Use the environment file or host-level configuration OpenClaw reads at startup, but keep the source of truth in a managed system instead of passing raw .env files around.',
        ],
        [
            'question' => 'Should OpenClaw secrets be committed to Git?',
            'answer' => 'No. Commit templates and documentation only. Real API keys, passwords, tokens, and signing secrets should stay out of Git, tickets, chat, and screenshots.',
        ],
        [
            'question' => 'How does Ghostable fit into an OpenClaw workflow?',
            'answer' => 'Ghostable stores environment values in a shared workspace, tracks changes, controls access, and can write the runtime env file OpenClaw already expects before startup.',
        ],
    ];
@endphp

@push('meta')
    <x-seo-meta
        title="{{ $title }}"
        description="{{ $description }}"
        :keywords="$keywords"
    />
    <x-article-schema
        title="{{ $title }}"
        description="{{ $description }}"
        :keywords="$keywords"
        :url="route('openclaw-environment-variables')"
        section="Guide"
    />
    <x-breadcrumb-schema :items="[
        ['name' => 'Home', 'item' => route('home')],
        ['name' => 'OpenClaw Environment Variables', 'item' => route('openclaw-environment-variables')],
    ]" />
    <x-faq-schema :items="$faqItems" />
@endpush

<x-layouts.guest title="{{ $title }}" canonical="{{ route('openclaw-environment-variables') }}">
    <div class="bg-white">
        <div class="px-6 pb-20 pt-16 lg:px-8">
            <div class="mx-auto max-w-6xl">
                <div class="lg:grid lg:grid-cols-[minmax(0,3fr)_320px] lg:items-start lg:gap-12">
                    <div class="max-w-3xl space-y-8">
                        <header class="space-y-5">
                            <flux:breadcrumbs class="pb-2">
                                <flux:breadcrumbs.item href="{{ route('home') }}" separator="slash">Home</flux:breadcrumbs.item>
                                <flux:breadcrumbs.item separator="slash">OpenClaw environment variables</flux:breadcrumbs.item>
                            </flux:breadcrumbs>

                            <p class="text-sm font-semibold uppercase tracking-[0.16em] text-emerald-700">
                                OpenClaw env guide
                            </p>
                            <h1 class="text-pretty text-4xl font-medium tracking-tighter text-gray-950 sm:text-6xl">
                                {{ $title }}
                            </h1>
                            <p class="text-xl font-medium text-gray-600">
                                Use this as the durable reference for setting up OpenClaw env files, keeping secrets out of unsafe places, and giving your team a repeatable workflow.
                            </p>
                            <div class="flex flex-wrap gap-3">
                                <flux:button href="{{ route('register') }}" variant="primary">
                                    Start free
                                </flux:button>
                                <flux:button href="{{ route('integrations.openclaw') }}" icon:trailing="arrow-right" variant="ghost">
                                    View the OpenClaw integration
                                </flux:button>
                            </div>
                        </header>

                        <x-site.on-this-page :items="$tableOfContents" variant="mobile" class="lg:hidden" />

                        <article class="prose prose-lg prose-slate max-w-none">
                            <div class="space-y-8">
                                <p>OpenClaw still depends on ordinary environment configuration: keys, URLs, ports, tokens, feature flags, and credentials that need to exist before the app or container starts. The hard part is not knowing that these values exist. The hard part is keeping them correct across local development, CI, staging, and production without leaking secrets or letting files drift.</p>
                                <p>This guide covers the practical setup choices that matter most: how to structure an OpenClaw env file, where secrets become risky, how teams should handle local and deploy workflows, and where Ghostable fits.</p>

                                <hr>

                                <h2 id="what-openclaw-env-vars-are">What OpenClaw environment variables are</h2>
                                <p>OpenClaw environment variables are runtime configuration values that control how OpenClaw connects to services, authenticates requests, stores data, and enables optional behavior. They usually include values such as app URLs, database credentials, API keys, queue settings, object storage details, and integration tokens.</p>
                                <p>Treat env keys as a contract. Key names should stay consistent across environments; values should change per environment. Pair this with clear naming from the <flux:link href="{{ route('learn.env-naming-conventions') }}">ENV variable naming conventions guide</flux:link> so developers and automation can understand the same file.</p>

                                <h2 id="env-setup">A practical .env setup for OpenClaw</h2>
                                <p>The safest setup has two parts: a committed template that documents expected keys, and an uncommitted runtime env file that contains real values.</p>
<pre><code># .env.openclaw.example
OPENCLAW_APP_URL=https://openclaw.example.com
OPENCLAW_DATABASE_URL=&lt;database-url&gt;
OPENCLAW_REDIS_URL=&lt;redis-url&gt;
OPENCLAW_STORAGE_BUCKET=&lt;bucket-name&gt;
OPENCLAW_API_KEY=&lt;api-key&gt;
OPENCLAW_WEBHOOK_SECRET=&lt;webhook-secret&gt;</code></pre>
                                <p>Keep the template useful but non-sensitive. The real env file can be generated by Ghostable before OpenClaw starts, written to the path your Docker Compose file or host service already reads, and treated as disposable runtime output. For a step-by-step setup walkthrough, read <flux:link href="{{ route('blog.view', 'openclaw-env-setup') }}">How to Set Up OpenClaw Environment Variables</flux:link>. For template structure, the same principles from the <flux:link href="{{ route('learn.laravel-env-example') }}">.env.example guide</flux:link> apply here.</p>
                                <p>For a deeper walkthrough of file structure, paths, and example values, read the <flux:link href="{{ route('blog.view', 'openclaw-env-file') }}">OpenClaw .env file guide</flux:link>.</p>

                                <h2 id="secret-risks">Where OpenClaw secrets get risky</h2>
                                <p>Secrets usually leak through workflow shortcuts, not because someone set out to be careless. Watch for these patterns:</p>
                                <ul>
                                    <li>Copying a real <code>.env</code> into a committed example file.</li>
                                    <li>Sharing OpenClaw tokens in chat, tickets, docs, or screenshots.</li>
                                    <li>Reusing one production value in local, staging, and CI.</li>
                                    <li>Letting generated env files become a second source of truth.</li>
                                    <li>Giving every developer access to every environment by default.</li>
                                </ul>
                                <p>The fix is boring on purpose: keep secrets out of Git, scope access by environment, rotate values when they are exposed, and make the generated OpenClaw env file replaceable. For a deeper guide to access, rotation, and source-of-truth workflows, read <flux:link href="{{ route('blog.view', 'openclaw-secrets-management') }}">OpenClaw Secrets Management</flux:link>. For a security checklist, read <flux:link href="{{ route('blog.view', 'openclaw-environment-variables-security') }}">OpenClaw Environment Variables Security</flux:link>.</p>

                                <h2 id="team-workflows">Local, dev, and team workflows</h2>
                                <p>A single-person OpenClaw install can survive with a hand-edited env file. A team cannot. The workflow needs to answer who changed a value, which environment it changed in, and how OpenClaw picked it up.</p>
                                <h3>Local development</h3>
                                <p>Developers should pull only the development values they need, then write them into a local OpenClaw env file. Local secrets should be easy to refresh and easy to delete.</p>
<pre><code>ghostable env pull --env development --file .env.openclaw</code></pre>
                                <h3>CI and deploys</h3>
                                <p>CI and production runners should use scoped deployment tokens, not a developer account. Generate the env file immediately before startup so OpenClaw receives current values without long-lived files floating around.</p>
<pre><code>ghostable env deploy --token $GHOSTABLE_CI_TOKEN --file .env.openclaw
docker compose --env-file .env.openclaw up -d</code></pre>
                                <h3>Production operations</h3>
                                <p>For production, keep changes reviewable and auditable. If a secret rotates, update the value in one source of truth, regenerate the OpenClaw env file during deploy, and avoid manual edits on the host.</p>

                                <h2 id="how-ghostable-helps">How Ghostable helps OpenClaw teams</h2>
                                <p>Ghostable gives OpenClaw teams a managed place for environment values, permissions, history, and deployment output. The OpenClaw integration is intentionally env-file native: it does not require OpenClaw to know about Ghostable. Ghostable writes the file OpenClaw already expects.</p>
                                <ul>
                                    <li><strong>Source of truth:</strong> store values once instead of maintaining scattered copies.</li>
                                    <li><strong>Scoped access:</strong> separate development, staging, and production permissions.</li>
                                    <li><strong>Deployment tokens:</strong> let automation read only the values it needs.</li>
                                    <li><strong>Audit history:</strong> understand who changed a value and when.</li>
                                    <li><strong>Disposable output:</strong> regenerate OpenClaw env files instead of hand-editing them.</li>
                                </ul>
                                <p>For the product-specific setup, see the <flux:link href="{{ route('integrations.openclaw') }}">Ghostable + OpenClaw integration page</flux:link>.</p>

                                <h2 id="faq">FAQ</h2>
                                @foreach($faqItems as $item)
                                    <h3>{{ $item['question'] }}</h3>
                                    <p>{!! $item['answer'] !!}</p>
                                @endforeach
                            </div>
                        </article>
                    </div>

                    <aside class="sticky top-24 hidden space-y-6 lg:block">
                        <x-site.on-this-page :items="$tableOfContents" />
                        <div class="rounded-2xl border border-gray-200 bg-zinc-50 p-5">
                            <p class="text-sm font-semibold uppercase tracking-[0.12em] text-gray-500">
                                Related reading
                            </p>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <flux:link href="{{ route('blog.view', 'openclaw-env-setup') }}" variant="ghost" class="inline-flex font-semibold text-gray-900">
                                        How to Set Up OpenClaw Environment Variables
                                    </flux:link>
                                    <p class="mt-1 text-sm text-gray-700">
                                        Step-by-step setup for local files, generated env output, and startup flow.
                                    </p>
                                </div>
                                <div>
                                    <flux:link href="{{ route('blog.view', 'openclaw-env-file') }}" variant="ghost" class="inline-flex font-semibold text-gray-900">
                                        OpenClaw .env File Guide
                                    </flux:link>
                                    <p class="mt-1 text-sm text-gray-700">
                                        File structure, paths, examples, and what belongs in a template versus a real runtime env file.
                                    </p>
                                </div>
                                <div>
                                    <flux:link href="{{ route('blog.view', 'openclaw-secrets-management') }}" variant="ghost" class="inline-flex font-semibold text-gray-900">
                                        OpenClaw Secrets Management
                                    </flux:link>
                                    <p class="mt-1 text-sm text-gray-700">
                                        Access control, rotation, source-of-truth workflows, and avoiding secret sprawl.
                                    </p>
                                </div>
                                <div>
                                    <flux:link href="{{ route('blog.view', 'openclaw-environment-variables-security') }}" variant="ghost" class="inline-flex font-semibold text-gray-900">
                                        OpenClaw Environment Variables Security
                                    </flux:link>
                                    <p class="mt-1 text-sm text-gray-700">
                                        Common leak paths, unsafe shortcuts, and safer workflows for OpenClaw teams.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-2xl border border-gray-200 bg-zinc-50 p-5">
                            <p class="text-sm font-semibold uppercase tracking-[0.12em] text-gray-500">
                                Next step
                            </p>
                            <p class="mt-3 text-sm text-gray-700">
                                See the integration page for the product-specific OpenClaw workflow and deployment commands.
                            </p>
                            <flux:button href="{{ route('integrations.openclaw') }}" icon:trailing="arrow-right" variant="ghost" class="mt-4">
                                OpenClaw integration
                            </flux:button>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </div>
</x-layouts.guest>
