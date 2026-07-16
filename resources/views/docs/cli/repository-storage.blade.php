<x-docs.page
    route-name="docs.cli.workflows.projects"
    title="Repository & Storage"
    section="Core Concepts"
    description="Understand which Ghostable files belong in Git, which identity material remains local, and what reviewers can learn from encrypted project state."
    :on-this-page="[
        ['label' => 'Repository-backed state', 'href' => '#repository-state'],
        ['label' => 'Local identity', 'href' => '#local-identity'],
        ['label' => 'Metadata visibility', 'href' => '#metadata'],
        ['label' => 'Git workflow', 'href' => '#git-workflow'],
        ['label' => 'Multiple checkouts', 'href' => '#multiple-checkouts'],
    ]"
>
    <x-docs.section id="repository-state" title="Repository-backed state">
        <p>The <code>.ghostable/</code> directory is project state, not a local cache. It is intended to be committed, reviewed, branched, and merged with the application:</p>
        <pre class="overflow-x-auto rounded-xl border border-gray-200 bg-gray-50 p-5 font-mono text-sm leading-7 text-gray-700 dark:border-white/10 dark:bg-white/[0.04] dark:text-gray-300"><code>.ghostable/
├── ghostable.yaml
├── policy.json
├── devices/
├── access-requests/
├── environments/
│   └── production/
│       ├── access/
│       ├── keys/
│       └── values/
├── events/
├── schema.yaml
├── schemas/
└── hygiene.yaml</code></pre>
        <p>
            Values are encrypted, while device, policy, key metadata, access, activity, and suppression records are signed so tampering can be detected. Deleting or hand-editing these files can invalidate the project state; use CLI commands whenever one exists.
        </p>
    </x-docs.section>

    <x-docs.section id="local-identity" title="Local identity">
        <p>
            Each device has an Ed25519 signing key and X25519 encryption key. Public device records are committed. Private material is stored outside the repository:
        </p>
        <x-docs.command-table :commands="[
            ['command' => 'macOS', 'description' => 'Keychain service dev.ghostable.identity.&lt;project-id&gt;.'],
            ['command' => 'Windows', 'description' => 'Credential Manager target dev.ghostable.identity.&lt;project-id&gt;.'],
            ['command' => 'Linux / Unix', 'description' => '${XDG_CONFIG_HOME:-~/.config}/ghostable/identities/&lt;project-id&gt;.json.'],
            ['command' => 'GHOSTABLE_KEYSTORE', 'description' => 'Optional identity-store override used for controlled environments and testing.'],
        ]" />
        <p>File-backed identity directories use <code>0700</code> permissions and identity files use <code>0600</code> on Unix-like systems.</p>
    </x-docs.section>

    <x-docs.section id="metadata" title="Metadata visibility">
        <p>
            Encryption protects secret values, not all context. Repository readers may see project and environment names, public device labels, access roles, variable key names, annotations, change reasons, event timing, and schema descriptions.
        </p>
        <x-docs.callout type="security" title="Metadata must remain non-secret">
            Never place credentials in a key name, annotation, schema description, change reason, commit message, or pull-request discussion. Use encrypted variable context when a confidential note is required.
        </x-docs.callout>
    </x-docs.section>

    <x-docs.section id="git-workflow" title="Git workflow">
        <p>Review Ghostable changes alongside the code that consumes them:</p>
        <x-docs.terminal
            title="Review repository state"
            :commands="[
                'ghostable validate --env staging',
                'ghostable review',
                'git diff -- .ghostable',
                'git add .ghostable && git commit -m &quot;Update staging configuration&quot;',
            ]"
        />
        <p>
            Signed records do not remove the need for code review. A valid signature proves which authorized identity produced a record; it does not prove that the change is wise or that a reviewer should merge it.
        </p>
    </x-docs.section>

    <x-docs.section id="multiple-checkouts" title="Multiple checkouts" :border="false">
        <p>
            Ghostable associates a local project identity with the canonical repository root. If the same project ID is opened from another checkout or worktree, Ghostable may require <code>ghostable access join</code> for that checkout instead of silently reusing an identity registered elsewhere. This prevents an unrelated path from borrowing a project's local private keys.
        </p>
    </x-docs.section>
</x-docs.page>
