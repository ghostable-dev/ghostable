<x-docs.page
    route-name="docs.cli.workflows.review"
    title="Review & Secret Scanning"
    section="Workflows"
    description="Review changed code for environment drift and hard-coded secrets before a pull request is merged, with redacted human and machine-readable output."
    :on-this-page="[
        ['label' => 'What review checks', 'href' => '#checks'],
        ['label' => 'Git comparison', 'href' => '#comparison'],
        ['label' => 'Focused review modes', 'href' => '#modes'],
        ['label' => 'Scan configuration', 'href' => '#configuration'],
        ['label' => 'Output formats', 'href' => '#output'],
        ['label' => 'Suppressions', 'href' => '#suppressions'],
    ]"
>
    <x-docs.section id="checks" title="What review checks">
        <p><code>ghostable review</code> combines two local checks:</p>
        <ul>
            <li><strong>ENV state drift</strong> finds environment references added in changed code and compares them with encrypted values, schema files, <code>.env.example</code>, and signed Ghostable records.</li>
            <li><strong>Hard-coded secret scanning</strong> looks for likely credentials in changed files and redacts matching values by default.</li>
        </ul>
        <p>
            ENV reference detection covers common patterns in PHP/Laravel, JavaScript/TypeScript/Node, Go, Python, Ruby/Rails, Java, C#, Rust, Swift, and shell or deployment scripts. GitHub Actions environment references under <code>.github/</code> are ignored because they often refer to GitHub Secrets or Variables.
        </p>
    </x-docs.section>

    <x-docs.section id="comparison" title="Git comparison">
        <p>
            Without <code>--base</code>, Ghostable tries the current branch upstream, then <code>origin/main</code>, <code>origin/master</code>, <code>main</code>, <code>master</code>, and finally <code>HEAD</code>. The head includes local worktree changes by default.
        </p>
        <x-docs.terminal title="Review a pull request range" :commands="['ghostable review --base origin/main --head HEAD', 'ghostable review app/ config/ --base origin/main']" />
    </x-docs.section>

    <x-docs.section id="modes" title="Focused review modes">
        <x-docs.terminal
            title="Focused review"
            :commands="[
                'ghostable review --env-only',
                'ghostable review --secrets-only',
                'ghostable scan',
            ]"
        />
        <p><code>scan</code> remains a compatibility command for the hard-coded secret scanner. Prefer <code>review --secrets-only</code> in new scripts.</p>
        <x-docs.callout type="warning" title="Avoid --show-values">
            <code>--show-values</code> can print matching plaintext. It is for deliberate human debugging, not CI, copied issue reports, or coding-agent sessions.
        </x-docs.callout>
    </x-docs.section>

    <x-docs.section id="configuration" title="Scan configuration">
        <p>
            The project manifest sets the default scan level to <code>standard</code> and ignores Git metadata, dependencies, build output, encrypted value files, and environment key files. Override a run with <code>--level relaxed|standard|strict</code>, repeated <code>--ignore</code> patterns, or <code>--max-size</code>.
        </p>
    </x-docs.section>

    <x-docs.section id="output" title="Output formats">
        <x-docs.terminal
            title="Machine-readable review"
            :commands="[
                'ghostable review --format github',
                'ghostable review --json',
                'ghostable review --secrets-only --json',
            ]"
        />
        <p>Use GitHub format for annotations in Actions and JSON for other automation.</p>
    </x-docs.section>

    <x-docs.section id="suppressions" title="Suppressions" :border="false">
        <p>Create a signed, scoped, optionally expiring suppression only after reviewing a false positive:</p>
        <x-docs.terminal
            title="Suppress a reviewed finding"
            :commands="[
                'ghostable review suppress --code &lt;finding-code&gt; --path tests/Fixtures/token.php --line 14 --reason &quot;Documented test fixture&quot; --expires-in 30d',
            ]"
        />
        <p>Suppression records are repository-visible policy decisions. Prefer narrow path, line, kind, key, and expiration scopes over broad permanent exclusions.</p>
    </x-docs.section>
</x-docs.page>
