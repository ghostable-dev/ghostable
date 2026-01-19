@inject('learn', '\App\Learn\LearnRepository')
@php
    $tutorial = $learn->findBySlug('env-validation-tutorial');
    $tutorialTitle = $tutorial['title'] ?? 'Validate your .env files with Ghostable';
    $tutorialDescription = $tutorial['description'] ?? 'A practical walkthrough for defining Ghostable validation schemas, running checks locally, and blocking deploys when config drifts.';
    $tutorialKeywords = $tutorial['keywords'] ?? [];
    $tutorialImage = $tutorial['image'] ?? null;
    $tableOfContents = [
        ['href' => '#what-youll-build', 'label' => 'What you\'ll build'],
        ['href' => '#prerequisites', 'label' => 'Prerequisites'],
        ['href' => '#define-schema', 'label' => '1. Define your validation schema'],
        ['href' => '#framework-rules', 'label' => '2. Add rules for your framework'],
        ['href' => '#validate-locally', 'label' => '3. Validate locally before merging'],
        ['href' => '#ci-cd', 'label' => '4. Gate deploys in CI/CD'],
        ['href' => '#anti-patterns', 'label' => 'What not to do'],
        ['href' => '#deployment-benefits', 'label' => 'Why validation protects deploys'],
        ['href' => '#next-steps', 'label' => 'Next steps'],
    ];
@endphp

@push('meta')
    <x-seo-meta
        title="{{ $tutorialTitle }} - Ghostable Tutorials"
        description="{{ $tutorialDescription }}"
        :keywords="$tutorialKeywords"
        :image="$tutorialImage"
    />
    <x-article-schema
        title="{{ $tutorialTitle }}"
        description="{{ $tutorialDescription }}"
        :keywords="$tutorialKeywords"
        :image="$tutorialImage"
        :url="route('learn.env-validation-tutorial')"
        section="Tutorial"
    />
    <x-breadcrumb-schema :items="[
        ['name' => 'Learn', 'item' => route('learn.index')],
        ['name' => 'Tutorials', 'item' => route('learn.index')],
        ['name' => $tutorialTitle, 'item' => route('learn.env-validation-tutorial')],
    ]" />
@endpush

<x-layouts.guest title="{{ $tutorialTitle }}" canonical="{{ route('learn.env-validation-tutorial') }}">
    <div class="bg-white">
        <div class="px-6 lg:px-8 pt-16 pb-20">
            <div class="mx-auto max-w-6xl">
                <div class="lg:grid lg:grid-cols-[minmax(0,3fr)_320px] lg:items-start lg:gap-12">
                    <div class="space-y-8 max-w-3xl">
                        <header class="space-y-4">
                            <flux:breadcrumbs class="pb-2">
                                <flux:breadcrumbs.item href="{{ route('learn.index') }}" separator="slash">Learn</flux:breadcrumbs.item>
                                <flux:breadcrumbs.item separator="slash">Tutorials</flux:breadcrumbs.item>
                                <flux:breadcrumbs.item separator="slash">ENV validation</flux:breadcrumbs.item>
                            </flux:breadcrumbs>
                            <h1 class="text-4xl font-medium tracking-tighter text-gray-950 sm:text-6xl text-pretty">
                                {{ $tutorialTitle }}
                            </h1>
                            <p class="text-xl font-medium text-gray-600">
                                {{ $tutorialDescription }}
                            </p>
                        </header>

                        <x-site.on-this-page :items="$tableOfContents" variant="mobile" class="lg:hidden" />

                        <article class="prose prose-lg prose-slate max-w-none">
                            <div class="space-y-8">
                                <p>Ghostable's validation keeps bad environment variables out of your deployments without ever uploading plaintext values or schema details. In this tutorial you'll define validation rules, run them locally, wire them into CI, and learn patterns that keep Laravel, Node/Next.js, Rails, and Django apps from shipping broken config.</p>

                                <h2 id="what-youll-build">What you'll build</h2>
                                <ul>
                                    <li>A project-level schema in <code>.ghostable/schema.yaml</code> with sensible defaults.</li>
                                    <li>Environment overrides (e.g., production-only rules) in <code>.ghostable/schemas/&lt;env&gt;.yaml</code>.</li>
                                    <li>A repeatable command: <code>ghostable env validate --env &lt;env&gt;</code> that fails fast when config drifts.</li>
                                    <li>A CI job that blocks merges/deploys when validation fails.</li>
                                </ul>

                                <h2 id="prerequisites">Prerequisites</h2>
                                <ul>
                                    <li>Ghostable CLI installed and authenticated (<code>ghostable login --token $GHOSTABLE_TOKEN</code>).</li>
                                    <li>A project initialized with <code>.ghostable/ghostable.yaml</code> so the CLI knows which project/environment to target.</li>
                                    <li>Local <code>.env</code> files for the environments you want to validate.</li>
                                    <li>Node.js available if you're running the CLI in CI (see GitHub Actions example below).</li>
                                </ul>

                                <h2 id="define-schema">1. Define your validation schema</h2>
                                <p>All validation lives locally inside <code>.ghostable</code> (see the <flux:link href="https://docs.ghostable.dev/v2/digging-deeper/validation" target="_blank">validation docs</flux:link>). Start with a global schema file and add per-environment overrides only when needed.</p>

