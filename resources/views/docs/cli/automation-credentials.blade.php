<x-docs.page
    route-name="docs.cli.workflows.deploy-tokens"
    title="Automation Credentials"
    section="Automation & CI"
    description="Give CI and deployment systems non-interactive, environment-scoped access without copying a human device identity into automation."
    :on-this-page="[
        ['label' => 'Create a credential', 'href' => '#create'],
        ['label' => 'Store the token', 'href' => '#store'],
        ['label' => 'Use the credential', 'href' => '#use'],
        ['label' => 'Scope and permissions', 'href' => '#scope'],
        ['label' => 'Revoke and replace', 'href' => '#revoke'],
    ]"
>
    <x-docs.section id="create" title="Create a credential">
        <p>Create the credential from an owner device and grant only the environments and roles the job needs:</p>
        <x-docs.terminal
            title="Create CI and deploy credentials"
            :commands="[
                'ghostable access create --name github-actions --kind ci --grant staging:reader',
                'ghostable access create --name production-deploy --kind deploy --grant production:reader',
            ]"
        />
        <p>
            Kinds are <code>ci</code>, <code>deploy</code>, and <code>access</code>. They label intended use; effective access comes from the environment grants. Automation grants accept only <code>reader</code> or <code>writer</code>.
        </p>
    </x-docs.section>

    <x-docs.section id="store" title="Store the token">
        <p>
            The command returns a credential token and writes its public device and grant records under <code>.ghostable/</code>. Store the token immediately in the CI or deployment platform's encrypted secret store as <code>GHOSTABLE_CI_TOKEN</code>, then commit the repository changes.
        </p>
        <x-docs.callout type="security" title="The token is a secret">
            Never commit the token, place it in a deployment script, echo it in logs, or pass it as a CLI argument. Anyone holding it can exercise the grants encoded for that automation identity.
        </x-docs.callout>
    </x-docs.section>

    <x-docs.section id="use" title="Use the credential">
        <p>Ghostable automatically loads the token from the environment:</p>
        <x-docs.terminal
            title="Non-interactive access"
            :commands="[
                'export GHOSTABLE_CI_TOKEN=&quot;&lt;secret supplied by platform&gt;&quot;',
                'ghostable validate --env staging --json',
                'ghostable deploy production --dry-run --json',
            ]"
        />
        <p>
            Automation credentials bypass local biometric or user-presence prompts because no user session exists. Their security therefore depends on token storage, runner isolation, and narrow grants.
        </p>
    </x-docs.section>

    <x-docs.section id="scope" title="Scope and permissions">
        <ul>
            <li>Use <code>reader</code> for validation, tests, process injection, and deployments that only read Ghostable state.</li>
            <li>Use <code>writer</code> only when the job must commit new encrypted values or metadata.</li>
            <li>Create separate credentials for CI and production deployment so either one can be revoked independently.</li>
            <li>Do not grant production to preview or pull-request jobs.</li>
        </ul>
    </x-docs.section>

    <x-docs.section id="revoke" title="Revoke and replace" :border="false">
        <p>Automation credentials appear as devices in access views. Revoke the credential's device ID and commit the signed policy change:</p>
        <x-docs.terminal title="Revoke automation" :commands="['ghostable access list --full', 'ghostable access revoke --device-id &lt;credential-device-id&gt; --env all']" />
        <p>Delete the old platform secret, create a replacement credential when needed, and rotate environment keys after a suspected compromise.</p>
    </x-docs.section>
</x-docs.page>
