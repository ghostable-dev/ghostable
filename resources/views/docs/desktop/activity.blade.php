<x-docs.page
    route-name="docs.desktop.workflows.activity"
    title="Activity"
    product-name="Ghostable Desktop"
    section="Workflows"
    description="Inspect signed project events, tune audit coverage, filter history, and understand what activity does and does not prove."
    :on-this-page="[
        ['label' => 'Signed project history', 'href' => '#history'],
        ['label' => 'Activity modes', 'href' => '#modes'],
        ['label' => 'Search and filters', 'href' => '#filters'],
        ['label' => 'Signature status', 'href' => '#signatures'],
        ['label' => 'Purge activity', 'href' => '#purge'],
    ]"
>
    <x-docs.section id="history" title="Signed project history">
        <p>
            Activity displays repository-backed events recorded by the Ghostable engine. Each row can identify the action, environment or key context, actor device, time, and signature result without showing secret plaintext.
        </p>
        <p>
            Because the log travels through Git, pull the latest branch before investigating an event. A local checkout only contains history present in its current repository state.
        </p>
{{-- ghostable:screenshot-placement {"id":"desktop-project-activity","provider":"ghostable-desktop-v3","shot_id":"project-activity","alt":"Ghostable Desktop signed project activity history","caption":"Activity records repository-backed actions with actor, signature, and time context."} --}}
{{-- ghostable:screenshot-output desktop-project-activity:start --}}
<img
    class="block dark:hidden w-full rounded-xl"
    src="{{ asset('images/generated/screenshots/ghostable-desktop-v3/project-activity-light.png') }}"
    alt="Ghostable Desktop signed project activity history"
/>

<img
    class="hidden dark:block w-full rounded-xl"
    src="{{ asset('images/generated/screenshots/ghostable-desktop-v3/project-activity-dark.png') }}"
    alt="Ghostable Desktop signed project activity history"
/>
<p class="mt-3 text-sm text-zinc-500">Activity records repository-backed actions with actor, signature, and time context.</p>
{{-- ghostable:screenshot-output desktop-project-activity:end --}}

    </x-docs.section>

    <x-docs.section id="modes" title="Activity modes">
        <x-docs.command-table :commands="[
            ['command' => 'off', 'description' => 'Do not record normal project activity events.'],
            ['command' => 'minimal', 'description' => 'Record a reduced set of important operations.'],
            ['command' => 'full', 'description' => 'Record the broadest supported activity detail for review and audit workflows.'],
        ]" />
        <p>
            Configure the mode and audited environments in Project Settings. Production and staging commonly deserve fuller coverage than disposable local environments, but the correct policy depends on repository size, review needs, and retention requirements.
        </p>
    </x-docs.section>

    <x-docs.section id="filters" title="Search and filters">
        <p>
            Search narrows events by displayed action and context. Filters can isolate action families or signature status, while pagination loads older repository events. Closing a filter does not clear or modify history.
        </p>
        <p>
            Use a specific key, environment, device name, or action when reconstructing a change. Pair the result with the Git commit that introduced the corresponding <code>.ghostable/</code> update.
        </p>
    </x-docs.section>

    <x-docs.section id="signatures" title="Signature status">
        <p>
            A valid signature shows that the event matches a trusted project device record and was not altered after signing. An invalid or unknown signature deserves investigation before relying on the event.
        </p>
        <x-docs.callout type="security" title="Signatures are not user surveillance">
            Device signatures establish repository-level authenticity. They do not prove who was physically using a machine, that the machine was uncompromised, or that an external provider applied a requested change.
        </x-docs.callout>
    </x-docs.section>

    <x-docs.section id="purge" title="Purge activity" :border="false">
        <p>
            The Activity toolbar can purge recorded events according to the current project controls. Purging changes repository state and reduces future visibility, so use it only under an explicit retention policy and review the resulting Git diff.
        </p>
        <p>
            Git history may still contain earlier activity files. Purge is project-state maintenance, not retroactive erasure from every clone or commit.
        </p>
    </x-docs.section>
</x-docs.page>
