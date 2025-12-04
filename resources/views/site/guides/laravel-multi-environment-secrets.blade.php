@inject('learn', '\App\Learn\LearnRepository')
@php
    $guide = $learn->findBySlug('laravel-multi-environment-secrets');
    $guideTitle = $guide['title'] ?? 'Laravel Multi-Environment Secrets Strategy';
    $guideDescription = $guide['description'] ?? 'How to manage Laravel configuration and secrets safely across local, staging, and production.';
    $guideKeywords = $guide['keywords'] ?? [];
    $tableOfContents = [
        ['href' => '#introduction', 'label' => 'Introduction'],
        ['href' => '#environment-basics', 'label' => 'Laravel environment basics'],
        ['href' => '#layout', 'label' => 'Typical multi-environment layout'],
        ['href' => '#managing-envs', 'label' => 'How to manage multiple environments'],
        ['href' => '#best-practices', 'label' => 'Best practices & security'],
        ['href' => '#migration-strategy', 'label' => 'Migration strategy'],
        ['href' => '#when-to-use', 'label' => 'When to use which approach'],
        ['href' => '#how-ghostable-helps', 'label' => 'How Ghostable helps'],
        ['href' => '#conclusion', 'label' => 'Conclusion'],
        ['href' => '#faq', 'label' => 'FAQ'],
    ];
    $faqItems = [
        [
            'question' => 'Should I keep multiple .env files in the repo?',
            'answer' => 'Only commit templates (e.g., .env.example, .env.staging.example). Real secrets belong in a secret manager or injected environment variables on the server.',
            'expanded' => true,
        ],
        [
            'question' => 'Can I call env() directly in my code?',
            'answer' => 'Keep env() calls inside config files and read via config() elsewhere. This avoids surprises with config caching and keeps configuration centralized.',
            'expanded' => true,
        ],
        [
            'question' => 'How do I handle APP_KEY across environments?',
            'answer' => 'Generate a unique APP_KEY per environment with php artisan key:generate. Never commit real keys, and rotate if a key is exposed.',
            'expanded' => true,
        ],
        [
            'question' => 'What about secrets in CI/CD?',
            'answer' => 'Store them in your CI/CD secret store and inject as environment variables during deploy. Avoid baking secrets into build artifacts.',
            'expanded' => true,
        ],
        [
            'question' => 'How do I prevent drift between staging and production?',
            'answer' => 'Use a single source of truth for expected keys (.env.example or config validation), validate during deploy, and keep changes synchronized through PRs and a secret manager.',
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

<x-layouts.guest title="{{ $guideTitle }}" canonical="{{ route('learn.laravel-multi-environment-secrets') }}">
    <div class="bg-white">
        <div class="px-6 lg:px-8 pt-16 pb-20">
            <div class="mx-auto max-w-6xl">
                <div class="lg:grid lg:grid-cols-[minmax(0,3fr)_320px] lg:items-start lg:gap-12">
                    <div class="space-y-8 max-w-3xl">
                        <header class="space-y-4">
                            <flux:breadcrumbs class="pb-2">
                                <flux:breadcrumbs.item href="{{ route('learn.index') }}" separator="slash">Learn</flux:breadcrumbs.item>
                                <flux:breadcrumbs.item separator="slash">Laravel Multi-Environment Secrets</flux:breadcrumbs.item>
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
                                <p>Real-world Laravel apps live in at least three environments: local, staging, and production. Same code, different risk levels, different secrets. A sloppy approach leaks credentials, breaks deploys, and slows teams down. A disciplined approach turns environments into a predictable workflow with clear ownership, safe rotations, and reliable rollbacks.</p>
                                <p>That discipline mirrors the <flux:link href="https://12factor.net/config">12-factor “Config” principle</flux:link>, which keeps code portable by injecting environment-specific settings rather than baking them into the repo.</p>

                                <hr>

                                <h2 id="introduction">Introduction</h2>
                                <p>Multi-environment planning is about more than three .env files. It means:</p>
                                <ul>
                                    <li>Separating code from configuration so the same build can run anywhere.</li>
                                    <li>Reducing leak paths (Git history, logs, backups, screenshots, support tickets).</li>
                                    <li>Keeping environments aligned while still allowing safe experimentation in dev.</li>
                                    <li>Making deploys repeatable and auditable with minimal manual steps.</li>
                                </ul>

                                <hr>

                                <h2 id="environment-basics">Laravel environment basics</h2>
                                <ul>
                                    <li><code>APP_ENV</code> tells Laravel which environment you are in (<code>local</code>, <code>staging</code>, <code>production</code>, etc.). Hosting platforms often inject this for you.</li>
                                    <li><code>.env</code> is loaded at runtime and must not be committed. <code>.env.example</code> documents expected keys without secrets.</li>
                                    <li>For production, prefer injected environment variables or a secret manager instead of plaintext files on disk.</li>
                                    <li>Keep <code>env()</code> calls inside config files; use <code>config()</code> in application code so config caching behaves.</li>
                                </ul>
                                <p>Need a template to start from? Use the pattern in our <flux:link href="{{ route('learn.laravel-env-example') }}">Laravel .env.example guide</flux:link> so every environment begins with the same documented baseline.</p>

                                <hr>

                                <h2 id="layout">Typical multi-environment layout</h2>
                                <p>A simple but effective layout:</p>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Environment</th>
                                            <th>Purpose</th>
                                            <th>Secrets / Config handling</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><strong>Local</strong></td>
                                            <td>Developer work, rapid iteration</td>
                                            <td><code>.env</code> copied from <code>.env.example</code>; personal credentials only</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Staging</strong></td>
                                            <td>Pre-production testing, QA, demos</td>
                                            <td><code>.env.staging</code> or injected variables; secrets kept outside Git</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Production</strong></td>
                                            <td>Live traffic, compliance, high trust</td>
                                            <td>Injected environment variables or secret manager; avoid plaintext files on disk</td>
                                        </tr>
                                    </tbody>
                                </table>

                                <hr>

                                <h2 id="managing-envs">How to manage multiple environments</h2>
                                <h3>Option A: separate .env-style files</h3>
<pre><code># On staging or production during deploy
cp .env.production .env
php artisan config:cache
php artisan migrate --force</code></pre>
                                <ul>
                                    <li><strong>Pros:</strong> simple to grasp, low tooling overhead.</li>
                                    <li><strong>Cons:</strong> risk of commits or leaks, no access control, poor audit trail, harder rotation.</li>
                                </ul>

                                <h3>Option B: injected environment variables / secret manager</h3>
                                <ul>
                                    <li>Use platform- or OS-level environment variables, container runtime variables, or a dedicated secret manager.</li>
                                    <li>Keep secrets encrypted at rest, scoped by environment, and traceable with audit logs.</li>
                                    <li>Rotate credentials without shipping new code or artifacts.</li>
                                </ul>
                                <p>Teams typically reach for <flux:link href="https://developer.hashicorp.com/vault/docs">HashiCorp Vault</flux:link>, <flux:link href="https://docs.aws.amazon.com/systems-manager/latest/userguide/systems-manager-parameter-store.html">AWS Parameter Store</flux:link>, or platform-specific secret stores to make this flow repeatable.</p>

                                <h3>Recommended: templates + injected secrets</h3>
                                <p>Commit templates (<code>.env.example</code>, optionally <code>.env.staging.example</code>) for documentation, but inject real values from a secret manager during deploy. This keeps expectations visible while secrets stay out of Git and build outputs.</p>

                                <hr>

                                <h2 id="best-practices">Best practices &amp; security</h2>
                                <ul>
                                    <li>Never commit real secrets. Only commit templates and obvious placeholders.</li>
                                    <li>Use uppercase, underscored variable names; avoid spaces or punctuation.</li>
                                    <li>Validate required keys early (bootstrap scripts or service providers) to fail fast on missing configuration.</li>
                                    <li>Cache config in staging/production (<code>php artisan config:cache</code>), but remember to warm it after updates.</li>
                                    <li>Back up secret stores securely and track who can read or rotate credentials.</li>
                                    <li>Document ownership: who updates staging vs. production values, and how rotations are approved.</li>
                                </ul>

                                <hr>

                                <h2 id="migration-strategy">Migration strategy: single env to multi-env + secret manager</h2>
                                <ol>
                                    <li>Audit every environment variable your app relies on; note which are sensitive.</li>
                                    <li>Create environment-specific templates (<code>.env.staging.example</code>, <code>.env.production.example</code>) and refresh <code>.env.example</code>.</li>
                                    <li>Choose a secret manager or hosting-level env store; populate it per environment.</li>
                                    <li>Update deploy scripts/CI to inject variables for staging and production, then run <code>config:cache</code> and any migrations.</li>
                                    <li>Refactor code to read via <code>config()</code> so caching is safe.</li>
                                    <li>Document the workflow: who adds keys, how to rotate, how to onboard new teammates.</li>
                                    <li>Test in staging first. Verify expected keys, log redaction, and that secrets never touch artifacts.</li>
                                </ol>

                                <hr>

                                <h2 id="when-to-use">When to use which approach</h2>
                                <ul>
                                    <li><strong>Local development:</strong> <code>.env</code> from <code>.env.example</code>; developer-owned credentials.</li>
                                    <li><strong>Shared staging:</strong> injected env vars or a secrets manager; optional <code>.env.staging</code> if tightly controlled.</li>
                                    <li><strong>Production:</strong> always injected env vars or secret manager; avoid plaintext files; enforce access controls and audit logs.</li>
                                    <li><strong>Frequent rotations or multiple services:</strong> secret manager + environment-aware config, with validation to prevent drift.</li>
                                </ul>

                                <hr>

                                <h2 id="how-ghostable-helps">How Ghostable helps</h2>
                                <ul>
                                    <li><strong>Environment templates:</strong> keep <code>.env.example</code> accurate and in sync with staging/production expectations.</li>
                                    <li><strong>Validation:</strong> catch missing or extra keys before deploy; enforce parity across environments.</li>
                                    <li><strong>Secure sharing:</strong> share secrets without passing around files; audit who accessed what, and when.</li>
                                    <li><strong>Rotation and drift control:</strong> rotate credentials centrally and push updates without leaking into Git or artifacts.</li>
                                </ul>
                                <p>Want these guardrails without gluing tools together? <flux:link href="{{ route('pricing') }}">Ghostable</flux:link> ships environment templates, validation, and audited sharing out of the box.</p>

                                <hr>

                                <h2 id="conclusion">Conclusion</h2>
                                <p>Treat environments as first-class citizens. Document expected keys, keep secrets out of Git, inject values from a secure store, and validate at deploy time. With a clean workflow, local development stays fast, staging stays trustworthy, and production stays locked down.</p>
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
