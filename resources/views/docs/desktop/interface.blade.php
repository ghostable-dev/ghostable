<x-docs.page
    route-name="docs.desktop.interface"
    title="Interface Tour"
    product-name="Ghostable Desktop"
    section="Getting Started"
    description="Understand the launcher, project sidebar, environment workspace, detail panels, search controls, and settings surfaces."
    :on-this-page="[
        ['label' => 'Window model', 'href' => '#windows'],
        ['label' => 'Project sidebar', 'href' => '#sidebar'],
        ['label' => 'Variable workspace', 'href' => '#workspace'],
        ['label' => 'Search and filters', 'href' => '#search'],
        ['label' => 'Context actions', 'href' => '#actions'],
        ['label' => 'Application and project settings', 'href' => '#settings'],
    ]"
>
    <x-docs.section id="windows" title="Window model">
        <p>
            The Project Launcher remains the local index, and every opened repository gets its own project window. This keeps project state and navigation scoped to one repository while letting you work across several repositories at once.
        </p>
        <p>
            Application settings apply across Desktop. Project settings are stored with or derived from the current repository and only affect that project.
        </p>
    </x-docs.section>

    <x-docs.section id="sidebar" title="Project sidebar">
        <p>
            The top of the sidebar identifies the project and launcher group. Shared environments appear next, followed by the primary project surfaces:
        </p>
        <ul>
            <li><strong>Activity</strong> shows signed project events.</li>
            <li><strong>Validation</strong> shows global and environment-specific rules and diagnostics.</li>
            <li><strong>Review</strong> scans source and configuration hygiene.</li>
            <li><strong>Access</strong> manages devices, requests, grants, and automation credentials.</li>
            <li><strong>Settings</strong> controls the repository path, environment types, file generation, cleanup, review, and activity.</li>
        </ul>
        <p>Use the sidebar control at the top to collapse or expand it when you need more workspace.</p>
    </x-docs.section>

    <x-docs.section id="workspace" title="Variable workspace">
        <p>
            Select an environment to show its variables. The table displays the key, a visible or masked value, version, and last-updated time. A missing-keys banner appears when validation requires keys that the environment does not contain.
        </p>
        <p>
            Select a row to open its detail panel. From there you can edit the value and note, change enabled or commented state, inspect metadata, configure dynamic behavior, and manage validation or annotations supported by the engine.
        </p>
    </x-docs.section>

    <x-docs.section id="search" title="Search and filters">
        <p>
            The magnifying-glass control searches the current surface. Variable filters narrow rows by state or metadata, while Activity and Review expose controls relevant to their own results. Search never changes encrypted project state.
        </p>
        <p>
            The environment search beside <strong>Shared environments</strong> helps when a project has many targets. The plus controls beside environments and in the variable toolbar create new records.
        </p>
    </x-docs.section>

    <x-docs.section id="actions" title="Context and environment actions">
        <p>
            Use context menus for variable operations and the environment actions menu for <strong>Compare Environments</strong>, <strong>Pull Local File</strong>, <strong>Push Local File</strong>, and <strong>Sync Local File</strong>. Preview destructive file operations before confirming them.
        </p>
        <p>
            Desktop invokes explicit CLI operations behind these controls. A failed action reports the command error without silently switching to a different repository or environment.
        </p>
    </x-docs.section>

    <x-docs.section id="settings" title="Application and project settings" :border="false">
        <p>
            Open launcher settings for defaults, appearance, licensing, and version information. Open <strong>Settings</strong> at the bottom of a project sidebar for repository-specific behavior. See <a href="{{ route('docs.desktop.reference.application-settings') }}">Application Settings</a> and <a href="{{ route('docs.desktop.reference.project-settings') }}">Project Settings</a> for every control.
        </p>
    </x-docs.section>
</x-docs.page>
