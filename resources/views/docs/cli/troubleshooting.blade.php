<x-docs.page
    route-name="docs.cli.reference.troubleshooting"
    title="Troubleshooting"
    section="Reference"
    description="Diagnose common project, identity, policy, user-presence, automation, provider, and Git-state failures without exposing plaintext values."
    :on-this-page="[
        ['label' => 'Collect safe diagnostics', 'href' => '#diagnostics'],
        ['label' => 'Project not found', 'href' => '#project'],
        ['label' => 'Identity and policy errors', 'href' => '#identity'],
        ['label' => 'User-presence failures', 'href' => '#presence'],
        ['label' => 'Automation token failures', 'href' => '#automation'],
        ['label' => 'Provider CLI failures', 'href' => '#providers'],
        ['label' => 'Git conflicts', 'href' => '#conflicts'],
    ]"
>
    <x-docs.section id="diagnostics" title="Collect safe diagnostics">
        <p>Start with version, repository, and redacted project state. These commands should not print decrypted values:</p>
        <x-docs.terminal
            title="Diagnostic baseline"
            :commands="[
                'ghostable --version',
                'ghostable status --json',
                'git status --short',
                'git diff -- .ghostable',
            ]"
        />
        <p>
            Record the operating system, installation method, exact command, exit code, and redacted stderr. Never attach <code>GHOSTABLE_CI_TOKEN</code>, local identity files, generated env files, <code>--show-values</code> output, or provider credentials.
        </p>
    </x-docs.section>

    <x-docs.section id="project" title="Project not found">
        <p>
            Run Ghostable from a repository directory containing <code>.ghostable/ghostable.yaml</code>, or from one of its descendants. If the repository has no manifest, initialize a new project with <code>ghostable setup</code> or pull the branch that contains the reviewed <code>.ghostable/</code> state.
        </p>
        <x-docs.terminal title="Locate project state" :commands="['git rev-parse --show-toplevel', 'git status --short -- .ghostable']" />
        <p>Do not copy another project's manifest to fix discovery. Its project ID is a cryptographic boundary, not a reusable template value.</p>
    </x-docs.section>

    <x-docs.section id="identity" title="Identity and policy errors">
        <x-docs.command-table :commands="[
            ['command' => 'Identity registered to another path', 'description' => 'This checkout or worktree is a different canonical root. Join it as a new device instead of copying private identity material.'],
            ['command' => 'Device has been revoked', 'description' => 'The old identity is permanently unusable. Join with --force to create a new identity, then request access again.'],
            ['command' => 'Policy signer is not trusted or policy is stale', 'description' => 'Fetch and review the latest policy and access commits from the authoritative branch. Never lower a trusted policy version or repair signatures by hand.'],
            ['command' => 'Invalid signature', 'description' => 'Stop and inspect Git history. Restore one complete reviewed record generation or replay the Ghostable operation from clean state.'],
        ]" />
        <x-docs.terminal title="Create a replacement device identity" :commands="['unset GHOSTABLE_CI_TOKEN', 'ghostable access join --force --name &quot;Replacement workstation&quot;']" />
        <p>On PowerShell, remove an accidentally inherited automation token with <code>Remove-Item Env:GHOSTABLE_CI_TOKEN</code> before joining as a human device.</p>
    </x-docs.section>

    <x-docs.section id="presence" title="User-presence failures">
        <p>
            Protected plaintext operations require an interactive local session. Retry from a terminal attached to the signed-in desktop session and complete Touch ID, Windows Hello, or the local PAM-backed confirmation. SSH sessions, background services, redirected input, and headless jobs generally cannot satisfy the prompt.
        </p>
        <p>
            Use a dry run when it is sufficient. For CI or deployment automation, create a narrowly scoped automation credential instead of attempting to bypass local user presence.
        </p>
    </x-docs.section>

    <x-docs.section id="automation" title="Automation token failures">
        <x-docs.command-table :commands="[
            ['command' => 'Missing token', 'description' => 'Bind GHOSTABLE_CI_TOKEN through the job secret environment, not as a CLI argument or echoed shell value.'],
            ['command' => 'Invalid prefix or malformed token', 'description' => 'Replace the secret from its trusted source. Tokens cannot be reconstructed from repository records.'],
            ['command' => 'Project ID mismatch', 'description' => 'The token belongs to another Ghostable project. Confirm the checkout and create a project-scoped replacement.'],
            ['command' => 'Revoked credential', 'description' => 'Create a new credential, commit its public records, update the job secret, and remove the old secret.'],
            ['command' => 'Permission denied', 'description' => 'Inspect access matrix and grant only the required environment and reader or writer role.'],
        ]" />
        <p>Do not expose protected tokens to forked or unreviewed pull-request code. Use credential-free <code>review --secrets-only</code> for that trust boundary.</p>
    </x-docs.section>

    <x-docs.section id="providers" title="Provider CLI failures">
        <p>
            A Ghostable provider dry run does not invoke the provider CLI, query remote state, or test authentication. For a real deployment, confirm the expected <code>forge</code>, <code>vapor</code>, or <code>cloud</code> executable is on <code>PATH</code>, outside the application repository, and authenticated in the same user or runner context.
        </p>
        <x-docs.terminal title="Provider executable checks" :commands="['forge --help', 'vapor --help', 'cloud --help']" />
        <p>
            If a provider push fails, treat its stderr as potentially sensitive, verify the remote environment before retrying, and inspect OS temporary storage after abrupt runner termination. Ghostable redacts known values from provider failures, but transformed provider output may still require manual redaction.
        </p>
    </x-docs.section>

    <x-docs.section id="conflicts" title="Git conflicts" :border="false">
        <p>
            Do not hand-merge signed records. Follow the <a href="{{ route('docs.cli.workflows.projects') }}#git-conflicts">repository conflict runbook</a> to select one complete record generation, replay the discarded Ghostable operation, and verify status, validation, review, and the final Git diff.
        </p>
    </x-docs.section>
</x-docs.page>
