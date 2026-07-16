<x-docs.page
    route-name="docs.desktop.workflows.local-files"
    title="Local Environment Files"
    product-name="Ghostable Desktop"
    section="Workflows"
    description="Compare encrypted shared state with local files, pull or push deliberate changes, and keep plaintext outside Git."
    :on-this-page="[
        ['label' => 'Two different stores', 'href' => '#stores'],
        ['label' => 'Compare environments', 'href' => '#compare'],
        ['label' => 'Pull a local file', 'href' => '#pull'],
        ['label' => 'Push a local file', 'href' => '#push'],
        ['label' => 'Synchronize a file', 'href' => '#sync'],
        ['label' => 'Example and cleanup tools', 'href' => '#example-cleanup'],
    ]"
>
    <x-docs.section id="stores" title="Two different stores">
        <p>
            Shared Ghostable environments are encrypted repository state. A local <code>.env</code> file is plaintext on one machine. Desktop connects these stores through explicit actions; it does not make a local file safe to commit.
        </p>
        <x-docs.callout type="security" title="Keep local files ignored">
            Confirm <code>.env</code> and any environment-specific plaintext files are covered by <code>.gitignore</code>. Generated <code>.env.example</code> files should contain reviewed placeholders or intentionally safe values only.
        </x-docs.callout>
    </x-docs.section>

    <x-docs.section id="compare" title="Compare environments">
        <p>
            Use <strong>Compare Environments</strong> to inspect key and value differences before copying state. A comparison is read-only: it helps you identify missing, changed, or extra keys without mutating either side.
        </p>
        <p>
            Compare first when promoting configuration between development, staging, and production. Environment types and access policy still apply to the destination.
        </p>
    </x-docs.section>

    <x-docs.section id="pull" title="Pull a local file">
        <p>
            <strong>Pull Local File</strong> writes the selected shared environment into a plaintext file inside the repository. Choose the target path carefully and preview the operation when offered.
        </p>
        <ol>
            <li>Select the source shared environment.</li>
            <li>Open environment actions and choose <strong>Pull Local File</strong>.</li>
            <li>Confirm the path and review the planned keys.</li>
            <li>Write the file, then verify permissions and Git ignore status.</li>
        </ol>
        <p>Pull only when a framework or tool actually requires a file. Prefer direct process injection through the CLI when no file is necessary.</p>
    </x-docs.section>

    <x-docs.section id="push" title="Push a local file">
        <p>
            <strong>Push Local File</strong> reads a plaintext file and updates the selected encrypted environment. It can change multiple values at once, so compare and inspect the destination before confirming.
        </p>
        <x-docs.callout type="warning" title="Push can overwrite shared values">
            A stale local file can replace newer shared configuration. Pull the latest Git state, compare the file, confirm the selected environment, and provide a reason that explains the batch update.
        </x-docs.callout>
    </x-docs.section>

    <x-docs.section id="sync" title="Synchronize a file">
        <p>
            <strong>Sync Local File</strong> reconciles the configured local file and shared environment according to the action's preview. Treat sync as a write operation, not background magic: inspect additions, updates, removals, and commented keys before continuing.
        </p>
        <p>
            When the local and shared versions both changed, stop and decide which value is authoritative. Desktop cannot infer team intent from two valid values.
        </p>
    </x-docs.section>

    <x-docs.section id="example-cleanup" title="Example and cleanup tools" :border="false">
        <p>
            Project Settings can generate <code>.env.example</code> from an environment in dry-run or replace mode. Review which values are kept and which are blanked before replacing an existing file.
        </p>
        <p>
            Cleanup tools can preview local environment files that match the project's conventions and remove them after confirmation. Dry-run first, and only include <code>.env.example</code> when you deliberately want it considered. See <a href="{{ route('docs.desktop.reference.project-settings') }}">Project Settings</a> for the exact controls.
        </p>
    </x-docs.section>
</x-docs.page>
