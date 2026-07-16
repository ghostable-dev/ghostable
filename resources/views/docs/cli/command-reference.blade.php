<x-docs.page
    route-name="docs.cli.reference.commands"
    title="Command Reference"
    section="Reference"
    description="A complete map of the Ghostable CLI 3.x command surface, including human workflows, automation output, aliases, and advanced integration commands."
    :on-this-page="[
        ['label' => 'Top-level commands', 'href' => '#top-level'],
        ['label' => 'Environment commands', 'href' => '#environment'],
        ['label' => 'Variable commands', 'href' => '#variable'],
        ['label' => 'Access commands', 'href' => '#access'],
        ['label' => 'Validation and review', 'href' => '#quality'],
        ['label' => 'Hygiene and deployment', 'href' => '#operations'],
        ['label' => 'Agent and advanced commands', 'href' => '#advanced'],
        ['label' => 'Automation conventions', 'href' => '#conventions'],
    ]"
>
    <x-docs.section id="top-level" title="Top-level commands">
        <x-docs.command-table :commands="[
            ['command' => 'ghostable setup', 'description' => 'Initialize the project, owner device, policy, environments, keys, and optional .env import.'],
            ['command' => 'ghostable status', 'description' => 'Show project, device, environment, and variable counts.'],
            ['command' => 'ghostable adopt', 'description' => 'Generate a plain-text adoption prompt for schema, example, hygiene, drift, annotation, and CI work.'],
            ['command' => 'ghostable env', 'description' => 'Manage environments and environment-level value workflows.'],
            ['command' => 'ghostable var', 'description' => 'Manage one variable, promotion, encrypted context, and annotations.'],
            ['command' => 'ghostable validate', 'description' => 'Validate stored or file-based values against schema rules.'],
            ['command' => 'ghostable schema', 'description' => 'Manage schema files, rules, and keys.'],
            ['command' => 'ghostable review', 'description' => 'Review changed-code ENV usage and hard-coded secrets.'],
            ['command' => 'ghostable scan', 'description' => 'Compatibility alias for hard-coded secret scanning; prefer review --secrets-only.'],
            ['command' => 'ghostable example', 'description' => 'Generate or update .env.example from encrypted state and code references.'],
            ['command' => 'ghostable hygiene', 'description' => 'Report operational findings, configure rotation rules, suppress findings, and rotate environment keys.'],
            ['command' => 'ghostable access', 'description' => 'Manage devices, requests, roles, and automation credentials.'],
            ['command' => 'ghostable device', 'description' => 'Alias for human-device access operations.'],
            ['command' => 'ghostable deploy', 'description' => 'Write a local env file or sync values to a supported provider.'],
            ['command' => 'ghostable agent', 'description' => 'Emit agent guidance and the recommended safe capability list.'],
        ]" />
        <x-docs.terminal title="Command help" :commands="['ghostable --help', 'ghostable env --help', 'ghostable env pull --help']" />
    </x-docs.section>

    <x-docs.section id="environment" title="Environment commands">
        <x-docs.command-table :commands="[
            ['command' => 'env list', 'description' => 'List environment names, types, variable counts, and last update times.'],
            ['command' => 'env create [name]', 'description' => 'Create an environment with optional --type, --from-env or --from-file, and --seed mode.'],
            ['command' => 'env push', 'description' => 'Create or update values from an env file without deleting absent stored keys.'],
            ['command' => 'env sync', 'description' => 'Push a file and delete stored keys that are absent from it.'],
            ['command' => 'env pull', 'description' => 'Merge or replace a local env file; backs up an existing file by default.'],
            ['command' => 'env clean', 'description' => 'Preview or remove project-root .env and .env.* files, keeping examples by default.'],
            ['command' => 'env run', 'description' => 'Inject values into one child command without writing an env file.'],
            ['command' => 'env shell', 'description' => 'Open a shell with injected values.'],
            ['command' => 'env diff', 'description' => 'Compare a stored environment to a file, or compare two stored environments.'],
            ['command' => 'env history', 'description' => 'Show signed events filtered by environment, key, action, or limit.'],
            ['command' => 'env rename', 'description' => 'Rename an environment and its repository-backed state.'],
            ['command' => 'env delete', 'description' => 'Delete an environment after confirmation.'],
            ['command' => 'env duplicate &lt;source&gt; &lt;target&gt;', 'description' => 'Advanced shortcut to create and seed a copy using keys-only, non-sensitive, or all.'],
            ['command' => 'env layout generate', 'description' => 'Advanced command that writes sparse key ordering metadata from stored keys or a file.'],
            ['command' => 'env file save', 'description' => 'Integration command that atomically saves base64-encoded env content inside the project with restrictive permissions.'],
        ]" />
    </x-docs.section>

    <x-docs.section id="variable" title="Variable commands">
        <x-docs.command-table :commands="[
            ['command' => 'var push', 'description' => 'Save one key from a file or an interactive secret prompt.'],
            ['command' => 'var pull', 'description' => 'Write one key to a file or explicitly print it with --show-values.'],
            ['command' => 'var promote', 'description' => 'Copy a value or key-only layout from one environment to another; var copy is an alias.'],
            ['command' => 'var delete', 'description' => 'Delete one stored variable with an optional signed reason.'],
            ['command' => 'var history', 'description' => 'Show signed history using the environment-history filters.'],
            ['command' => 'var context', 'description' => 'Read or replace an encrypted note associated with one key.'],
            ['command' => 'var annotation list', 'description' => 'List plaintext typed annotations for a key.'],
            ['command' => 'var annotation set', 'description' => 'Set exactly one --string, --number, or --bool annotation value.'],
            ['command' => 'var annotation remove', 'description' => 'Remove one annotation by name.'],
            ['command' => 'var enable / disable', 'description' => 'Advanced commands that control active versus commented env-file output.'],
            ['command' => 'var status', 'description' => 'Advanced explicit status command using --enabled, --disabled, or --commented.'],
        ]" />
    </x-docs.section>

    <x-docs.section id="access" title="Access commands">
        <x-docs.command-table :commands="[
            ['command' => 'access join', 'description' => 'Create a local device identity and public repository record for this checkout.'],
            ['command' => 'access status', 'description' => 'Show the current device, local identity path, roles, and permissions.'],
            ['command' => 'access list', 'description' => 'List project device records.'],
            ['command' => 'access approvers', 'description' => 'Show devices allowed to grant access for an environment.'],
            ['command' => 'access requests list', 'description' => 'List pending requests; --all includes reviewed and already-granted requests.'],
            ['command' => 'access requests create', 'description' => 'Request reader, writer, grantor, or owner access for one environment or all.'],
            ['command' => 'access requests approve / deny', 'description' => 'Review a signed request by request ID.'],
            ['command' => 'access share', 'description' => 'Directly grant a role to a device for an environment or all.'],
            ['command' => 'access grants', 'description' => 'Advanced list of environment grant records.'],
            ['command' => 'access matrix', 'description' => 'Advanced role matrix by device and environment.'],
            ['command' => 'access create', 'description' => 'Create a scoped ci, deploy, or access automation credential.'],
            ['command' => 'access revoke', 'description' => 'Remove a device or automation identity from an environment or all.'],
            ['command' => 'access leave', 'description' => 'Remove this machine\'s local access; the last owner cannot leave.'],
            ['command' => 'access cleanup', 'description' => 'Preview or remove orphaned local identities.'],
            ['command' => 'access delete', 'description' => 'Delete an already-revoked public device record.'],
        ]" />
    </x-docs.section>

    <x-docs.section id="quality" title="Validation and review">
        <x-docs.command-table :commands="[
            ['command' => 'validate', 'description' => 'Validate --env stored values or a supplied --file; supports --json.'],
            ['command' => 'schema rule add / remove / update', 'description' => 'Mutate rules in the global or selected schema file.'],
            ['command' => 'schema key rename / remove', 'description' => 'Rename or remove a schema key.'],
            ['command' => 'schema file save / delete', 'description' => 'Advanced integration commands for base64 schema content and safe deletion.'],
            ['command' => 'review [run]', 'description' => 'Review ENV drift and secrets for the selected paths and Git range.'],
            ['command' => 'review suppress', 'description' => 'Create a signed, scoped, optionally expiring finding suppression.'],
            ['command' => 'scan [run]', 'description' => 'Run only the compatibility hard-coded secret scanner.'],
            ['command' => 'scan suppress', 'description' => 'Create a signed secret-scan suppression.'],
            ['command' => 'example generate', 'description' => 'Create or update an example file using blank, non-sensitive, or all value mode.'],
        ]" />
    </x-docs.section>

    <x-docs.section id="operations" title="Hygiene and deployment">
        <x-docs.command-table :commands="[
            ['command' => 'hygiene report', 'description' => 'Report rotation, environment-key age, and optional stale or unused findings.'],
            ['command' => 'hygiene rotation list / set / remove', 'description' => 'Manage project and environment-specific variable rotation rules.'],
            ['command' => 'hygiene suppress', 'description' => 'Create a signed hygiene exception by code and optional env/key scope.'],
            ['command' => 'hygiene rotate', 'description' => 'Inspect or rotate an environment encryption key.'],
            ['command' => 'deploy [environment]', 'description' => 'Replace a local .env by default; supports --merge, --backup, --only, and --dry-run.'],
            ['command' => 'deploy local', 'description' => 'Force local env-file output when the manifest has a provider target.'],
            ['command' => 'deploy laravel-forge', 'description' => 'Sync selected values with the Forge CLI; forge is an alias.'],
            ['command' => 'deploy laravel-vapor', 'description' => 'Sync values with the Vapor CLI; vapor is an alias.'],
            ['command' => 'deploy laravel-cloud', 'description' => 'Set selected values with the Cloud CLI; cloud is an alias.'],
        ]" />
    </x-docs.section>

    <x-docs.section id="advanced" title="Agent and advanced commands">
        <x-docs.command-table :commands="[
            ['command' => 'agent instructions', 'description' => 'Print the recommended Ghostable rules for coding agents.'],
            ['command' => 'agent capabilities', 'description' => 'List the safe default agent command allowlist; supports --json.'],
            ['command' => 'agent init', 'description' => 'Write Ghostable guidance to AGENTS.md; --dry-run prints without writing.'],
            ['command' => 'adopt --all --ci', 'description' => 'Generate a broad project-adoption prompt including optional CI recommendations.'],
            ['command' => 'setup --agent-instructions', 'description' => 'Initialize the project and add agent guidance in one workflow.'],
        ]" />
        <p>Commands omitted from the standard interactive menus are advanced integration surfaces. Prefer the documented primary workflow unless Desktop or another controlled tool requires them.</p>
    </x-docs.section>

    <x-docs.section id="conventions" title="Automation conventions" :border="false">
        <ul>
            <li>Use <code>--json</code> for structured output where offered.</li>
            <li>Repeat list flags or pass comma-separated values, such as <code>--only APP_KEY,DATABASE_URL</code>.</li>
            <li>Pass <code>--reason</code> for writes, promotions, deletions, and rotations.</li>
            <li>Use <code>--assume-yes</code> or <code>-y</code> only after a script has fully resolved its target.</li>
            <li>Set the standard <code>NO_COLOR</code> environment variable to disable ANSI styling.</li>
            <li>Use <code>ghostable --version</code> to record the CLI release in diagnostics.</li>
        </ul>
    </x-docs.section>
</x-docs.page>