<pre><code class="language-yaml">.ghostable/
  ghostable.yaml
  schema.yaml           # global rules
  schemas/
    production.yaml     # env-specific overrides
    staging.yaml
</code></pre>

                                <p>Create <code>.ghostable/schema.yaml</code> with baseline rules that apply everywhere:</p>
<pre><code class="language-yaml">APP_NAME:
  - required
  - string
  - max:64

APP_ENV:
  - required
  - in:local,staging,production

APP_DEBUG:
  - required
  - boolean

APP_URL:
  - required
  - url

LOG_CHANNEL:
  - required
  - in:stack,stdout
</code></pre>

                                <p>Add overrides for stricter environments (e.g., production):</p>
<pre><code class="language-yaml"># .ghostable/schemas/production.yaml
APP_DEBUG:
  - required
  - boolean
  - in:false

APP_KEY:
  - required
  - starts_with:base64:
  - min:44

QUEUE_CONNECTION:
  - required
  - in:redis,sqs
</code></pre>

                                <p>Ghostable merges the global schema with the matching override file before validating your <code>.env</code>.</p>

                                <h2 id="framework-rules">2. Add rules for your framework</h2>
                                <p>Here are practical rule sets you can drop into <code>schema.yaml</code>. Tweak names and allowed values to match your stack.</p>

                                <h3 id="laravel-rules" class="text-xl font-semibold">Laravel</h3>
<pre><code class="language-yaml">APP_KEY:
  - required
  - starts_with:base64:
  - min:44

SESSION_DRIVER:
  - required
  - in:file,database,redis

CACHE_DRIVER:
  - required
  - in:file,redis,memcached,dynamodb

MAIL_MAILER:
  - required
  - in:smtp,log,mailgun,postmark,sendmail

DB_CONNECTION:
  - required
  - in:mysql,pgsql,sqlsrv,sqlite
</code></pre>

                                <h3 id="nextjs-rules" class="text-xl font-semibold">Next.js / Node</h3>
<pre><code class="language-yaml">NODE_ENV:
  - required
  - in:development,staging,production

NEXT_PUBLIC_API_URL:
  - required
  - url

DATABASE_URL:
  - required
  - regex:^postgres://

JWT_SECRET:
  - required
  - min:32
  - starts_with:sk_
</code></pre>

                                <h3 id="django-rules" class="text-xl font-semibold">Django / Rails</h3>
<pre><code class="language-yaml">SECRET_KEY:
  - required
  - min:50

DJANGO_SETTINGS_MODULE:
  - required
  - ends_with:.settings

RAILS_ENV:
  - required
  - in:development,test,production

DATABASE_URL:
  - required
  - regex:^postgres://

ALLOWED_HOSTS:
  - required
  - string
</code></pre>

                                <p>Prefer <code>in:</code> for controlled enums, <code>starts_with</code>/<code>regex</code> for keys and URLs, and <code>required</code> everywhere you expect the app to boot.</p>

                                <h2 id="validate-locally">3. Validate locally before merging</h2>
                                <p>Run validation against any environment. The CLI loads <code>schema.yaml</code>, merges the matching override, and compares against your resolved <code>.env</code> (you can pass <code>--file</code> for non-standard names).</p>
