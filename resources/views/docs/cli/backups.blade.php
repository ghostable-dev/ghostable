<x-docs.page
    route-name="docs.cli.reference.backups"
    title="Backups & Offline"
    section="Reference"
    description="Plan recovery around Git-backed encrypted state, separately protected private identities, automatic env-file backups, and the limits of an offline local-first system."
    :on-this-page="[
        ['label' => 'Repository backup', 'href' => '#repository'],
        ['label' => 'Identity recovery', 'href' => '#identity'],
        ['label' => 'Env-file backups', 'href' => '#env-files'],
        ['label' => 'Offline behavior', 'href' => '#offline'],
        ['label' => 'Recovery exercises', 'href' => '#recovery'],
        ['label' => 'Plaintext cleanup', 'href' => '#cleanup'],
    ]"
>
    <x-docs.section id="repository" title="Repository backup">
        <p>
            Ghostable has no separate hosted vault to restore from. The Git repository is the durable copy of encrypted values, public devices, policy, access grants, environment keys, schema, hygiene configuration, and signed events. Protect it with the same remote redundancy and retention used for source code.
        </p>
        <p>
            A repository backup alone preserves ciphertext and history, but it cannot decrypt values without at least one still-authorized private device identity or automation credential.
        </p>
    </x-docs.section>

    <x-docs.section id="identity" title="Identity recovery">
        <p>
            Private device identities live outside Git. Maintain more than one owner device so the project is not dependent on a single laptop. If all authorized private identities and automation credentials are lost, public device records and encrypted repository state cannot reconstruct the missing private keys.
        </p>
        <x-docs.callout type="security" title="Redundancy without key sharing">
            Add a second owner through the normal device join and grant process. Do not solve recovery by copying one owner's private identity file to multiple people or committing it to a password-protected archive inside the repository.
        </x-docs.callout>
    </x-docs.section>

    <x-docs.section id="env-files" title="Env-file backups">
        <p>
            <code>env pull</code> merges by default and creates a timestamped <code>.ghostable-backup-&lt;timestamp&gt;</code> copy before overwriting an existing file. Disable that behavior only with <code>--no-backup</code>.
        </p>
        <p>
            Local <code>deploy</code> replaces its target by default and creates a backup only when <code>--backup</code> is passed. Dry runs never write a backup because they do not write the destination.
        </p>
        <x-docs.terminal title="Safe file writes" :commands="['ghostable env pull --env default --file .env --dry-run', 'ghostable env pull --env default --file .env', 'ghostable deploy local production --file .env.production --backup']" />
    </x-docs.section>

    <x-docs.section id="offline" title="Offline behavior">
        <p>
            Setup, encryption, decryption, validation, review, hygiene, local process injection, and repository state changes run locally and do not require a Ghostable service. Normal Git synchronization still requires access to your remote, and provider deployments require their provider CLI and network.
        </p>
        <p>
            Homebrew, npm, and release-archive installation or updating also require their distribution sources. Local protected access depends on the operating system's user-presence facility, not a Ghostable network call.
        </p>
    </x-docs.section>

    <x-docs.section id="recovery" title="Recovery exercises">
        <ol>
            <li>Confirm the repository remote contains current <code>.ghostable/</code> state.</li>
            <li>Verify at least two owner devices can run <code>ghostable access status</code>.</li>
            <li>Test a read-only pull or process injection from a secondary device.</li>
            <li>Review how CI and deploy tokens would be revoked and replaced.</li>
            <li>Document the external systems that receive plaintext after decryption.</li>
        </ol>
    </x-docs.section>

    <x-docs.section id="cleanup" title="Plaintext cleanup" :border="false">
        <x-docs.terminal title="Review and remove env files" :commands="['ghostable env clean --dry-run', 'ghostable env clean']" />
        <p>
            Cleanup includes Ghostable-generated backup files in the project root. Preserve a backup outside the working tree only when policy requires it, and protect that plaintext copy independently from the encrypted repository.
        </p>
    </x-docs.section>
</x-docs.page>
