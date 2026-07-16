<x-docs.page
    route-name="docs.desktop.workflows.validation-review"
    title="Validation & Review"
    product-name="Ghostable Desktop"
    section="Workflows"
    description="Define configuration contracts, find missing or invalid values, and scan the repository for hard-coded secrets and environment drift."
    :on-this-page="[
        ['label' => 'Validation and Review differ', 'href' => '#difference'],
        ['label' => 'Validation rules', 'href' => '#rules'],
        ['label' => 'Environment overrides', 'href' => '#overrides'],
        ['label' => 'Review scanning', 'href' => '#review'],
        ['label' => 'Scan levels and ignores', 'href' => '#scan-settings'],
        ['label' => 'Before committing', 'href' => '#before-commit'],
    ]"
>
    <x-docs.section id="difference" title="Validation and Review differ">
        <p>
            <strong>Validation</strong> checks environment values against an explicit schema. <strong>Review</strong> inspects repository hygiene, including hard-coded secrets and environment drift. Run both: a value can satisfy its schema while the same credential is accidentally embedded in source code.
        </p>
    </x-docs.section>

    <x-docs.section id="rules" title="Validation rules">
        <p>
            The Validation page lists project rules from <code>.ghostable/schema.yaml</code> and shows diagnostics for the current environment. Add rules for keys whose absence or shape should block a release.
        </p>
        <p>Supported rules are defined by the bundled CLI and include common contracts such as:</p>
        <x-docs.command-table :commands="[
            ['command' => 'required', 'description' => 'Require the key to exist and contain an acceptable value.'],
            ['command' => 'format / pattern', 'description' => 'Require a value to match an expected shape.'],
            ['command' => 'allowed values', 'description' => 'Restrict a key to an approved set.'],
            ['command' => 'different_from', 'description' => 'Require the value to differ from another environment.'],
            ['command' => 'unique', 'description' => 'Require an environment-specific value where reuse would be unsafe.'],
        ]" />
        <p>
            Use the <a href="{{ route('docs.cli.reference.validation') }}">CLI Validation reference</a> for the current rule grammar and edge cases. Desktop edits the same schema.
        </p>
{{-- ghostable:screenshot-placement {"id":"desktop-project-validation","provider":"ghostable-desktop-v3","shot_id":"project-validation","alt":"Ghostable Desktop project validation rules and diagnostics","caption":"Global rules and environment overrides make missing or invalid configuration explicit."} --}}
{{-- ghostable:screenshot-output desktop-project-validation:start --}}
<img
    class="block dark:hidden w-full rounded-xl"
    src="{{ asset('images/generated/screenshots/ghostable-desktop-v3/project-validation-light.png') }}"
    alt="Ghostable Desktop project validation rules and diagnostics"
/>

<img
    class="hidden dark:block w-full rounded-xl"
    src="{{ asset('images/generated/screenshots/ghostable-desktop-v3/project-validation-dark.png') }}"
    alt="Ghostable Desktop project validation rules and diagnostics"
/>
<p class="mt-3 text-sm text-zinc-500">Global rules and environment overrides make missing or invalid configuration explicit.</p>
{{-- ghostable:screenshot-output desktop-project-validation:end --}}

    </x-docs.section>

    <x-docs.section id="overrides" title="Environment overrides">
        <p>
            Global rules apply across environments. Environment overrides narrow or adjust a rule for one target, such as enforcing a production-only hostname or allowing a development placeholder.
        </p>
        <p>
            Prefer a clear global contract plus small overrides. Duplicating the entire schema per environment makes drift harder to review.
        </p>
    </x-docs.section>

    <x-docs.section id="review" title="Review scanning">
        <p>
            The Review page runs repository checks through the CLI. Hard-coded secret findings identify likely credentials in tracked or working-tree source. Environment checks surface keys that are missing, unexpectedly reused, or inconsistent with project expectations.
        </p>
        <p>
            Review findings are evidence, not automatic rotation. If a real credential reached source or Git history, remove it from code, rotate it at the provider, update Ghostable, and assess the history separately.
        </p>
{{-- ghostable:screenshot-placement {"id":"desktop-project-review","provider":"ghostable-desktop-v3","shot_id":"project-review","alt":"Ghostable Desktop project Review results for environment and secret findings","caption":"Review combines ENV diagnostics, hard-coded secret scanning, and hygiene findings."} --}}
{{-- ghostable:screenshot-output desktop-project-review:start --}}
<img
    class="block dark:hidden w-full rounded-xl"
    src="{{ asset('images/generated/screenshots/ghostable-desktop-v3/project-review-light.png') }}"
    alt="Ghostable Desktop project Review results for environment and secret findings"
/>

<img
    class="hidden dark:block w-full rounded-xl"
    src="{{ asset('images/generated/screenshots/ghostable-desktop-v3/project-review-dark.png') }}"
    alt="Ghostable Desktop project Review results for environment and secret findings"
/>
<p class="mt-3 text-sm text-zinc-500">Review combines ENV diagnostics, hard-coded secret scanning, and hygiene findings.</p>
{{-- ghostable:screenshot-output desktop-project-review:end --}}

    </x-docs.section>

    <x-docs.section id="scan-settings" title="Scan levels and ignored paths">
        <x-docs.command-table :commands="[
            ['command' => 'relaxed', 'description' => 'Lower-noise checks for repositories with many fixtures or generated patterns.'],
            ['command' => 'standard', 'description' => 'The default balance for normal application repositories.'],
            ['command' => 'strict', 'description' => 'Broader detection when false positives are acceptable during a security-focused review.'],
        ]" />
        <p>
            Ignored paths are project policy. Keep the list narrow and review every addition. Generated directories and encrypted Ghostable value paths are reasonable defaults; application source should not be excluded merely to make a finding disappear.
        </p>
    </x-docs.section>

    <x-docs.section id="before-commit" title="Before committing" :border="false">
        <ol>
            <li>Resolve blocking validation errors for each changed environment.</li>
            <li>Run Review at the project's configured scan level.</li>
            <li>Investigate new findings and document intentional exceptions through project policy.</li>
            <li>Inspect the Git diff for schema, scan settings, ignored paths, and encrypted state.</li>
            <li>Commit the smallest coherent change.</li>
        </ol>
    </x-docs.section>
</x-docs.page>
