@inject('learn', '\App\Learn\LearnRepository')
@php
    $guide = $learn->findBySlug('laravel-env-example');
    $guideTitle = $guide['title'] ?? 'Laravel .env.example — The Definitive Guide';
    $guideDescription = $guide['description'] ?? 'A practical reference for creating, maintaining, and sharing .env.example files for Laravel teams.';
    $guideKeywords = $guide['keywords'] ?? [];
    $tableOfContents = [
        ['href' => '#what-env-example-is-used-for', 'label' => 'What .env.example is used for'],
        ['href' => '#common-misconceptions', 'label' => 'Common misconceptions & mistakes'],
        ['href' => '#recommended-conventions', 'label' => 'Recommended conventions for .env.example'],
        ['href' => '#security-implications', 'label' => 'Security implications'],
        ['href' => '#team-workflow', 'label' => 'Team workflow best practices'],
        ['href' => '#how-ghostable-helps', 'label' => 'How Ghostable helps'],
        ['href' => '#conclusion', 'label' => 'Conclusion'],
        ['href' => '#faq', 'label' => 'FAQ'],
    ];
    $faqItems = [
        [
            'question' => 'What is the purpose of .env.example?',
            'answer' => 'It’s a template for <code>.env</code> that documents expected environment variables and their shapes—without exposing secrets.',
            'expanded' => true,
        ],
        [
            'question' => 'What should be in .env.example?',
            'answer' => 'All required keys, important optional keys, and safe placeholders. Never live passwords, tokens, keys, or real <code>APP_KEY</code> values.',
            'expanded' => true,
        ],
        [
            'question' => 'What is the naming convention for .env.example?',
            'answer' => 'Use <code>.env.example</code> at the project root. If you keep multiple templates, use explicit names like <code>.env.staging.example</code>.',
            'expanded' => true,
        ],
        [
            'question' => 'What is the default Laravel environment file name?',
            'answer' => '<code>.env</code> — it loads at runtime and should not be committed. <code>.env.example</code> is the committed template.',
            'expanded' => true,
        ],
        [
            'question' => 'Should .env.example contain real values?',
            'answer' => 'No. Use non-sensitive defaults and obvious placeholders. Real secrets belong in a secret manager or non-committed environment configuration.',
            'expanded' => true,
        ],
    ];
@endphp

@push('meta')
    <x-seo-meta
        title="{{ $guideTitle }}"
        description="{{ $guideDescription }}"
        :keywords="$guideKeywords"
    />
@endpush

<x-layouts.guest title="Laravel .env.example — The Definitive Guide" canonical="{{ route('learn.laravel-env-example') }}">
    <div class="bg-white">
        <div class="px-6 lg:px-8 pt-16 pb-20">
            <div class="mx-auto max-w-6xl">
                <div class="lg:grid lg:grid-cols-[minmax(0,3fr)_320px] lg:items-start lg:gap-12">
                    <div class="space-y-8 max-w-3xl">
                        <header class="space-y-4">
                            <flux:breadcrumbs class="pb-2">
                                <flux:breadcrumbs.item href="{{ route('learn.index') }}" separator="slash">Learn</flux:breadcrumbs.item>
                                <flux:breadcrumbs.item separator="slash">Laravel .env.example</flux:breadcrumbs.item>
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
                    <p><code>.env.example</code> looks harmless and boring, which is exactly why teams underestimate it. Handled correctly, it’s the contract for your application’s configuration: a single source of truth that tells every developer, CI job, and server what environment variables are expected without ever exposing real secrets. Handled badly, it becomes a half-broken copy of <code>.env</code>, a source of confusion, and occasionally a stepping stone to leaking secrets.</p>
                    <p>This page is a permanent reference for how to treat <code>.env.example</code> in Laravel (and similar frameworks), how to structure it, and how to integrate it into team workflows and secret management. Laravel’s own <flux:link href="https://laravel.com/docs/configuration">configuration docs</flux:link> reinforce the same pattern: env-driven config with sane defaults and no secrets in Git.</p>

                    <hr>

                    <h2 id="what-env-example-is-used-for">What .env.example is used for</h2>
                    <p>At its core, <code>.env.example</code> is a template for your real environment file (<code>.env</code> in Laravel).</p>
                    <h3>1. Onboarding new developers</h3>
                    <p>For a new developer, <code>.env.example</code> answers:</p>
                    <ul>
                        <li>Which environment variables are required?</li>
                        <li>Which ones are optional?</li>
                        <li>What shape and type values should have?</li>
                        <li>What needs real credentials vs. what can stay as default?</li>
                    </ul>