<pre><code class="language-bash">ghostable env validate --env production
# or validate a custom file:
ghostable env validate --env staging --file .env.staging</code></pre>

                                <p>Failures are human-readable and the command exits non-zero:</p>
<pre><code class="language-bash">X APP_KEY must start with "base64:"
X APP_DEBUG must be "false"
! QUEUE_CONNECTION is set to sync (recommended: redis)</code></pre>
                                <p>Fix values locally, rerun, and only push or deploy once validation passes.</p>

                                <h2 id="ci-cd">4. Gate deploys in CI/CD</h2>
                                <p>Because validation happens locally (<flux:link href="{{ route('learn.zero-knowledge-encryption') }}">zero-knowledge</flux:link>), it fits neatly into your pipeline. Here is a GitHub Actions job that blocks merges when validation fails:</p>
<pre><code class="language-yaml">name: Validate env

on:
  pull_request:
  workflow_dispatch:

jobs:
  validate-env:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: 20
      - run: npm install @ghostable/cli@latest
      - name: Validate production schema
        env:
          GHOSTABLE_TOKEN: $@{{ secrets.GHOSTABLE_TOKEN }}
        run: |
          ghostable login --token "$GHOSTABLE_TOKEN"
          ghostable env validate --env production
</code></pre>

                                <p>Apply the same pattern to deploy jobs so release pipelines fail fast when config drifts.</p>

                                <h2 id="anti-patterns">What not to do</h2>
                                <ul>
                                    <li>Don't copy real secrets into schema files or checked-in <code>.env</code> examples - keep schemas as contracts, not storage.</li>
                                    <li>Don't treat warnings as optional in production. Tighten <code>in:</code> lists or add overrides so prod rules are explicit.</li>
                                    <li>Don't skip validation for "small" changes. A single mistyped queue driver or URL can break deploys.</li>
                                    <li>Don't maintain divergent schemas per developer. Keep one shared <code>.ghostable</code> folder in version control.</li>
                                </ul>

                                <h2 id="deployment-benefits">Why validation protects deploys</h2>
                                <ul>
                                    <li><strong>Prevents broken releases:</strong> schema violations fail pipelines before code hits servers.</li>
                                    <li><strong>Codifies expectations:</strong> rules document required keys and safe values across every environment.</li>
                                    <li><strong>Stops config drift:</strong> pairing validation with <code>ghostable env push|pull</code> keeps remote and local envs aligned.</li>
                                    <li><strong>Safer rotations:</strong> regex and <code>starts_with</code> checks catch half-rotated keys or wrong providers.</li>
                                </ul>

                                <h2 id="next-steps">Next steps</h2>
                                <ul>
                                    <li>Expand schemas with optional keys marked <code>nullable</code> to reduce noise while staying explicit.</li>
                                    <li>Add staging/preview overrides that mirror production (minus the strictest checks) to surface drift earlier.</li>
                                    <li>Continue with the <flux:link href="https://docs.ghostable.dev/v2/digging-deeper/validation" target="_blank">validation deep-dive</flux:link> for full rule syntax and tips.</li>
                                </ul>
                            </div>
                        </article>
                    </div>

                    <aside class="hidden lg:block space-y-4 lg:sticky lg:top-24">
                        @if(!empty($tutorial['tags']))
                            <x-site.tag-list :tags="$tutorial['tags']" variant="card" />
                        @endif

                        <x-site.on-this-page :items="$tableOfContents" />
                    </aside>
                </div>

                <div class="flex flex-wrap gap-3 pt-4">
                    <flux:button variant="primary" href="{{ route('learn.index') }}" icon="chevron-left">
                        Back to Learn
                    </flux:button>
                    <flux:button variant="ghost" href="https://docs.ghostable.dev/v2/digging-deeper/validation" target="_blank">
                        View validation docs
                    </flux:button>
                </div>
            </div>
        </div>
    </div>

    <livewire:account.livewire.mailing-list-signup-form/>
</x-layouts.guest>
