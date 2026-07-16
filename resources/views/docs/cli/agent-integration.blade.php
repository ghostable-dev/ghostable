<x-docs.page
    route-name="docs.cli.reference.agents"
    title="Agent Integration"
    section="Reference"
    description="Give coding agents enough Ghostable context to inspect configuration safely while keeping mutation, plaintext output, access changes, and deployments under explicit human authority."
    :on-this-page="[
        ['label' => 'Install project guidance', 'href' => '#guidance'],
        ['label' => 'Safe default capabilities', 'href' => '#capabilities'],
        ['label' => 'Read-only investigation', 'href' => '#investigation'],
        ['label' => 'Mutating workflows', 'href' => '#mutations'],
        ['label' => 'Secret handling', 'href' => '#secrets'],
        ['label' => 'Adopting existing projects', 'href' => '#adoption'],
    ]"
>
    <x-docs.section id="guidance" title="Install project guidance">
        <p>Write Ghostable's maintained guidance into the repository's <code>AGENTS.md</code>:</p>
        <x-docs.terminal title="Agent instructions" :commands="['ghostable agent init --dry-run', 'ghostable agent init', 'ghostable agent instructions']" />
        <p>
            Setup can perform the same integration with <code>--agent-instructions</code>. Review generated guidance before committing it, especially when the repository already has agent rules.
        </p>
    </x-docs.section>

    <x-docs.section id="capabilities" title="Safe default capabilities">
        <p><code>agent capabilities</code> emits the recommended allowlist for default agent behavior:</p>
        <x-docs.terminal title="Capability inventory" :commands="['ghostable agent capabilities', 'ghostable agent capabilities --json']" />
        <p>The list favors status, lists, redacted diffs, history, validation, review, hygiene, and dry-run pull or deploy planning. It is not the complete CLI inventory.</p>
    </x-docs.section>

    <x-docs.section id="investigation" title="Read-only investigation">
        <x-docs.terminal
            title="Agent-safe investigation"
            :commands="[
                'ghostable status --json',
                'ghostable env list --json',
                'ghostable env diff --from staging --to production --json',
                'ghostable validate --env staging --json',
                'ghostable review --secrets-only --json',
                'ghostable access matrix --json',
            ]"
        />
        <p>Agents should prefer structured output, state assumptions explicitly, and avoid extrapolating plaintext values from metadata.</p>
    </x-docs.section>

    <x-docs.section id="mutations" title="Mutating workflows">
        <p>
            Pushing, syncing, promoting, deleting, rotating, approving access, revoking devices, pulling to a file, and deploying all change state or expand plaintext exposure. An agent should perform them only when the user explicitly requests that operation and should dry-run or diff first where available.
        </p>
        <x-docs.terminal
            title="Review before mutation"
            :commands="[
                'ghostable env diff --env staging --file .env.staging --json',
                'ghostable validate --env staging --json',
                'ghostable env push --env staging --file .env.staging --reason &quot;Requested configuration update&quot; --json',
            ]"
        />
    </x-docs.section>

    <x-docs.section id="secrets" title="Secret handling">
        <x-docs.callout type="security" title="Do not reveal values by default">
            Agents should not add <code>--show-values</code>, print env files, echo process variables, paste tokens into commands, or include plaintext in summaries. A user request to change a value is not automatically a request to display it.
        </x-docs.callout>
        <p>
            Prefer file-based input for non-interactive <code>var push</code>, redacted JSON diffs, <code>env run --mask-output</code>, and scoped automation credentials. Treat terminal output and tool logs as possible disclosure channels.
        </p>
    </x-docs.section>

    <x-docs.section id="adoption" title="Adopting existing projects" :border="false">
        <p>Generate a structured adoption prompt for an agent or developer:</p>
        <x-docs.terminal title="Adoption analysis" :commands="['ghostable adopt --sections schema,annotations,example,hygiene,drift,ci']" />
        <p>
            The generated prompt emphasizes evidence-backed, non-secret metadata and moves uncertain ownership, deployment, visibility, and rotation questions into an explicit open-questions list.
        </p>
    </x-docs.section>
</x-docs.page>
