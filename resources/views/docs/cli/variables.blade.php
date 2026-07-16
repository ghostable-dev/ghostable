<x-docs.page
    route-name="docs.cli.workflows.variable-promotions"
    title="Variables & Promotions"
    section="Core Concepts"
    description="Change individual values without exposing them in shell history, promote configuration deliberately, and attach the right kind of context to each key."
    :on-this-page="[
        ['label' => 'Write one variable', 'href' => '#push'],
        ['label' => 'Read one variable', 'href' => '#pull'],
        ['label' => 'Promote between environments', 'href' => '#promote'],
        ['label' => 'Delete and history', 'href' => '#delete-history'],
        ['label' => 'Encrypted context', 'href' => '#context'],
        ['label' => 'Annotations and status', 'href' => '#metadata'],
    ]"
>
    <x-docs.section id="push" title="Write one variable">
        <p>
            In an interactive terminal, <code>var push</code> can prompt securely for a value. In scripts or non-interactive sessions, Ghostable requires <code>--file</code> so the value does not appear in shell history.
        </p>
        <x-docs.terminal title="Update one variable" :commands="['ghostable var push --env staging --key STRIPE_SECRET_KEY --file .env.staging --reason &quot;Rotate sandbox credential&quot;']" />
        <p>The file is parsed locally and only the selected key is encrypted and written.</p>
    </x-docs.section>

    <x-docs.section id="pull" title="Read one variable">
        <p>Write a selected variable to an env file without printing it:</p>
        <x-docs.terminal title="Pull one key" :commands="['ghostable var pull --env default --key APP_KEY --file .env']" />
        <p>
            Passing <code>--show-values</code> prints the plaintext value to stdout. Production-like environments require user-presence confirmation before either path can expose the value.
        </p>
    </x-docs.section>

    <x-docs.section id="promote" title="Promote between environments">
        <p>Promotion is explicit about source, destination, key, mode, and reason:</p>
        <x-docs.terminal
            title="Promote configuration"
            :commands="[
                'ghostable var promote --from staging --to production --key FEATURE_API_URL --reason &quot;Release new API endpoint&quot;',
                'ghostable var promote --from staging --to production --key STRIPE_SECRET_KEY --mode key-only --reason &quot;Reserve production key layout&quot;',
            ]"
        />
        <p>
            The default <code>value</code> mode copies the value and variable flags. <code>key-only</code> adds the key to the destination layout without copying a secret across environment boundaries.
        </p>
    </x-docs.section>

    <x-docs.section id="delete-history" title="Delete and history">
        <x-docs.terminal
            title="Variable lifecycle"
            :commands="[
                'ghostable var history --env production --key LEGACY_API_TOKEN',
                'ghostable var delete --env production --key LEGACY_API_TOKEN --reason &quot;Integration retired&quot;',
            ]"
        />
        <p>Deletion is signed and reviewable. Use <code>--assume-yes</code> only in a script that has already confirmed its target.</p>
    </x-docs.section>

    <x-docs.section id="context" title="Encrypted context">
        <p>
            Variable context is an encrypted note for information that should travel with a key but should not be visible as repository metadata.
        </p>
        <x-docs.terminal title="Encrypted note" :commands="['ghostable var context --env production --key STRIPE_SECRET_KEY --note &quot;Stored in the platform security vault; contact the payments owner for rotation.&quot;']" />
    </x-docs.section>

    <x-docs.section id="metadata" title="Annotations and status" :border="false">
        <p>Annotations are signed, plaintext, typed metadata. Supported values are string, number, and boolean:</p>
        <x-docs.terminal
            title="Key annotations"
            :commands="[
                'ghostable var annotation set --env production --key APP_KEY --name owner --string platform',
                'ghostable var annotation set --env production --key APP_KEY --name rotation_days --number 90',
                'ghostable var annotation set --env production --key APP_KEY --name deploy.managed --bool true',
                'ghostable var annotation list --env production --key APP_KEY',
            ]"
        />
        <p>
            Annotations must never contain secrets. Advanced status commands <code>var enable</code>, <code>var disable</code>, and <code>var status</code> control whether a stored key is emitted as active or commented in generated env files.
        </p>
    </x-docs.section>
</x-docs.page>
