<x-docs.page
    route-name="docs.cli.workflows.devices"
    title="Access & Devices"
    section="Core Concepts"
    description="Manage human device identities, environment-scoped roles, signed access requests, and the repository changes required to grant or revoke access."
    :on-this-page="[
        ['label' => 'Device identities', 'href' => '#identities'],
        ['label' => 'Inspect access', 'href' => '#inspect'],
        ['label' => 'Requests and direct grants', 'href' => '#grant'],
        ['label' => 'Permission model', 'href' => '#permissions'],
        ['label' => 'Revoke, leave, and delete', 'href' => '#remove'],
        ['label' => 'Local cleanup', 'href' => '#cleanup'],
    ]"
>
    <x-docs.section id="identities" title="Device identities">
        <p>
            A device is a project-scoped cryptographic identity, not a user account. Joining creates a signing key, encryption key, self-signed public record, and local private identity. Private material is never committed.
        </p>
        <x-docs.terminal title="Join and inspect" :commands="['ghostable access join --name &quot;Sam Workstation&quot;', 'ghostable access status']" />
        <p><code>device</code> is an alias for human-device operations; the broader <code>access</code> command also manages requests and automation credentials.</p>
    </x-docs.section>

    <x-docs.section id="inspect" title="Inspect access">
        <x-docs.terminal
            title="Access views"
            :commands="[
                'ghostable access list',
                'ghostable access approvers --env production',
                'ghostable access grants --env production',
                'ghostable access matrix',
            ]"
        />
        <p>
            The matrix is the quickest way to review effective roles by device and environment. Add <code>--full</code> only when complete device IDs are needed for an operation.
        </p>
    </x-docs.section>

    <x-docs.section id="grant" title="Requests and direct grants">
        <p>A joining device can create a signed request that another authorized device reviews:</p>
        <x-docs.terminal
            title="Request workflow"
            :commands="[
                'ghostable access requests create --env staging --role writer --reason &quot;Joining release rotation&quot;',
                'ghostable access requests list',
                'ghostable access requests approve --request-id &lt;request-id&gt; --reason &quot;Approved by release owner&quot;',
            ]"
        />
        <p>For a direct grant, use <code>access share</code> with the target device ID, environment or <code>all</code>, and role.</p>
    </x-docs.section>

    <x-docs.section id="permissions" title="Permission model">
        <x-docs.command-table :commands="[
            ['command' => 'reader', 'description' => 'Read and decrypt environment values.'],
            ['command' => 'writer', 'description' => 'Reader permissions plus environment value changes.'],
            ['command' => 'grantor', 'description' => 'Reader permissions plus access-grant authority for the environment.'],
            ['command' => 'owner', 'description' => 'Read, write, grant, and owner authority across the project.'],
        ]" />
        <p>Grantor and writer are separate roles. A person who approves access does not automatically have permission to change values.</p>
    </x-docs.section>

    <x-docs.section id="remove" title="Revoke, leave, and delete">
        <x-docs.terminal
            title="Remove access"
            :commands="[
                'ghostable access revoke --device-id &lt;device-id&gt; --env all',
                'ghostable access leave',
                'ghostable access delete --device-id &lt;revoked-device-id&gt;',
            ]"
        />
        <p>
            Revoke permanently marks the target identity as revoked, removes the selected grants, and automatically rotates keys for the affected environments. The <code>--env</code> option selects which grants and keys change; it does not make the identity reusable elsewhere. Use <code>--env all</code> for offboarding or compromise. A revoked device must join again with a new identity before it can receive access.
        </p>
        <p>
            Leave removes the current machine's local project access. Delete removes an already-revoked public device record. Neither environment-key rotation nor record deletion erases secrets already seen by that device or rotates the credentials those values represent. The last owner cannot leave or be revoked, preserving a path to project administration.
        </p>
    </x-docs.section>

    <x-docs.section id="cleanup" title="Local cleanup" :border="false">
        <p>Find identities whose registered projects no longer exist, then remove them after review:</p>
        <x-docs.terminal title="Clean local identities" :commands="['ghostable access cleanup --dry-run', 'ghostable access cleanup']" />
    </x-docs.section>
</x-docs.page>
