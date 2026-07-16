<x-docs.page
    route-name="docs.cli.reference.validation"
    title="Validation & Schemas"
    section="Reference"
    description="Declare project-wide and environment-specific rules, validate encrypted or local values, and keep configuration contracts reviewable in Git."
    :on-this-page="[
        ['label' => 'Schema files', 'href' => '#files'],
        ['label' => 'Schema format', 'href' => '#format'],
        ['label' => 'Supported rules', 'href' => '#rules'],
        ['label' => 'Environment overrides', 'href' => '#environment-rules'],
        ['label' => 'Manage schemas', 'href' => '#manage'],
        ['label' => 'Run validation', 'href' => '#run'],
    ]"
>
    <x-docs.section id="files" title="Schema files">
        <p>
            Global rules live in <code>.ghostable/schema.yaml</code>. Rules for one environment live in <code>.ghostable/schemas/&lt;environment&gt;.yaml</code>. Both are plaintext, repository-visible contracts and must not include secret values.
        </p>
    </x-docs.section>

    <x-docs.section id="format" title="Schema format">
        <pre class="overflow-x-auto rounded-xl border border-gray-200 bg-gray-50 p-5 font-mono text-sm leading-7 text-gray-700 dark:border-white/10 dark:bg-white/[0.04] dark:text-gray-300"><code>APP_ENV:
  - required
  - in:local,staging,production

APP_URL:
  - required
  - url
  - starts_with:https://

QUEUE_CONNECTION:
  - nullable
  - string

SESSION_LIFETIME:
  - required
  - integer
  - min:1
  - max:1440</code></pre>
        <p>Each top-level key maps to an ordered list of rules. Rules with arguments use <code>name:argument</code>.</p>
    </x-docs.section>

    <x-docs.section id="rules" title="Supported rules">
        <x-docs.command-table :commands="[
            ['command' => 'required', 'description' => 'The key must exist and have a non-empty value.'],
            ['command' => 'nullable', 'description' => 'Other rules are skipped when the value is missing or empty.'],
            ['command' => 'string', 'description' => 'Accepts the env value as a string.'],
            ['command' => 'integer / numeric', 'description' => 'Requires an integer or floating-point-compatible numeric value.'],
            ['command' => 'boolean', 'description' => 'Accepts true, false, 1, 0, yes, or no, case-insensitively.'],
            ['command' => 'email / url', 'description' => 'Requires a valid email address or absolute URL.'],
            ['command' => 'starts_with / ends_with', 'description' => 'Requires the declared prefix or suffix.'],
            ['command' => 'regex', 'description' => 'Requires a match against the supplied regular expression.'],
            ['command' => 'in', 'description' => 'Requires one of a comma-separated set of exact values.'],
            ['command' => 'min / max', 'description' => 'Compares numeric values numerically and other values by character length.'],
            ['command' => 'different_from', 'description' => 'Requires this key to differ from the same key in another environment.'],
        ]" />
    </x-docs.section>

    <x-docs.section id="environment-rules" title="Environment-specific rules">
        <p>
            Environment rules are appended to global rules for the same key. This makes it possible to keep a shared contract while adding production requirements:
        </p>
        <pre class="overflow-x-auto rounded-xl border border-gray-200 bg-gray-50 p-5 font-mono text-sm leading-7 text-gray-700 dark:border-white/10 dark:bg-white/[0.04] dark:text-gray-300"><code># .ghostable/schemas/production.yaml
APP_KEY:
  - required
  - different_from:staging

APP_DEBUG:
  - required
  - in:false,0</code></pre>
    </x-docs.section>

    <x-docs.section id="manage" title="Manage schemas">
        <x-docs.terminal
            title="Schema commands"
            :commands="[
                'ghostable schema rule add --key APP_URL --rule required',
                'ghostable schema rule add --key APP_URL --rule url',
                'ghostable schema rule update --key APP_URL --old-rule url --new-rule starts_with:https://',
                'ghostable schema rule remove --key APP_URL --rule starts_with:https://',
                'ghostable schema key rename --old-key OLD_API_URL --new-key API_URL',
            ]"
        />
        <p>Pass <code>--file .ghostable/schemas/production.yaml</code> to target an environment-specific schema.</p>
    </x-docs.section>

    <x-docs.section id="run" title="Run validation" :border="false">
        <x-docs.terminal
            title="Validate values"
            :commands="[
                'ghostable validate --env production',
                'ghostable validate --env staging --file .env.staging',
                'ghostable validate --env production --json',
            ]"
        />
        <p>
            Without <code>--file</code>, validation reads stored encrypted values. With <code>--file</code>, it validates that local file against the selected environment's merged rules. A warning is emitted when no schema rules exist.
        </p>
    </x-docs.section>
</x-docs.page>
