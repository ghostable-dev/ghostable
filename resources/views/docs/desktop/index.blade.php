<x-docs.page
    route-name="docs.desktop.index"
    title="Ghostable Desktop"
    product-name="Ghostable Desktop"
    section="Desktop"
    description="A paid desktop interface for managing Ghostable projects, encrypted environments, validation, reviews, activity, and device access without leaving your development workflow."
    :on-this-page="[
        ['label' => 'Desktop and CLI', 'href' => '#desktop-and-cli'],
        ['label' => 'What you can do', 'href' => '#what-you-can-do'],
        ['label' => 'The launcher', 'href' => '#launcher'],
        ['label' => 'Licensing', 'href' => '#licensing'],
        ['label' => 'Where to begin', 'href' => '#where-to-begin'],
    ]"
>
    <x-docs.section id="desktop-and-cli" title="Desktop and CLI">
        <p>
            Ghostable Desktop is an Electron application built around the Ghostable CLI engine. The interface reads and writes the same repository-backed <code>.ghostable/</code> state as the CLI, so a change made in Desktop is visible to CLI users and can be reviewed through Git.
        </p>
        <p>
            Desktop is unversioned documentation for the current paid client. The <a href="{{ route('docs.cli.index') }}">CLI 3.x documentation</a> remains the source of truth for encryption, repository formats, role semantics, and command behavior.
        </p>
        <x-docs.callout type="security" title="Local-first by design">
            Project secrets are encrypted and decrypted on authorized devices. Ghostable does not operate a hosted vault that receives plaintext project values. License validation is a separate application entitlement check and does not upload your environment data.
        </x-docs.callout>
    </x-docs.section>

    <x-docs.section id="what-you-can-do" title="What you can do">
        <ul>
            <li>Organize local repositories into groups and open each project in its own window.</li>
            <li>Initialize a new project or adopt existing <code>.env</code> files with a guided setup flow.</li>
            <li>Create shared environments, edit encrypted variables, attach notes and annotations, and inspect version metadata.</li>
            <li>Compare, pull, push, and synchronize local environment files.</li>
            <li>Define validation rules and review source code for hard-coded secrets and environment drift.</li>
            <li>Inspect signed activity and manage human devices, access requests, grants, and scoped automation credentials.</li>
            <li>Configure project cleanup, example-file generation, review levels, activity modes, the default IDE, and appearance.</li>
        </ul>
    </x-docs.section>

    <x-docs.section id="launcher" title="The launcher">
        <p>
            The Project Launcher is Desktop's local index. Groups help you arrange repositories by company, client, or product; projects point to folders already present on this Mac. The search field filters the launcher, while the plus button creates a group or adds a project.
        </p>
        <p>
            A <strong>Setup</strong> badge means a Ghostable manifest was found. A <strong>Not setup</strong> badge means the folder can be opened into the guided initialization flow.
        </p>
{{-- ghostable:screenshot-placement {"id":"desktop-launcher-overview","provider":"ghostable-desktop-v3","shot_id":"launcher-overview","alt":"Ghostable Desktop Project Launcher with a configured and unconfigured project","caption":"The local Project Launcher organizes repositories into groups and shows setup status."} --}}
{{-- ghostable:screenshot-output desktop-launcher-overview:start --}}
<img
    class="block dark:hidden w-full rounded-xl"
    src="{{ asset('images/generated/screenshots/ghostable-desktop-v3/launcher-overview-light.png') }}"
    alt="Ghostable Desktop Project Launcher with a configured and unconfigured project"
/>

<img
    class="hidden dark:block w-full rounded-xl"
    src="{{ asset('images/generated/screenshots/ghostable-desktop-v3/launcher-overview-dark.png') }}"
    alt="Ghostable Desktop Project Launcher with a configured and unconfigured project"
/>
<p class="mt-3 text-sm text-zinc-500">The local Project Launcher organizes repositories into groups and shows setup status.</p>
{{-- ghostable:screenshot-output desktop-launcher-overview:end --}}

    </x-docs.section>

    <x-docs.section id="licensing" title="Licensing">
        <p>
            A valid Desktop license is required to open and use project windows. You can still reach the launcher and application settings before activation so you can enter a key, inspect the installed version, or recover a license.
        </p>
        <p>
            Personal and team licenses are perpetual for the purchased app version and include one year of updates. Device activation limits and team seat limits depend on the selected plan. See <a href="{{ route('docs.desktop.reference.licensing') }}">Licensing & Updates</a> for activation, offline validation, transfers, recovery, and renewal behavior.
        </p>
    </x-docs.section>

    <x-docs.section id="where-to-begin" title="Where to begin" :border="false">
        <ol>
            <li><a href="{{ route('docs.desktop.installation') }}">Install Ghostable Desktop</a> and move it to Applications.</li>
            <li>Activate a license from <strong>Settings → License</strong>.</li>
            <li><a href="{{ route('docs.desktop.projects') }}">Add a repository and complete project setup</a>.</li>
            <li>Take the <a href="{{ route('docs.desktop.interface') }}">interface tour</a>, then learn the <a href="{{ route('docs.desktop.workflows.environments') }}">environment and variable workflow</a>.</li>
            <li>Review <a href="{{ route('docs.desktop.reference.security') }}">Security & Storage</a> before using production credentials.</li>
        </ol>
    </x-docs.section>
</x-docs.page>
