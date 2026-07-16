<x-docs.page
    route-name="docs.cli.new-projects"
    title="New Projects"
    section="Getting Started"
    description="Initialize Ghostable with a clean repository, define the first environments, and commit the encrypted project state your team will share."
    :on-this-page="[
        ['label' => 'Initialize interactively', 'href' => '#interactive'],
        ['label' => 'Explicit team setup', 'href' => '#explicit-setup'],
        ['label' => 'Review generated files', 'href' => '#review-files'],
        ['label' => 'Commit the baseline', 'href' => '#commit-baseline'],
        ['label' => 'Create more environments', 'href' => '#more-environments'],
    ]"
>
    <x-docs.section id="interactive" title="Initialize interactively">
        <p>Run setup from the project root:</p>
        <x-docs.terminal title="Initialize Ghostable" :commands="['ghostable setup']" />
        <p>
            Setup asks for a project name and device label, creates a <code>default</code> environment, and detects an existing root <code>.env</code>. If one exists, you can import it immediately. The importing device becomes the first project owner.
        </p>
    </x-docs.section>

    <x-docs.section id="explicit-setup" title="Explicit team setup">
        <p>For a reproducible non-interactive initialization, pass every decision as a flag:</p>
        <x-docs.terminal
            title="Initialize a team project"
            :commands="[
                'ghostable setup --name &quot;Acme API&quot; --device-name &quot;Jordan MacBook&quot; --env default --env staging --env production --language php --framework laravel --package-manager composer --seed-dotenv --create-example --agent-instructions',
            ]"
        />
        <p>
            <code>--seed-dotenv</code> imports the root <code>.env</code> into <code>default</code>. Additional initial environments receive their own keys but are not automatically given the default environment's values. Use <code>env create --from-env</code> or individual promotions to seed them intentionally.
        </p>
        <x-docs.callout type="warning" title="Keep plaintext out of Git">
            Setup does not make an existing <code>.env</code> safe to commit. Keep <code>.env</code> and environment-specific plaintext files in <code>.gitignore</code>; commit the generated <code>.env.example</code> and <code>.ghostable/</code> state instead.
        </x-docs.callout>
    </x-docs.section>

    <x-docs.section id="review-files" title="Review generated files">
        <p>Use status and Git to inspect the baseline before committing it:</p>
        <x-docs.terminal title="Inspect the baseline" :commands="['ghostable status', 'git status --short', 'ghostable validate --env default']" />
        <p>
            The new <code>.ghostable/</code> directory contains a manifest, signed policy, public device record, environment keys and grants, encrypted values, key metadata, and signed events. Your private device keys are stored outside the repository.
        </p>
    </x-docs.section>

    <x-docs.section id="commit-baseline" title="Commit the baseline">
        <x-docs.terminal
            title="Commit Ghostable state"
            :commands="[
                'git add .ghostable .env.example AGENTS.md',
                'git commit -m &quot;Initialize Ghostable v3&quot;',
            ]"
        />
        <p>
            Review <code>.ghostable/</code> diffs with the same care as application code. The values are encrypted, but device names, environment names, change reasons, annotations, and timestamps are intentionally visible metadata.
        </p>
    </x-docs.section>

    <x-docs.section id="more-environments" title="Create more environments" :border="false">
        <p>Create an empty environment, seed only its key layout, or copy non-sensitive values from an existing environment:</p>
        <x-docs.terminal
            title="Create environments"
            :commands="[
                'ghostable env create preview --type preview',
                'ghostable env create staging --type staging --from-env default --seed keys-only',
                'ghostable env create production --type production --from-env staging --seed non-sensitive',
            ]"
        />
        <p>Use <code>--seed all</code> only when copying every value is an intentional and reviewed decision.</p>
    </x-docs.section>
</x-docs.page>
