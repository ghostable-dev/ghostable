<x-docs.page
    route-name="docs.cli.automation.continuous-integration"
    title="Continuous Integration"
    section="Automation & CI"
    description="Run validation, drift review, secret scanning, and application tests in CI with explicit flags, structured output, and narrowly scoped credentials."
    :on-this-page="[
        ['label' => 'CI principles', 'href' => '#principles'],
        ['label' => 'Create CI access', 'href' => '#access'],
        ['label' => 'Validation and review', 'href' => '#checks'],
        ['label' => 'Run tests with values', 'href' => '#tests'],
        ['label' => 'Pull-request safety', 'href' => '#pull-requests'],
        ['label' => 'Automation contract', 'href' => '#contract'],
        ['label' => 'Example pipeline', 'href' => '#pipeline'],
    ]"
>
    <x-docs.section id="principles" title="CI principles">
        <ul>
            <li>Pass all selections as flags; CI has no interactive prompt session.</li>
            <li>Prefer <code>--json</code>, <code>--format github</code>, or <code>--sarif</code> over parsing human output.</li>
            <li>Use one scoped automation credential per trust boundary.</li>
            <li>Do not enable <code>--show-values</code> or echo <code>GHOSTABLE_CI_TOKEN</code>.</li>
            <li>Keep production credentials out of untrusted pull-request jobs.</li>
        </ul>
    </x-docs.section>

    <x-docs.section id="access" title="Create CI access">
        <p>From an owner device:</p>
        <x-docs.terminal title="CI credential" :commands="['ghostable access create --name github-actions --kind ci --grant staging:reader']" />
        <p>
            Store the returned token in the CI platform as <code>GHOSTABLE_CI_TOKEN</code> and commit the generated public device, policy, and access-grant changes. A reader grant is sufficient for validation, review, and test-time decryption.
        </p>
    </x-docs.section>

    <x-docs.section id="checks" title="Validation and review">
        <x-docs.terminal
            title="CI checks"
            :commands="[
                'ghostable validate --env staging --json',
                'ghostable review --base origin/main --head HEAD --format github',
                'ghostable hygiene report --env staging --sarif',
            ]"
        />
        <p>
            Pass an explicit base in CI so comparisons do not depend on checkout tracking configuration. Validation, review, and scan exit non-zero when actionable findings remain. Hygiene reports findings in its payload but exits successfully unless execution itself fails, so enforce hygiene thresholds through the SARIF consumer or a separate policy step.
        </p>
    </x-docs.section>

    <x-docs.section id="tests" title="Run tests with values">
        <p>Inject values into the test process without writing a persistent env file:</p>
        <x-docs.terminal title="Test with injected values" :commands="['ghostable env run --env staging --no-inherit --mask-output --strict -- npm test']" />
        <p>
            <code>--no-inherit</code> reduces ambient runner variables, <code>--mask-output</code> replaces exact injected values found in child stdout and stderr, and <code>--strict</code> fails when requested configuration is missing or invalid. Masking is best effort: encoded, transformed, split, or file-written values can still escape. The runner can access decrypted process memory, so use an isolated CI environment.
        </p>
    </x-docs.section>

    <x-docs.section id="pull-requests" title="Pull-request safety">
        <x-docs.callout type="security" title="Forked code is untrusted">
            Do not expose a Ghostable token to workflows executing code from forks or unreviewed pull requests. A malicious test can read process environment, memory, or files after Ghostable decrypts values, even when command output is masked.
        </x-docs.callout>
        <p>Run repository-only secret scanning without credentials on untrusted changes, and reserve decryption for protected branches or reviewed deployment jobs.</p>
    </x-docs.section>

    <x-docs.section id="contract" title="Automation contract">
        <x-docs.command-table :commands="[
            ['command' => 'stdout', 'description' => 'Automation-oriented output is written here when --json, --format github, or --sarif is supported and selected.'],
            ['command' => 'stderr', 'description' => 'Usage, verification, runtime, and final failure messages are written here. A failing JSON command may still emit a complete JSON result on stdout.'],
            ['command' => 'Exit 0', 'description' => 'The command completed and its checks passed.'],
            ['command' => 'Exit 1', 'description' => 'Invalid usage, a runtime or verification error, or findings from commands that fail checks such as validate, review, and scan. Hygiene reports findings without failing by default.'],
            ['command' => 'Exit 130', 'description' => 'An interactive prompt was canceled.'],
            ['command' => 'env run', 'description' => 'Returns the child process exit code when that process starts and exits unsuccessfully.'],
        ]" />
        <p>
            Structured formats are available only when the command's <code>--help</code> lists them. Version-lock the CLI, ignore unknown JSON fields, and never assume structured output is secret-free: credential creation intentionally returns its token, while value-reading commands remain redacted unless plaintext output is explicitly requested.
        </p>
        <p>See the <a href="{{ route('docs.cli.reference.commands') }}#automation-contract">command reference automation contract</a> for the supported formats and a validation payload example.</p>
    </x-docs.section>

    <x-docs.section id="pipeline" title="Example pipeline" :border="false">
        <p>
            This GitHub Actions workflow scans pull requests without a credential and limits decryption to pushes on the protected <code>main</code> branch. Store <code>GHOSTABLE_CI_TOKEN</code> as a repository or environment secret available only to the protected job.
        </p>
        <pre class="overflow-x-auto rounded-xl border border-gray-200 bg-gray-50 p-5 font-mono text-sm leading-7 text-gray-700 dark:border-white/10 dark:bg-white/[0.04] dark:text-gray-300"><code>name: Ghostable

on:
  pull_request:
  push:
    branches: [main]

permissions:
  contents: read

jobs:
  scan-untrusted:
    if: $@{{ github.event_name == 'pull_request' }}
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
        with:
          fetch-depth: 0
      - uses: actions/setup-node@v4
        with:
          node-version: 22
          cache: npm
      - run: npm ci
      - run: npx ghostable review --base "origin/$@{{ github.base_ref }}" --head HEAD --secrets-only --format github

  protected-checks:
    if: $@{{ github.event_name == 'push' && github.ref == 'refs/heads/main' }}
    runs-on: ubuntu-latest
    env:
      GHOSTABLE_CI_TOKEN: $@{{ secrets.GHOSTABLE_CI_TOKEN }}
    steps:
      - uses: actions/checkout@v4
        with:
          fetch-depth: 0
      - uses: actions/setup-node@v4
        with:
          node-version: 22
          cache: npm
      - run: npm ci
      - run: npx ghostable validate --env staging --json
      - run: npx ghostable review --base "$@{{ github.event.before }}" --head "$@{{ github.sha }}" --format github
      - run: npx ghostable env run --env staging --no-inherit --mask-output --strict -- npm test</code></pre>
        <p>Pin <code>@ghostable/cli</code> and commit the lockfile so CI and developers execute the same 3.x release.</p>
    </x-docs.section>
</x-docs.page>
