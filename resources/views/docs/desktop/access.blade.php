<x-docs.page
    route-name="docs.desktop.workflows.access"
    title="Access & Automation"
    product-name="Ghostable Desktop"
    section="Workflows"
    description="Manage human device identities, environment-scoped permissions, signed requests, grants, revocation, and machine credentials."
    :on-this-page="[
        ['label' => 'Devices, not accounts', 'href' => '#devices'],
        ['label' => 'Permission model', 'href' => '#permissions'],
        ['label' => 'Requests and grants', 'href' => '#requests'],
        ['label' => 'Revoke and maintain access', 'href' => '#revoke'],
        ['label' => 'Agents and machines', 'href' => '#automation'],
        ['label' => 'Commit access changes', 'href' => '#commit'],
    ]"
>
    <x-docs.section id="devices" title="Devices, not accounts">
        <p>
            Human access is attached to a project-scoped cryptographic device identity, not a Ghostable cloud login. The Access page lists known devices, status, environment roles, pending requests, and grants carried by the repository.
        </p>
        <p>
            A license seat and a project device are different concepts. Licensing controls who may use the paid Desktop client; project access controls which encrypted environments a specific identity may read or change.
        </p>
{{-- ghostable:screenshot-placement {"id":"desktop-project-access","provider":"ghostable-desktop-v3","shot_id":"project-access","alt":"Ghostable Desktop project access page with human devices and agent credentials","caption":"Human device grants and scoped Agents / Machines credentials are managed separately."} --}}
{{-- ghostable:screenshot-output desktop-project-access:start --}}
<img
    class="block dark:hidden w-full rounded-xl"
    src="{{ asset('images/generated/screenshots/ghostable-desktop-v3/project-access-light.png') }}"
    alt="Ghostable Desktop project access page with human devices and agent credentials"
/>

<img
    class="hidden dark:block w-full rounded-xl"
    src="{{ asset('images/generated/screenshots/ghostable-desktop-v3/project-access-dark.png') }}"
    alt="Ghostable Desktop project access page with human devices and agent credentials"
/>
<p class="mt-3 text-sm text-zinc-500">Human device grants and scoped Agents / Machines credentials are managed separately.</p>
{{-- ghostable:screenshot-output desktop-project-access:end --}}

    </x-docs.section>

    <x-docs.section id="permissions" title="Permission model">
        <x-docs.command-table :commands="[
            ['command' => 'reader', 'description' => 'Read and decrypt values for the granted environment.'],
            ['command' => 'writer', 'description' => 'Reader permissions plus environment value changes.'],
            ['command' => 'grantor', 'description' => 'Reader permissions plus access-grant authority for the environment.'],
            ['command' => 'owner', 'description' => 'Read, write, grant, and owner authority across the project.'],
        ]" />
        <p>
            Grantor and writer are intentionally separate. Give each device the smallest useful role and scope production separately from development.
        </p>
    </x-docs.section>

    <x-docs.section id="requests" title="Requests and grants">
        <p>
            A new device can create a signed request for an environment and role. An authorized device reviews the request details, requested scope, device identity, and reason before approving or denying it.
        </p>
        <p>
            Direct grants are appropriate when an approver has already verified the target identity through a trusted channel. Never grant access based only on a copied device name; verify the full device identifier or fingerprint when the risk warrants it.
        </p>
    </x-docs.section>

    <x-docs.section id="revoke" title="Revoke and maintain access">
        <p>
            Revocation removes a device's environment access in new repository state. Access maintenance also exposes operations for a device to leave a project and for administrators to remove public records that are already revoked.
        </p>
        <x-docs.callout type="warning" title="Revocation cannot erase prior knowledge">
            A device that previously decrypted a value may retain it in memory, a local file, logs, or backups. Rotate sensitive credentials after revoking a lost, compromised, or departing device.
        </x-docs.callout>
        <p>The last owner cannot leave, preserving a path to project administration.</p>
    </x-docs.section>

    <x-docs.section id="automation" title="Agents and machines">
        <p>
            The <strong>Agents / Machines</strong> area creates scoped automation credentials for CI, deploy systems, and other non-human runtimes. Choose the narrowest environment, role, and automation kind that can complete the job.
        </p>
        <p>
            The returned credential is a secret. Capture it once, store it in the target platform's protected secret store, and pass it as <code>GHOSTABLE_CI_TOKEN</code>. Do not commit it, paste it into tickets, or reuse a human device identity in CI.
        </p>
        <p>
            See <a href="{{ route('docs.cli.workflows.deploy-tokens') }}">Automation Credentials</a>, <a href="{{ route('docs.cli.automation.continuous-integration') }}">Continuous Integration</a>, and <a href="{{ route('docs.cli.automation.deployments') }}">Deployments</a> for engine-level examples.
        </p>
    </x-docs.section>

    <x-docs.section id="commit" title="Commit access changes" :border="false">
        <p>
            Requests, approvals, grants, revocations, and public device records are repository changes. Inspect the Access page and Git diff, then commit and distribute the resulting <code>.ghostable/</code> state through the normal review path. Other clones do not see the new policy until they receive that commit.
        </p>
    </x-docs.section>
</x-docs.page>
