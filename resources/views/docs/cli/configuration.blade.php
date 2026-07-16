<x-docs.page
    route-name="docs.cli.reference.configuration"
    title="Configuration"
    section="Reference"
    description="Configure project identity, environment types, activity, deployment hints, scan behavior, validation, hygiene, and runtime environment variables."
    :on-this-page="[
        ['label' => 'Project manifest', 'href' => '#manifest'],
        ['label' => 'Manifest fields', 'href' => '#fields'],
        ['label' => 'Scan defaults', 'href' => '#scan'],
        ['label' => 'Schema and hygiene files', 'href' => '#policy-files'],
        ['label' => 'Runtime environment', 'href' => '#runtime'],
        ['label' => 'Editing configuration', 'href' => '#editing'],
    ]"
>
    <x-docs.section id="manifest" title="Project manifest">
        <p><code>.ghostable/ghostable.yaml</code> identifies the project and its stable operational defaults:</p>
        <pre class="overflow-x-auto rounded-xl border border-gray-200 bg-gray-50 p-5 font-mono text-sm leading-7 text-gray-700 dark:border-white/10 dark:bg-white/[0.04] dark:text-gray-300"><code>schema: ghostable.project.v1
id: project_01example
name: Acme API
language: php
framework: laravel
packageManager: composer
deployTarget: laravel-forge
activity:
  mode: minimal
  auditEnvironments:
    - production
    - staging
environments:
  default:
    type: local
  staging:
    type: staging
  production:
    type: production
scan:
  level: standard
  ignores:
    - .git/**
    - node_modules/**
    - vendor/**
    - .ghostable/environments/**/values/**
    - .ghostable/environments/**/keys/**</code></pre>
    </x-docs.section>

    <x-docs.section id="fields" title="Manifest fields">
        <x-docs.command-table :commands="[
            ['command' => 'schema', 'description' => 'Manifest format identifier; v3 currently writes ghostable.project.v1.'],
            ['command' => 'id', 'description' => 'Stable cryptographic project identifier. Do not copy it to create a separate project.'],
            ['command' => 'name', 'description' => 'Human-readable project name.'],
            ['command' => 'language / framework / packageManager', 'description' => 'Optional project hints captured during setup.'],
            ['command' => 'deployTarget', 'description' => 'Optional default target: local, laravel-forge, laravel-vapor, or laravel-cloud.'],
            ['command' => 'activity.mode', 'description' => 'Signed activity mode: off, minimal, or full.'],
            ['command' => 'activity.auditEnvironments', 'description' => 'Environments highlighted for audit behavior; defaults to production and staging.'],
            ['command' => 'environments', 'description' => 'Map of environment names to intent-bearing type labels.'],
            ['command' => 'scan.level', 'description' => 'Default hard-coded secret scan level: relaxed, standard, or strict.'],
            ['command' => 'scan.ignores', 'description' => 'Glob patterns excluded from local review and related reference scans.'],
        ]" />
    </x-docs.section>

    <x-docs.section id="scan" title="Scan defaults">
        <p>
            New manifests ignore Git internals, <code>node_modules</code>, <code>vendor</code>, <code>dist</code>, <code>build</code>, encrypted value records, and environment key records. Add project-specific generated or fixture paths only after confirming they do not need scanning.
        </p>
        <p>Per-run <code>review --level</code> and <code>--ignore</code> flags override or extend the manifest behavior without changing the committed default.</p>
    </x-docs.section>

    <x-docs.section id="policy-files" title="Schema and hygiene files">
        <ul>
            <li><code>.ghostable/schema.yaml</code> declares project-wide validation rules.</li>
            <li><code>.ghostable/schemas/&lt;env&gt;.yaml</code> adds environment-specific rules.</li>
            <li><code>.ghostable/hygiene.yaml</code> declares project and environment-specific variable rotation intervals.</li>
        </ul>
        <p>These files are plaintext policy and must not contain credentials or confidential operational notes.</p>
    </x-docs.section>

    <x-docs.section id="runtime" title="Runtime environment">
        <x-docs.command-table :commands="[
            ['command' => 'GHOSTABLE_CI_TOKEN', 'description' => 'Scoped non-interactive automation credential loaded instead of a local device identity.'],
            ['command' => 'GHOSTABLE_KEYSTORE', 'description' => 'Overrides the local file-backed identity-store directory.'],
            ['command' => 'XDG_CONFIG_HOME', 'description' => 'Controls the default Linux/Unix configuration root when GHOSTABLE_KEYSTORE is unset.'],
            ['command' => 'NO_COLOR', 'description' => 'Disables ANSI color output when set to any non-empty value.'],
        ]" />
    </x-docs.section>

    <x-docs.section id="editing" title="Editing configuration" :border="false">
        <p>
            Use <code>env create</code>, <code>env rename</code>, <code>env delete</code>, <code>schema</code>, and <code>hygiene rotation</code> commands where possible so related signed state stays consistent. If a manifest hint or scan pattern must be edited manually, review the diff and run <code>ghostable status</code>, validation, and review afterward.
        </p>
    </x-docs.section>
</x-docs.page>
