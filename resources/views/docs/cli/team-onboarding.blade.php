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
        <p>
            The simplest review uses one pull request: the requester opens it, an authorized approver checks out or fetches that branch, adds the signed approval commit, and pushes it back to the same pull request. If branch permissions prevent that push, place the approval commit in a follow-up pull request and merge both before the requester attempts to decrypt values. Do not treat a request-only commit as completed access.
        </p>
        <x-docs.callout type="info" title="Git review and Ghostable authority are separate">
            Ghostable verifies that the approving device is an owner or an environment grantor. It does not require that requester and approver are different people; use branch protection or repository review rules when a separate human approval is required.
        </x-docs.callout>
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
        <p>For offboarding or a lost device, revoke the identity from every environment and commit all signed changes:</p>
        <x-docs.terminal
            title="Remove a device"
            :commands="[
                'ghostable access revoke --device-id &lt;device-id&gt; --env all',
                'git add .ghostable && git commit -m &quot;Revoke retired Ghostable device&quot;',
            ]"
        />
        <p>
            Revocation permanently marks that device identity as revoked and automatically rotates each affected environment key. The device cannot be granted access again; it must rejoin with a new identity. Although <code>--env</code> can name one environment, it controls which grants and environment keys are changed—not whether the identity is globally revoked—so use <code>all</code> for offboarding and compromise response.
        </p>
        <p>
            Rotation blocks the revoked identity from decrypting future Ghostable values. It cannot erase plaintext the device already read or secrets recoverable from older Git history. If compromise is possible, also rotate the underlying database password, API key, or provider credential at its issuer. A revoked public device record can be deleted later with <code>ghostable access delete</code>. Ghostable prevents revoking or removing the last owner.
        </p>
    </x-docs.section>
</x-docs.page>
