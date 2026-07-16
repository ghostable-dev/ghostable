<x-docs.page
    route-name="docs.cli.existing-projects"
    title="Existing Projects"
    section="Getting Started"
    description="Adopt an existing repository without losing its working environment, then introduce encrypted state, validation, examples, and team policy in reviewable steps."
    :on-this-page="[
        ['label' => 'Prepare the repository', 'href' => '#prepare'],
        ['label' => 'Import the first baseline', 'href' => '#import'],
        ['label' => 'Generate an adoption plan', 'href' => '#adopt'],
        ['label' => 'Reconcile the project', 'href' => '#reconcile'],
        ['label' => 'Roll out to the team', 'href' => '#rollout'],
    ]"
>
    <x-docs.section id="prepare" title="Prepare the repository">
        <p>
            Start on a dedicated branch with a known-good local <code>.env</code>. Confirm the application boots before migration, verify plaintext env files are ignored, and keep a secure copy outside the repository until the new baseline has been tested.
        </p>
        <x-docs.terminal title="Preflight" :commands="['git switch -c adopt-ghostable-v3', 'git check-ignore .env']" />
        <x-docs.callout type="warning" title="Choose one authoritative source">
            If developers have divergent <code>.env</code> files, decide which file represents the first shared baseline. Importing an arbitrary developer machine can preserve accidental or stale values.
        </x-docs.callout>
    </x-docs.section>

    <x-docs.section id="import" title="Import the first baseline">
        <x-docs.terminal
            title="Initialize from an existing .env"
            :commands="[
                'ghostable setup --seed-dotenv --create-example --agent-instructions',
                'ghostable env diff --env default --file .env',
                'ghostable validate --env default',
            ]"
        />
        <p>
            Setup encrypts the imported values locally and records the reason as a setup seed. The generated <code>.env.example</code> defaults to retaining non-sensitive example values while blanking values that look sensitive.
        </p>
    </x-docs.section>

    <x-docs.section id="adopt" title="Generate an adoption plan">
        <p>
            The <code>adopt</code> command produces a plain-text prompt that a developer or coding agent can use to assess schema rules, annotations, example-file drift, hygiene, and optional CI work:
        </p>
        <x-docs.terminal title="Adoption prompt" :commands="['ghostable adopt --all --ci']" />
        <p>
            Treat the prompt as a review aid. It is designed to separate evidence-backed changes from open questions such as secret ownership, deployment scope, and rotation expectations.
        </p>
    </x-docs.section>

    <x-docs.section id="reconcile" title="Reconcile the project">
        <ol>
            <li>Compare encrypted state to the working file with <code>env diff</code>.</li>
            <li>Add validation rules for values whose format or presence is understood.</li>
            <li>Generate <code>.env.example</code> and review every retained example value.</li>
            <li>Run <code>ghostable review</code> to find changed-code ENV drift and hard-coded secrets.</li>
            <li>Run the application using <code>ghostable env run</code> or a freshly pulled file.</li>
        </ol>
        <x-docs.terminal
            title="Reconciliation checks"
            :commands="[
                'ghostable example generate --dry-run',
                'ghostable review',
                'ghostable env run --env default -- php artisan test',
            ]"
        />
    </x-docs.section>

    <x-docs.section id="rollout" title="Roll out to the team" :border="false">
        <p>
            Commit the reviewed <code>.ghostable/</code> state, example file, and any agent instructions. Merge the baseline before teammates join so every device request is made against the same project ID and policy.
        </p>
        <p>
            Continue with <a href="{{ route('docs.cli.team-onboarding') }}">Team Onboarding</a> for the join, request, approval, and role-grant workflow.
        </p>
    </x-docs.section>
</x-docs.page>
