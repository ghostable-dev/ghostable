<x-docs.page
    route-name="docs.cli.index"
    title="Ghostable CLI 3.x"
    section="Documentation"
    description="Local-first environment management for teams that want encrypted, reviewable configuration without sending plaintext secrets to a hosted secrets service."
    :on-this-page="[
        ['label' => 'What Ghostable does', 'href' => '#what-ghostable-does'],
        ['label' => 'How it works', 'href' => '#how-it-works'],
        ['label' => 'A typical workflow', 'href' => '#typical-workflow'],
        ['label' => 'CLI and Desktop', 'href' => '#cli-and-desktop'],
        ['label' => 'Where to begin', 'href' => '#where-to-begin'],
    ]"
>
    <x-docs.section id="what-ghostable-does" title="What Ghostable does">
        <p>
            Ghostable stores encrypted environment values and the signed records needed to manage them inside your project repository. The CLI creates environments, encrypts and decrypts values, validates configuration, reviews code for environment drift and hard-coded secrets, and prepares values for local processes or deployment providers.
        </p>
        <p>
            There is no Ghostable login and no hosted Ghostable vault in the v3 architecture. Your repository carries the encrypted project state; each authorized device keeps its private identity outside the repository.
        </p>
    </x-docs.section>

    <x-docs.section id="how-it-works" title="How it works">
        <ol>
            <li><strong>Initialize a repository.</strong> <code>ghostable setup</code> creates a project manifest, the first device identity, environment keys, policy, and signed activity state.</li>
            <li><strong>Commit encrypted state.</strong> The <code>.ghostable/</code> directory is designed to travel through Git and code review. Plaintext <code>.env</code> files are not.</li>
            <li><strong>Grant access by device.</strong> New team members create their own device identities and request a role for one or more environments.</li>
            <li><strong>Work locally.</strong> Pull to a file only when necessary, or inject values directly into a command with <code>env run</code>.</li>
            <li><strong>Review every change.</strong> Signed value history, validation, hygiene reports, and secret scanning make environment changes visible without disclosing secret contents.</li>
        </ol>
    </x-docs.section>

    <x-docs.section id="typical-workflow" title="A typical workflow">
        <p>A normal development loop is intentionally small:</p>
        <x-docs.terminal
            title="Project workflow"
            :commands="[
                'ghostable status',
                'ghostable env diff --env default --file .env',
                'ghostable env push --env default --file .env --reason &quot;Update local configuration&quot;',
                'ghostable validate --env default',
                'ghostable review',
                'git add .ghostable && git commit -m &quot;Update encrypted environment&quot;',
            ]"
        />
        <p>
            Commands prompt for missing choices in an interactive terminal. Scripts, CI jobs, and coding agents should pass explicit flags and prefer <code>--json</code> output.
        </p>
    </x-docs.section>

    <x-docs.section id="cli-and-desktop" title="CLI and Desktop">
        <p>
            The CLI is Ghostable's versioned engine and the source of truth for project behavior. Ghostable Desktop is a paid macOS interface that runs the CLI underneath, so encryption, repository formats, access rules, and command behavior remain consistent between both products.
        </p>
        <p>
            Use these versioned 3.x docs for the shared engine. Use the <a href="{{ route('docs.desktop.index') }}">Desktop documentation</a> for installation, application diagnostics, and interface-specific guidance.
        </p>
    </x-docs.section>

    <x-docs.section id="where-to-begin" title="Where to begin" :border="false">
        <ul>
            <li>Install the CLI using the <a href="{{ route('docs.cli.installation') }}">installation guide</a>.</li>
            <li>Use <a href="{{ route('docs.cli.new-projects') }}">New Projects</a> when no Ghostable state exists yet.</li>
            <li>Use <a href="{{ route('docs.cli.existing-projects') }}">Existing Projects</a> to adopt a repository that already has one or more <code>.env</code> files.</li>
            <li>Read <a href="{{ route('docs.cli.team-onboarding') }}">Team Onboarding</a> before adding a second developer.</li>
            <li>Review the <a href="{{ route('docs.cli.reference.security') }}">Security</a> page before using Ghostable for production credentials.</li>
        </ul>
    </x-docs.section>
</x-docs.page>
