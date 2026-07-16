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
        <p>Pass an explicit base in CI so comparisons do not depend on checkout tracking configuration.</p>
    </x-docs.section>

    <x-docs.section id="tests" title="Run tests with values">
        <p>Inject values into the test process without writing a persistent env file:</p>
        <x-docs.terminal title="Test with injected values" :commands="['ghostable env run --env staging --no-inherit --mask-output --strict -- npm test']" />
        <p>
            <code>--no-inherit</code> reduces ambient runner variables, <code>--mask-output</code> redacts injected values from child output, and <code>--strict</code> fails when requested configuration is missing. The runner can still access decrypted process memory, so use an isolated CI environment.
        </p>
    </x-docs.section>

    <x-docs.section id="pull-requests" title="Pull-request safety">
        <x-docs.callout type="security" title="Forked code is untrusted">
            Do not expose a Ghostable token to workflows executing code from forks or unreviewed pull requests. A malicious test can read process environment, memory, or files after Ghostable decrypts values, even when command output is masked.
        </x-docs.callout>
        <p>Run repository-only secret scanning without credentials on untrusted changes, and reserve decryption for protected branches or reviewed deployment jobs.</p>
    </x-docs.section>

    <x-docs.section id="pipeline" title="Example pipeline" :border="false">
        <p>A minimal protected-branch job follows this order:</p>
        <x-docs.terminal
            title="Protected CI job"
            :commands="[
                'npm ci',
                'npx ghostable validate --env staging --json',
                'npx ghostable review --base origin/main --format github',
                'npx ghostable env run --env staging --mask-output --strict -- npm test',
            ]"
        />
        <p>Pin <code>@ghostable/cli</code> and commit the lockfile so CI and developers execute the same 3.x release.</p>
    </x-docs.section>
</x-docs.page>
