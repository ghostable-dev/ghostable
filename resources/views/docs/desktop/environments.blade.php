<x-docs.page
    route-name="docs.desktop.workflows.environments"
    title="Environments & Variables"
    product-name="Ghostable Desktop"
    section="Workflows"
    description="Create shared environments, manage encrypted values, inspect versions, and add the metadata that makes configuration understandable."
    :on-this-page="[
        ['label' => 'Shared environments', 'href' => '#environments'],
        ['label' => 'Create an environment', 'href' => '#create-environment'],
        ['label' => 'Work with variables', 'href' => '#variables'],
        ['label' => 'Variable details', 'href' => '#details'],
        ['label' => 'Missing keys', 'href' => '#missing'],
        ['label' => 'A reviewable workflow', 'href' => '#workflow'],
    ]"
>
    <x-docs.section id="environments" title="Shared environments">
        <p>
            A shared environment is an encrypted, repository-backed set of variables such as <code>development</code>, <code>staging</code>, or <code>production</code>. The environment name and type are visible project metadata; values remain encrypted at rest under <code>.ghostable/</code>.
        </p>
        <p>
            Selecting an environment loads only values this device is authorized to read. The table shows version and update information so a reviewer can distinguish a recent change from an unchanged value without exposing history in plaintext.
        </p>
{{-- ghostable:screenshot-placement {"id":"desktop-environment-variables","provider":"ghostable-desktop-v3","shot_id":"environment-variables","alt":"Ghostable Desktop environment variable table with a missing required key warning","caption":"Variables stay scoped to the selected shared environment; required missing keys remain visible above the table."} --}}
{{-- ghostable:screenshot-output desktop-environment-variables:start --}}
<img
    class="block dark:hidden w-full rounded-xl"
    src="{{ asset('images/generated/screenshots/ghostable-desktop-v3/environment-variables-light.png') }}"
    alt="Ghostable Desktop environment variable table with a missing required key warning"
/>

<img
    class="hidden dark:block w-full rounded-xl"
    src="{{ asset('images/generated/screenshots/ghostable-desktop-v3/environment-variables-dark.png') }}"
    alt="Ghostable Desktop environment variable table with a missing required key warning"
/>
<p class="mt-3 text-sm text-zinc-500">Variables stay scoped to the selected shared environment; required missing keys remain visible above the table.</p>
{{-- ghostable:screenshot-output desktop-environment-variables:end --}}

    </x-docs.section>

    <x-docs.section id="create-environment" title="Create an environment">
        <ol>
            <li>Select the plus control beside <strong>Shared environments</strong>.</li>
            <li>Enter a stable lowercase name, such as <code>staging</code>.</li>
            <li>Choose an environment type. Types influence policy and review behavior; they are not cosmetic labels.</li>
            <li>Confirm the change, inspect the new environment, and commit the resulting <code>.ghostable/</code> files.</li>
        </ol>
        <p>
            Environment access is scoped. Creating an environment does not automatically grant every device permission to read or change it. Review <a href="{{ route('docs.desktop.workflows.access') }}">Access & Automation</a> before sharing production access.
        </p>
    </x-docs.section>

    <x-docs.section id="variables" title="Work with variables">
        <p>
            Select the plus control in the variable toolbar to add a key. Use conventional uppercase names and provide a reason when Desktop requests one; the reason becomes part of signed, reviewable activity.
        </p>
        <ul>
            <li><strong>Enabled</strong> variables are emitted normally when writing or injecting an environment.</li>
            <li><strong>Commented</strong> variables can be represented as commented entries in supported file workflows.</li>
            <li><strong>Secret values</strong> are masked in the table. Revealing a value only affects the current authorized device and interface session.</li>
            <li><strong>Order</strong> can be managed so generated files remain predictable and easy to diff.</li>
        </ul>
        <x-docs.callout type="warning" title="Pasting is still plaintext handling">
            Desktop encrypts a value before committing project state, but the clipboard and local machine see the plaintext first. Use a trusted device, clear sensitive clipboard history, and avoid screen sharing while revealing or editing secrets.
        </x-docs.callout>
    </x-docs.section>

    <x-docs.section id="details" title="Variable details">
        <p>
            Select a variable row to open the detail panel. The panel separates the value from descriptive and policy metadata so teams can document a key without placing its secret in a note.
        </p>
        <ul>
            <li>Edit the value and record why it changed.</li>
            <li>Add an encrypted note for context that should travel with the value.</li>
            <li>Inspect version and update metadata.</li>
            <li>Configure dynamic-variable behavior when supported by the project.</li>
            <li>Add annotations used by tooling or team conventions.</li>
            <li>Review the validation rules that apply to the selected key.</li>
        </ul>
        <p>
            Notes should explain ownership, rotation, or usage. Do not duplicate the secret value inside a note, annotation, commit message, or issue tracker.
        </p>
{{-- ghostable:screenshot-placement {"id":"desktop-variable-detail","provider":"ghostable-desktop-v3","shot_id":"variable-detail","alt":"Ghostable Desktop variable detail panel for an encrypted database URL","caption":"Select a variable to inspect and edit its value, note, state, metadata, and policy details."} --}}
{{-- ghostable:screenshot-output desktop-variable-detail:start --}}
<img
    class="block dark:hidden w-full rounded-xl"
    src="{{ asset('images/generated/screenshots/ghostable-desktop-v3/variable-detail-light.png') }}"
    alt="Ghostable Desktop variable detail panel for an encrypted database URL"
/>

<img
    class="hidden dark:block w-full rounded-xl"
    src="{{ asset('images/generated/screenshots/ghostable-desktop-v3/variable-detail-dark.png') }}"
    alt="Ghostable Desktop variable detail panel for an encrypted database URL"
/>
<p class="mt-3 text-sm text-zinc-500">Select a variable to inspect and edit its value, note, state, metadata, and policy details.</p>
{{-- ghostable:screenshot-output desktop-variable-detail:end --}}

    </x-docs.section>

    <x-docs.section id="missing" title="Missing keys">
        <p>
            The banner above the table lists required keys missing from the selected environment. It is derived from project and environment validation rules. Add the missing value or intentionally adjust the rule; do not silence the signal by adding a meaningless placeholder to a production environment.
        </p>
    </x-docs.section>

    <x-docs.section id="workflow" title="A reviewable workflow" :border="false">
        <ol>
            <li>Pull the latest Git branch before editing encrypted state.</li>
            <li>Select the exact environment and confirm its type.</li>
            <li>Change the smallest set of variables and provide useful reasons.</li>
            <li>Run <a href="{{ route('docs.desktop.workflows.validation-review') }}">Validation and Review</a>.</li>
            <li>Inspect <code>git diff -- .ghostable</code>, then commit the encrypted state and signed records.</li>
        </ol>
    </x-docs.section>
</x-docs.page>
