<x-docs.page
    route-name="docs.desktop.projects"
    title="Projects & Setup"
    product-name="Ghostable Desktop"
    section="Getting Started"
    description="Organize repositories in the launcher, initialize new Ghostable state, or adopt an existing environment-file workflow safely."
    :on-this-page="[
        ['label' => 'Groups and projects', 'href' => '#launcher-projects'],
        ['label' => 'Add a repository', 'href' => '#add-project'],
        ['label' => 'Set up Ghostable', 'href' => '#setup'],
        ['label' => 'Adopt an existing project', 'href' => '#adopt'],
        ['label' => 'Commit project state', 'href' => '#commit'],
        ['label' => 'Open an existing project', 'href' => '#existing'],
    ]"
>
    <x-docs.section id="launcher-projects" title="Groups and projects">
        <p>
            Launcher groups are local organization only. They are not Ghostable teams, billing seats, or repository permissions, and changing a group does not modify project files. Use them to mirror the way you already think about clients, organizations, or product areas.
        </p>
        <p>
            Each project record stores a local folder path. Renaming or removing a launcher entry does not rename or delete the repository on disk.
        </p>
    </x-docs.section>

    <x-docs.section id="add-project" title="Add a repository">
        <ol>
            <li>Select the plus button in the Project Launcher.</li>
            <li>Create or choose a group.</li>
            <li>Add a project and select its repository folder.</li>
            <li>Choose <strong>Open</strong>.</li>
        </ol>
        <p>
            Ghostable checks the selected folder for a <code>.ghostable/ghostable.yaml</code> manifest. A configured repository opens normally; a repository without that manifest opens the setup guide.
        </p>
    </x-docs.section>

    <x-docs.section id="setup" title="Set up Ghostable">
        <p>The guided setup collects project and local-device details, then lets you choose which adoption steps to perform:</p>
        <ul>
            <li>Name the first shared environment and choose its environment type.</li>
            <li>Import an existing local <code>.env</code> file as the initial encrypted baseline.</li>
            <li>Create or update <code>.env.example</code>.</li>
            <li>Add managed Ghostable guidance to <code>AGENTS.md</code>.</li>
            <li>Copy a structured adoption prompt covering schema, annotations, example files, hygiene, drift, and optional CI.</li>
            <li>Name the device identity created for this project.</li>
        </ul>
        <x-docs.callout type="security" title="The first device becomes owner">
            Initial setup creates the project's first device identity and owner authority. The private identity is stored outside the repository; only public records and encrypted project state are written under <code>.ghostable/</code>.
        </x-docs.callout>
    </x-docs.section>

    <x-docs.section id="adopt" title="Adopt an existing project">
        <p>
            When a repository already has environment files, choose one authoritative baseline before importing. Review the file for local-only credentials, stale keys, and values that should never become shared development defaults.
        </p>
        <p>
            Setup can import the baseline and generate an example file, but it does not decide which values are safe for your team. After setup, inspect every variable, add validation rules, and run Review before committing.
        </p>
        <x-docs.callout type="warning" title="Do not commit plaintext">
            Commit <code>.ghostable/</code> and a reviewed <code>.env.example</code>. Keep real <code>.env</code> files ignored. Ghostable encrypts shared values; Git cannot protect a plaintext secret that was committed first.
        </x-docs.callout>
    </x-docs.section>

    <x-docs.section id="commit" title="Commit project state">
        <p>
            Review the generated files, then commit the <code>.ghostable/</code> directory through the repository's normal branch and review process. Signed activity, encrypted values, access grants, schemas, and project policy travel with that commit.
        </p>
        <x-docs.terminal title="Review the initial state" :commands="['git status --short', 'git diff -- .ghostable .env.example AGENTS.md', 'git add .ghostable .env.example AGENTS.md', 'git commit -m &quot;Initialize Ghostable&quot;']" />
    </x-docs.section>

    <x-docs.section id="existing" title="Open an existing project" :border="false">
        <p>
            Adding an already-configured repository to the launcher does not grant access. This Mac also needs a valid project device identity and environment grants. If the repository was cloned onto a new machine, follow the <a href="{{ route('docs.cli.team-onboarding') }}">team onboarding and device join workflow</a> before expecting encrypted values to open.
        </p>
    </x-docs.section>
</x-docs.page>
