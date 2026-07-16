<x-docs.page
    route-name="docs.cli.workflows.environments"
    title="Environments"
    section="Core Concepts"
    description="Model local, preview, staging, and production configuration as independently encrypted environments with explicit synchronization and history."
    :on-this-page="[
        ['label' => 'Environment types', 'href' => '#types'],
        ['label' => 'Create and seed', 'href' => '#create'],
        ['label' => 'Push and sync', 'href' => '#push-sync'],
        ['label' => 'Pull values', 'href' => '#pull'],
        ['label' => 'Run without a file', 'href' => '#run'],
        ['label' => 'Compare and audit', 'href' => '#compare'],
        ['label' => 'Rename and delete', 'href' => '#lifecycle'],
    ]"
>
    <x-docs.section id="types" title="Environment types">
        <p>
            Every environment has a name and a type. Interactive creation offers <code>local</code>, <code>development</code>, <code>preview</code>, <code>staging</code>, <code>production</code>, or a custom type. Types document intent and participate in protected-environment detection.
        </p>
        <p>
            Names or types containing <code>prod</code>, <code>production</code>, or <code>live</code> are treated as production-like and require local user confirmation before protected plaintext operations.
        </p>
        <x-docs.terminal title="List environments" :commands="['ghostable env list', 'ghostable env list --json']" />
    </x-docs.section>

    <x-docs.section id="create" title="Create and seed">
        <x-docs.terminal
            title="Create environments"
            :commands="[
                'ghostable env create preview --type preview',
                'ghostable env create staging --type staging --from-env default --seed keys-only',
                'ghostable env create production --type production --from-env staging --seed non-sensitive',
            ]"
        />
        <p>
            Seed modes are <code>keys-only</code>, <code>non-sensitive</code>, and <code>all</code>. Keys-only establishes layout without copying values. Non-sensitive copies values that do not appear secret. All copies every value and should receive deliberate review.
        </p>
        <p>Use <code>--from-file</code> instead of <code>--from-env</code> when a local env file should provide the initial key layout.</p>
    </x-docs.section>

    <x-docs.section id="push-sync" title="Push and sync">
        <p><code>env push</code> creates or updates keys present in a file without removing other stored keys:</p>
        <x-docs.terminal title="Push values" :commands="['ghostable env push --env staging --file .env.staging --reason &quot;Configure payment sandbox&quot;']" />
        <p><code>env sync</code> also deletes stored keys that are absent from the local file:</p>
        <x-docs.terminal title="Synchronize an environment" :commands="['ghostable env sync --env staging --file .env.staging --reason &quot;Remove retired integration&quot;']" />
        <x-docs.callout type="warning" title="Sync is destructive">
            Diff first. A truncated or incorrect input file can turn an intended update into many signed deletions.
        </x-docs.callout>
    </x-docs.section>

    <x-docs.section id="pull" title="Pull values">
        <p>
            Pull merges into an existing file by default and creates a timestamped backup before writing. Use <code>--replace</code> for an exact environment snapshot, <code>--only</code> for selected keys, and <code>--dry-run</code> to inspect the operation without writing.
        </p>
        <x-docs.terminal
            title="Materialize values"
            :commands="[
                'ghostable env pull --env default --file .env',
                'ghostable env pull --env staging --file .env.staging --replace',
                'ghostable env pull --env production --file .env.production --only APP_KEY --only DATABASE_URL',
            ]"
        />
        <p><code>--show-values</code> prints plaintext in command output. Avoid it in shared terminals, logs, CI, and agent sessions.</p>
    </x-docs.section>

    <x-docs.section id="run" title="Run without a file">
        <p>Inject decrypted values directly into a child process to reduce plaintext files on disk:</p>
        <x-docs.terminal
            title="Process injection"
            :commands="[
                'ghostable env run --env default -- php artisan test',
                'ghostable env run --env staging --mask-output -- npm run smoke-test',
                'ghostable env shell --env default',
            ]"
        />
        <p>
            The child inherits the current process environment by default. Pass <code>--no-inherit</code> to use only Ghostable values plus a minimal system environment, and <code>--strict</code> to validate injected values and fail when requested keys are missing.
        </p>
    </x-docs.section>

    <x-docs.section id="compare" title="Compare and audit">
        <x-docs.terminal
            title="Diff and history"
            :commands="[
                'ghostable env diff --env default --file .env',
                'ghostable env diff --from staging --to production',
                'ghostable env history --env production --limit 25',
                'ghostable env history --env production --key APP_KEY',
            ]"
        />
        <p>Diff output is redacted unless <code>--show-values</code> is explicitly requested. History records signed actions, environments, keys, devices, and timestamps.</p>
    </x-docs.section>

    <x-docs.section id="lifecycle" title="Rename and delete" :border="false">
        <x-docs.terminal
            title="Environment lifecycle"
            :commands="[
                'ghostable env rename --from preview-42 --to preview-43 --reason &quot;Match deployment environment&quot;',
                'ghostable env delete --env preview-43',
            ]"
        />
        <p>Both operations modify repository-backed state. Review and commit the resulting <code>.ghostable/</code> changes immediately.</p>
    </x-docs.section>
</x-docs.page>
