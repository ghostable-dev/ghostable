<x-docs.page
    route-name="docs.cli.workflows.daily-development"
    title="Daily Development"
    section="Workflows"
    description="Use a predictable, review-first loop for loading configuration, changing values, validating the result, and committing encrypted state."
    :on-this-page="[
        ['label' => 'Start from current state', 'href' => '#start'],
        ['label' => 'Run the application', 'href' => '#run'],
        ['label' => 'Change configuration', 'href' => '#change'],
        ['label' => 'Validate and review', 'href' => '#validate'],
        ['label' => 'Commit the change', 'href' => '#commit'],
        ['label' => 'Clean plaintext files', 'href' => '#clean'],
    ]"
>
    <x-docs.section id="start" title="Start from current state">
        <p>Pull the branch before materializing values so local work uses the latest encrypted records and grants:</p>
        <x-docs.terminal title="Start work" :commands="['git pull --ff-only', 'ghostable status', 'ghostable env diff --env default --file .env']" />
        <p>If the diff is intentional local work, keep it. Otherwise, pull the shared environment into the file before starting.</p>
    </x-docs.section>

    <x-docs.section id="run" title="Run the application">
        <p>Prefer process injection when the application does not require a physical <code>.env</code> file:</p>
        <x-docs.terminal
            title="Run with Ghostable values"
            :commands="[
                'ghostable env run --env default -- php artisan serve',
                'ghostable env run --env default --mask-output -- npm test',
            ]"
        />
        <p>Use <code>env pull</code> when framework tooling specifically reads a file.</p>
    </x-docs.section>

    <x-docs.section id="change" title="Change configuration">
        <p>Edit the local env file with normal developer tools, then inspect the redacted diff before storing it:</p>
        <x-docs.terminal
            title="Store a reviewed change"
            :commands="[
                'ghostable env diff --env default --file .env',
                'ghostable env push --env default --file .env --reason &quot;Configure local mail testing&quot;',
            ]"
        />
        <p>For one key, use <code>var push --file</code>. Use <code>env sync</code> only when keys absent from the file should be deleted.</p>
    </x-docs.section>

    <x-docs.section id="validate" title="Validate and review">
        <x-docs.terminal
            title="Pre-commit checks"
            :commands="[
                'ghostable validate --env default',
                'ghostable review',
                'ghostable hygiene report --env default',
            ]"
        />
        <p>Validation checks declared schema rules. Review checks changed-code ENV usage and hard-coded secrets. Hygiene checks operational age and rotation state.</p>
    </x-docs.section>

    <x-docs.section id="commit" title="Commit the change">
        <x-docs.terminal title="Commit encrypted state" :commands="['git diff -- .ghostable', 'git add .ghostable && git commit -m &quot;Configure local mail testing&quot;']" />
        <p>Commit the application code and its Ghostable state together when they are part of the same change.</p>
    </x-docs.section>

    <x-docs.section id="clean" title="Clean plaintext files" :border="false">
        <p>At the end of sensitive work, preview and remove project-root env files:</p>
        <x-docs.terminal title="Remove local plaintext" :commands="['ghostable env clean --dry-run', 'ghostable env clean']" />
        <p>
            Cleanup removes <code>.env</code>, <code>.env.*</code>, and Ghostable backup files from the project root. It keeps <code>.env.example</code> unless <code>--include-example</code> is explicitly passed.
        </p>
    </x-docs.section>
</x-docs.page>