<pre><code>cp .env.example .env
php artisan key:generate
# then fill in the blanks: DB credentials, mail, queue, etc.</code></pre>
                    <p>Without a maintained <code>.env.example</code>, onboarding turns into guessing, Slack messages, and “just send me your .env” (which is a security red flag).</p>

                    <h3>2. Defining expected environment keys</h3>
                    <p><code>.env.example</code> acts as a living schema for environment variables:</p>
                    <ul>
                        <li>It lists all keys your app expects.</li>
                        <li>It shows grouping and naming conventions.</li>
                        <li>It reveals dependencies (e.g. enabling a feature requires several keys to be set).</li>
                    </ul>
                    <p>Think of it as a <code>config.yml</code> for humans, but implemented with env vars for the framework.</p>

                    <h3>3. Preventing real secrets from being committed</h3>
                    <p>The reason <code>.env.example</code> exists instead of committing <code>.env</code> is simple:</p>
                    <ul>
                        <li><code>.env</code> contains real values: database passwords, API tokens, signing keys.</li>
                        <li><code>.env.example</code> contains placeholders or safe defaults, which can be public.</li>
                    </ul>
                    <p>By committing only <code>.env.example</code> you document the configuration shape, but you avoid committing real secrets to Git, CI logs, or anywhere else they can linger.</p>

                    <hr>

                    <h2 id="common-misconceptions">Common misconceptions &amp; mistakes</h2>
                    <h3>1. Storing real values in .env.example</h3>
                    <p>This is the most dangerous mistake: copying <code>.env</code> → <code>.env.example</code> and committing it.</p>
                    <p><strong>Problems:</strong></p>
                    <ul>
                        <li>Secrets land in version control and history.</li>
                        <li>Public or shared repos now expose your infrastructure.</li>
                    </ul>
                    <p><strong>Fix:</strong> <code>.env.example</code> should never contain live credentials, private keys, or long-lived secrets.</p>

                    <h3>2. Treating it as a second .env</h3>
                    <p>Some teams treat <code>.env.example</code> like a dev environment and keep working values inside it. Result: confusion about source of truth and drift between files.</p>
                    <p><strong>Fix:</strong> reference-only. The app should load from <code>.env</code> (or injected env vars), not from <code>.env.example</code>.</p>

                    <h3>3. Missing required keys</h3>
                    <p>Incomplete templates lead to failing migrations, broken CI, and silent misbehavior.</p>
                    <p><strong>Fix:</strong> treat missing keys as a bug; keep <code>.env.example</code> in sync with code expectations.</p>

                    <hr>

                    <h2 id="recommended-conventions">Recommended conventions for .env.example</h2>
                    <h3>1. Naming the file</h3>
                    <ul>
                        <li>Use <code>.env.example</code> in the project root.</li>
                        <li>If you keep multiple templates, name them explicitly (e.g., <code>.env.staging.example</code>), but most teams only need one plus a secret manager.</li>
                    </ul>
                    <p>Pair the file naming with consistent key naming from the <flux:link href="{{ route('learn.env-naming-conventions') }}">ENV naming conventions guide</flux:link> so templates stay readable across services.</p>

                    <h3>2. What should be in .env.example?</h3>
                    <p><strong>Include:</strong> required keys, important optional keys, safe defaults (e.g., <code>APP_ENV=local</code>).</p>
                    <p><strong>Avoid:</strong> real credentials or environment-specific secrets.</p>
<pre><code>APP_NAME="Your App Name"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

LOG_CHANNEL=stack

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

CACHE_DRIVER=file
QUEUE_CONNECTION=sync

MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.test"
MAIL_FROM_NAME="${APP_NAME}"</code></pre>
                    <p>Placeholders are non-sensitive and clearly not real. This can be safely committed.</p>

                    <h3>3. Required keys — “must not boot without these”</h3>
                    <p>Ensure <code>.env.example</code> lists the essentials: <code>APP_ENV</code>, <code>APP_DEBUG</code>, <code>APP_URL</code>, <code>APP_KEY</code> (blank placeholder), DB connection details, cache/session/queue drivers.</p>

                    <h3>4. Default placeholders</h3>
                    <p>Make placeholders obviously fake and descriptive (e.g., <code>DB_PASSWORD=&lt;your-local-db-password&gt;</code>). Avoid cute or real-looking defaults.</p>

                    <hr>

                    <h2 id="security-implications">Security implications</h2>
                    <h3>1. Why real secrets should never be included</h3>
                    <p>Committed secrets propagate to every clone, fork, backup, and CI artifact. Assume anything in Git may one day be exposed.</p>

                    <h3>2. APP_KEY considerations in Laravel</h3>
<pre><code>APP_KEY=
# or
# APP_KEY=base64:your-generated-key-here</code></pre>
                    <ul>
                        <li>Generate per environment with <code>php artisan key:generate</code>.</li>
                        <li>Never reuse keys across environments; never commit real keys.</li>
                        <li>If exposed, rotate and assume encrypted data is compromised.</li>
                    </ul>

                    <h3>3. Common leak patterns</h3>
                    <ul>
                        <li>Copying <code>.env</code> → <code>.env.example</code> to “share config”.</li>
                        <li>Zipping projects with <code>.env</code> and sharing in tickets or chat.</li>
                        <li>Public repos including <code>.env</code> from a bad <code>.gitignore</code>.</li>
                        <li>Backups or CI artifacts storing <code>.env</code>.</li>
                    </ul>
                    <p><strong>Mitigations:</strong> keep <code>.env</code> ignored, never copy it into tracked files, educate the team, and use a secret manager. The <flux:link href="https://cheatsheetseries.owasp.org/cheatsheets/Secrets_Management_Cheat_Sheet.html">OWASP Secrets Management Cheat Sheet</flux:link> is a solid checklist for hardening these practices.</p>
<pre><code>.env
.env.*
!.env.example</code></pre>

                    <hr>

                    <h2 id="team-workflow">Team workflow best practices</h2>
                    <h3>1. Keeping .env.example in sync</h3>
                    <ul>
                        <li>When you add an env variable in code, update <code>.env.example</code> in the same PR.</li>
                        <li>Code review checklist: “Did you touch env variables? If yes, is <code>.env.example</code> updated?”</li>
                        <li>Optional: CI checks that required keys exist.</li>
                    </ul>

                    <h3>2. Using validation rules</h3>
                    <p>Validate critical env vars early. Example (conceptual):</p>
<pre><code>$required = [
    'APP_ENV',
    'APP_URL',
    'DB_CONNECTION',
    'DB_HOST',
    'DB_DATABASE',
    'DB_USERNAME',
];

foreach ($required as $key) {
    if (empty(env($key))) {
        throw new RuntimeException(\"Missing required environment variable: {$key}\");
    }
}</code></pre>

                    <h3>3. How modern secret managers help</h3>
                    <p>Secret managers store sensitive values centrally, provide audited access, and enable rotation without touching Git. <code>.env.example</code> remains the template, while real secrets are injected at deploy/runtime. Whether you prefer <flux:link href="https://developer.hashicorp.com/vault/docs">HashiCorp Vault</flux:link> or <flux:link href="https://docs.aws.amazon.com/secretsmanager/latest/userguide/intro.html">AWS Secrets Manager</flux:link>, the workflow stays the same.</p>

                    <hr>

                        <h2 id="how-ghostable-helps">How Ghostable helps</h2>
                        <ul>
                            <li><strong>Validating expected keys:</strong> flag missing or extra variables across environments.</li>
                            <li><strong>Preventing drift:</strong> track changes so <code>.env.example</code>, local files, and remote configs stay aligned.</li>
                            <li><strong>Safe sharing:</strong> share structure and values securely without passing raw <code>.env</code> files around.</li>
                        </ul>
                        <p>Need tooling around these habits? <flux:link href="{{ route('pricing') }}">Ghostable</flux:link> bakes validation, sync, and secure sharing into one workflow.</p>

                        <hr>

                        <h2 id="conclusion">Conclusion</h2>
                        <p><code>.env.example</code> is a contract for how your application is configured. Keep it complete and up to date, never store real secrets inside it, enforce validation and sync in your workflows, and pair it with a proper secret management strategy. You’ll get faster onboarding, fewer config outages, and less risk of leaks.</p>
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
