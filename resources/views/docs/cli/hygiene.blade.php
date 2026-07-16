<x-docs.page
    route-name="docs.cli.workflows.hygiene"
    title="Hygiene & Rotation"
    section="Workflows"
    description="Track secrets that need attention, encode rotation expectations, suppress reviewed exceptions, and rotate environment encryption keys after access changes."
    :on-this-page="[
        ['label' => 'Run a hygiene report', 'href' => '#report'],
        ['label' => 'Variable rotation rules', 'href' => '#rules'],
        ['label' => 'Unused and stale checks', 'href' => '#optional-checks'],
        ['label' => 'Suppress findings', 'href' => '#suppress'],
        ['label' => 'Rotate environment keys', 'href' => '#key-rotation'],
        ['label' => 'Automation output', 'href' => '#automation'],
    ]"
>
    <x-docs.section id="report" title="Run a hygiene report">
        <x-docs.terminal
            title="Hygiene reports"
            :commands="[
                'ghostable hygiene report --env production',
                'ghostable hygiene report --env staging --env production',
            ]"
        />
        <p>
            The report checks configured variable rotation rules and environment-key age. Variable age is not treated as a problem by default because stable configuration such as <code>APP_DEBUG=false</code> does not require secret-style rotation.
        </p>
    </x-docs.section>

    <x-docs.section id="rules" title="Variable rotation rules">
        <p>Set a project default, then override it for a stricter environment:</p>
        <x-docs.terminal
            title="Rotation policy"
            :commands="[
                'ghostable hygiene rotation set --key STRIPE_SECRET_KEY --days 90',
                'ghostable hygiene rotation set --env production --key STRIPE_SECRET_KEY --days 60',
                'ghostable hygiene rotation list',
            ]"
        />
        <p>Rules are stored in <code>.ghostable/hygiene.yaml</code> and use whole-day intervals. Remove one with <code>hygiene rotation remove</code>.</p>
    </x-docs.section>

    <x-docs.section id="optional-checks" title="Unused and stale checks">
        <x-docs.terminal
            title="Opt-in checks"
            :commands="[
                'ghostable hygiene report --env production --unused',
                'ghostable hygiene report --env production --stale-after 90d',
            ]"
        />
        <p>
            Both checks are opt-in. Framework conventions, external platforms, reflection, and deploy scripts can consume environment keys without a direct source-code reference, so unused findings require human review.
        </p>
    </x-docs.section>

    <x-docs.section id="suppress" title="Suppress findings">
        <p>Create a signed exception with the narrowest useful scope and an expiration:</p>
        <x-docs.terminal
            title="Hygiene suppression"
            :commands="[
                'ghostable hygiene suppress --code unused_variable --env production --key LEGACY_CALLBACK_TOKEN --reason &quot;Read by external worker&quot; --expires-in 30d',
            ]"
        />
        <p>Use <code>--include-suppressed</code> when auditing all active findings and exceptions together.</p>
    </x-docs.section>

    <x-docs.section id="key-rotation" title="Rotate environment keys">
        <p>
            Environment-key rotation is different from rotating an application credential. It re-encrypts the environment's key material and refreshes access for currently authorized identities.
        </p>
        <x-docs.terminal
            title="Environment-key rotation"
            :commands="[
                'ghostable hygiene rotate --env production --dry-run',
                'ghostable hygiene rotate --env production --reason &quot;Device revoked after offboarding&quot;',
            ]"
        />
        <x-docs.callout type="security" title="Rotate after compromise or revocation">
            Revocation prevents future authorized use of current repository state. Rotate affected environment keys promptly after a lost device, exposed automation token, or offboarding event so newly committed state no longer uses stale grants.
        </x-docs.callout>
    </x-docs.section>

    <x-docs.section id="automation" title="Automation output" :border="false">
        <x-docs.terminal title="JSON and SARIF" :commands="['ghostable hygiene report --env production --json', 'ghostable hygiene report --env production --sarif']" />
        <p>Use JSON for custom policy checks and SARIF for security tooling that accepts standardized findings.</p>
    </x-docs.section>
</x-docs.page>
