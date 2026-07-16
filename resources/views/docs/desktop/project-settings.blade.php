<x-docs.page
    route-name="docs.desktop.reference.project-settings"
    title="Project Settings"
    product-name="Ghostable Desktop"
    section="Reference"
    description="Configure the current repository path, environment behavior, generated files, cleanup, review policy, and activity coverage."
    :on-this-page="[
        ['label' => 'Project folder', 'href' => '#folder'],
        ['label' => 'Environment types', 'href' => '#environment-types'],
        ['label' => 'Generate an example file', 'href' => '#example'],
        ['label' => 'Clean local files', 'href' => '#clean'],
        ['label' => 'Review settings', 'href' => '#review'],
        ['label' => 'Activity settings', 'href' => '#activity'],
    ]"
>
    <x-docs.section id="folder" title="Project folder">
        <p>
            Project Settings shows the repository folder used by the current window. <strong>Change</strong> points the launcher record and project window at a different local folder after the new path is validated.
        </p>
        <x-docs.callout type="warning" title="Changing folders changes project scope">
            Confirm the destination repository before changing this path. Project actions, CLI commands, file cleanup, and local-file writes are scoped to the selected folder.
        </x-docs.callout>
{{-- ghostable:screenshot-placement {"id":"desktop-project-settings","provider":"ghostable-desktop-v3","shot_id":"project-settings","alt":"Ghostable Desktop project settings for repository and review behavior","caption":"Project settings scope file generation, cleanup, Review, and Activity to one repository."} --}}
{{-- ghostable:screenshot-output desktop-project-settings:start --}}
<img
    class="block dark:hidden w-full rounded-xl"
    src="{{ asset('images/generated/screenshots/ghostable-desktop-v3/project-settings-light.png') }}"
    alt="Ghostable Desktop project settings for repository and review behavior"
/>

<img
    class="hidden dark:block w-full rounded-xl"
    src="{{ asset('images/generated/screenshots/ghostable-desktop-v3/project-settings-dark.png') }}"
    alt="Ghostable Desktop project settings for repository and review behavior"
/>
<p class="mt-3 text-sm text-zinc-500">Project settings scope file generation, cleanup, Review, and Activity to one repository.</p>
{{-- ghostable:screenshot-output desktop-project-settings:end --}}

    </x-docs.section>

    <x-docs.section id="environment-types" title="Environment types">
        <p>
            Assign each shared environment a type that reflects its operational role. Types help project policy and review distinguish disposable development configuration from staging or production configuration.
        </p>
        <p>
            Renaming or retyping an environment is repository state. Review access grants, validation overrides, automation credentials, and deployment references that target the previous name or type.
        </p>
    </x-docs.section>

    <x-docs.section id="example" title="Generate an example file">
        <p>
            Choose a source environment and preview how Ghostable would generate <code>.env.example</code>. The dry run reports which keys would be included and which values would be kept or blanked.
        </p>
        <p>
            Replace mode writes the result to disk. Inspect the existing example file first, because handwritten comments or framework instructions may need to be preserved manually.
        </p>
        <x-docs.callout type="security" title="Examples are public configuration contracts">
            Keep only intentionally safe defaults. API tokens, private URLs, credentials, and realistic production secrets do not belong in <code>.env.example</code>.
        </x-docs.callout>
    </x-docs.section>

    <x-docs.section id="clean" title="Clean local files">
        <p>
            Cleanup discovers plaintext environment files using the project's supported patterns. Run a dry preview, inspect every candidate, then remove only files you no longer need.
        </p>
        <p>
            The option to include the example file broadens the cleanup scope to <code>.env.example</code>. Leave it disabled for routine secret-file cleanup unless deleting or regenerating the example is intentional.
        </p>
    </x-docs.section>

    <x-docs.section id="review" title="Review settings">
        <p>
            Select the repository scan level—relaxed, standard, or strict—and maintain ignored paths. These settings affect the Review surface and are shared through project configuration.
        </p>
        <p>
            Review ignored-path changes as carefully as source changes. A broad ignore can hide future hard-coded credentials, while an unnecessarily strict level can create noise that trains reviewers to dismiss findings.
        </p>
    </x-docs.section>

    <x-docs.section id="activity" title="Activity settings" :border="false">
        <p>
            Choose <strong>off</strong>, <strong>minimal</strong>, or <strong>full</strong> activity and select environments that deserve audit coverage. Save the settings, inspect the generated project diff, and communicate retention changes to the team.
        </p>
        <p>
            See <a href="{{ route('docs.desktop.workflows.activity') }}">Activity</a> for signature semantics and purge limitations.
        </p>
    </x-docs.section>
</x-docs.page>
