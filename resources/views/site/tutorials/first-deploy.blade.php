@inject('learn', '\App\Learn\LearnRepository')
@php
    $tutorial = $learn->findBySlug('first-deploy-with-ghostable');
    $tutorialTitle = $tutorial['title'] ?? 'Your first deploy with Ghostable';
    $tutorialDescription = $tutorial['description'] ?? 'A practical walkthrough to prep environments, validate config, and ship your first deploy with Ghostable and CI.';
    $tutorialKeywords = $tutorial['keywords'] ?? [];
    $tutorialImage = $tutorial['image'] ?? null;
    $tableOfContents = [
        ['href' => '#what-youll-do', 'label' => 'What you\'ll do'],
        ['href' => '#prerequisites', 'label' => 'Prerequisites'],
        ['href' => '#step-1', 'label' => '1. Initialize Ghostable'],
        ['href' => '#step-2', 'label' => '2. Define your schema'],
        ['href' => '#step-3', 'label' => '3. Push env vars securely'],
        ['href' => '#step-4', 'label' => '4. Validate before deploy'],
        ['href' => '#step-5', 'label' => '5. Wire CI for deploys'],
        ['href' => '#step-6', 'label' => '6. Deploy with confidence'],
        ['href' => '#anti-patterns', 'label' => 'What not to do'],
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
@endpush

<x-layouts.guest title="{{ $tutorialTitle }}" canonical="{{ route('learn.first-deploy-with-ghostable') }}">
    <div class="bg-white">
        <div class="px-6 lg:px-8 pt-16 pb-20">
            <div class="mx-auto max-w-6xl">
                <div class="lg:grid lg:grid-cols-[minmax(0,3fr)_320px] lg:items-start lg:gap-12">
                    <div class="space-y-8 max-w-3xl">
                        <header class="space-y-4">
                            <flux:breadcrumbs class="pb-2">
                                <flux:breadcrumbs.item href="{{ route('learn.index') }}" separator="slash">Learn</flux:breadcrumbs.item>
                                <flux:breadcrumbs.item separator="slash">Tutorials</flux:breadcrumbs.item>
                                <flux:breadcrumbs.item separator="slash">Your first deploy</flux:breadcrumbs.item>
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
                                <p>Follow these steps to take a project from zero to first deploy with Ghostable. You will initialize the CLI, define a schema, push environment variables securely, add validation to your pipeline, and run a deploy that refuses to ship broken config.</p>

                                <h2 id="what-youll-do">What you'll do</h2>
                                <ul>
                                    <li>Initialize Ghostable in your repo so environments are linked to your project.</li>
                                    <li>Create a shared schema that documents and validates required env vars.</li>
                                    <li>Push secrets securely without committing raw values.</li>
                                    <li>Validate locally and in CI so deploys fail fast on bad config.</li>
                                    <li>Deploy with confidence using a deploy token instead of a personal token.</li>
                                </ul>

                                <h2 id="prerequisites">Prerequisites</h2>
                                <ul>
                                    <li>Ghostable CLI installed.</li>
                                    <li>A deploy/machine token stored as <code>GHOSTABLE_TOKEN</code> in CI (or a local login token while testing).</li>
                                    <li>Project repo with a <code>.env</code> you can sanitize and push.</li>
                                    <li>Access to your app's deploy pipeline (GitHub Actions example below).</li>
                                </ul>

                                <h2 id="step-1">1. Initialize Ghostable</h2>
                                <p>Link your repo to a Ghostable project. This writes <code>.ghostable/ghostable.yaml</code> with project + environment info (no secrets stored here).</p>
<pre><code class="language-bash">ghostable init
# follow prompts for org/project
</code></pre>
                                <p>Commit <code>.ghostable/ghostable.yaml</code> so teammates share the same project wiring.</p>

                                <h2 id="step-2">2. Define your schema</h2>
                                <p>Add a schema to document and validate required variables. Start global, then add overrides for production.</p>
<pre><code class="language-yaml"># .ghostable/schema.yaml
APP_NAME:
  - required
  - string
  - max:64

APP_ENV:
  - required
  - in:local,staging,production

APP_URL:
  - required
  - url

DB_CONNECTION:
  - required
  - in:mysql,pgsql,sqlsrv,sqlite

LOG_CHANNEL:
  - required
  - in:stack,stdout
</code></pre>

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

                                <h2 id="step-3">3. Push env vars securely</h2>
                                <p>Sanitize your local <code>.env</code> (no production secrets committed) and push values to Ghostable for your target environment.</p>
<pre><code class="language-bash">ghostable login --token "$GHOSTABLE_TOKEN"
ghostable env push --env production --file .env.production
</code></pre>
                                <p>This encrypts locally with your device key and sends only ciphertext + metadata. Ghostable never sees plaintext values.</p>

                                <h2 id="step-4">4. Validate before deploy</h2>
                                <p>Run validation locally to catch issues early. It merges <code>schema.yaml</code> with the environment override and checks your <code>.env</code>.</p>
<pre><code class="language-bash">ghostable env validate --env production --file .env.production
</code></pre>
                                <p>Fix any failing keys before you move to CI or release.</p>

                                <h2 id="step-5">5. Wire CI for deploys</h2>
                                <p>Add a job that logs in with a deploy token, validates, and only then runs your deploy step. Example: GitHub Actions.</p>
<pre><code class="language-yaml">name: Deploy

on:
  workflow_dispatch:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: 20
      - run: npm install @ghostable/cli@latest
      - name: Validate env
        env:
          GHOSTABLE_TOKEN: $@{{ secrets.GHOSTABLE_TOKEN }}
        run: |
          ghostable login --token "$GHOSTABLE_TOKEN"
          ghostable env validate --env production --file .env.production
      - name: Deploy app
        env:
          GHOSTABLE_TOKEN: $@{{ secrets.GHOSTABLE_TOKEN }}
        run: |
          # build and deploy using your toolchain
          npm run build
          npm run deploy
</code></pre>
                                <p>If validation fails, the job exits non-zero and the deploy is blocked.</p>

                                <h2 id="step-6">6. Deploy with confidence</h2>
                                <p>With schema + validation enforced, run your deploy job. Because secrets and schemas stay local, you avoid leaking values while still catching misconfigurations.</p>

                                <h2 id="anti-patterns">What not to do</h2>
                                <ul>
                                    <li>Don't skip validation in CI; a local-only check won't protect production deploys.</li>
                                    <li>Don't use personal tokens in pipelines - use deploy/machine tokens with least privilege.</li>
                                    <li>Don't commit real secrets to <code>.env</code> or <code>schema.yaml</code>; keep them in Ghostable and inject at runtime.</li>
                                    <li>Don't maintain drift between environments; push/pull regularly and keep schemas in version control.</li>
                                </ul>

                                <h2 id="next-steps">Next steps</h2>
                                <ul>
                                    <li>Add staging/preview schemas that mirror production to surface issues earlier.</li>
                                    <li>Use <code>ghostable env pull</code> in review apps to hydrate from safe sources instead of copying .env files.</li>
                                    <li>Extend validation rules with <code>regex</code> and <code>starts_with</code> for API keys and URLs.</li>
                                    <li>Keep reading the <flux:link href="https://docs.ghostable.dev/v2/digging-deeper/validation" target="_blank">validation docs</flux:link> and <flux:link href="https://docs.ghostable.dev/v2/the-basics/environments" target="_blank">environment basics</flux:link> for more patterns.</li>
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
                    <flux:button variant="ghost" href="https://docs.ghostable.dev/v2/the-basics/environments" target="_blank">
                        View docs
                    </flux:button>
                </div>
            </div>
        </div>
    </div>

    <livewire:account.livewire.mailing-list-signup-form/>
</x-layouts.guest>
