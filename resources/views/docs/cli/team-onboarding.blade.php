<x-docs.page
    route-name="docs.cli.team-onboarding"
    title="Team Onboarding"
    section="Getting Started"
    description="Add a developer through a signed, repository-reviewed device request without sharing another person's private key or copying a long-lived plaintext env file."
    :on-this-page="[
        ['label' => 'Join from the new device', 'href' => '#join'],
        ['label' => 'Request access', 'href' => '#request'],
        ['label' => 'Approve the request', 'href' => '#approve'],
        ['label' => 'Verify access', 'href' => '#verify'],
        ['label' => 'Roles', 'href' => '#roles'],
        ['label' => 'Offboarding', 'href' => '#offboarding'],
    ]"
>
    <x-docs.section id="join" title="Join from the new device">
        <p>After cloning the repository, the new team member creates a device identity:</p>
        <x-docs.terminal title="New team member" :commands="['ghostable access join --name &quot;Alex MacBook&quot;']" />
        <p>
            This writes a self-signed public device record under <code>.ghostable/devices/</code>. The signing and encryption private keys stay in the platform secret store on that device.
        </p>
    </x-docs.section>

    <x-docs.section id="request" title="Request access">
        <p>The new device requests the least-privileged role it needs:</p>
        <x-docs.terminal
            title="Request development access"
            :commands="[
                'ghostable access requests create --env default --role writer --reason &quot;Joining application team&quot;',
                'git add .ghostable && git commit -m &quot;Request Ghostable access for Alex&quot;',
            ]"
        />
        <p>
            Open a pull request containing the device and request records. An owner or grantor can inspect the device label, public-key fingerprints, requested scope, and reason before approving it.
        </p>
    </x-docs.section>

    <x-docs.section id="approve" title="Approve the request">
        <p>From a checkout containing the request, an authorized device lists and approves it:</p>
        <x-docs.terminal
            title="Owner or grantor"
            :commands="[
                'ghostable access requests list',
                'ghostable access requests approve --request-id &lt;request-id&gt; --reason &quot;Approved for application development&quot;',
                'git add .ghostable && git commit -m &quot;Grant Alex Ghostable access&quot;',
            ]"
        />
        <p>
            Approval creates or updates signed policy and per-device environment grants. For a direct grant, an owner or grantor may use <code>ghostable access share --device-id &lt;device-id&gt; --env default --role writer</code> instead.
        </p>
    </x-docs.section>

    <x-docs.section id="verify" title="Verify access">
        <p>Once the approval commit is merged, the new team member pulls it and verifies access:</p>
        <x-docs.terminal title="Verify the grant" :commands="['git pull', 'ghostable access status', 'ghostable env pull --env default --file .env']" />
        <p>A production-like pull also requires the local operating system's user-presence confirmation.</p>
    </x-docs.section>

    <x-docs.section id="roles" title="Roles">
        <x-docs.command-table :commands="[
            ['command' => 'reader', 'description' => 'Decrypt and read values in the environment.'],
            ['command' => 'writer', 'description' => 'Includes reader access and may create, change, promote, or delete values.'],
            ['command' => 'grantor', 'description' => 'Includes reader access and may grant or review access for the environment; it does not imply write access.'],
            ['command' => 'owner', 'description' => 'Project-wide ownership with read, write, grant, and ownership authority.'],
        ]" />
        <x-docs.callout type="security" title="Grant the smallest useful role">
            Most application developers need <code>writer</code> in development environments and, at most, <code>reader</code> in production. Reserve <code>owner</code> for the small set of people responsible for project policy and recovery.
        </x-docs.callout>
    </x-docs.section>

    <x-docs.section id="offboarding" title="Offboarding" :border="false">
        <p>Revoke the device, commit the signed changes, then rotate affected environment keys:</p>
        <x-docs.terminal
            title="Remove a device"
            :commands="[
                'ghostable access revoke --device-id &lt;device-id&gt; --env all',
                'ghostable hygiene rotate --env production --reason &quot;Team member offboarded&quot;',
                'git add .ghostable && git commit -m &quot;Revoke device and rotate production key&quot;',
            ]"
        />
        <p>A revoked device record can be deleted later with <code>ghostable access delete</code>. Ghostable prevents the last owner device from leaving the project.</p>
    </x-docs.section>
</x-docs.page>
