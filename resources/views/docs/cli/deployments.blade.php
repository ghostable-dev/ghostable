<x-docs.page
    route-name="docs.cli.automation.deployments"
    title="Deployments"
    section="Automation & CI"
    description="Materialize an exact local env file or sync decrypted values to Laravel Forge, Vapor, or Cloud with dry-run planning and scoped automation access."
    :on-this-page="[
        ['label' => 'Deployment safety', 'href' => '#safety'],
        ['label' => 'Local env files', 'href' => '#local'],
        ['label' => 'Laravel Forge', 'href' => '#forge'],
        ['label' => 'Laravel Vapor', 'href' => '#vapor'],
        ['label' => 'Laravel Cloud', 'href' => '#cloud'],
        ['label' => 'Deployment scripts', 'href' => '#scripts'],
    ]"
>
    <x-docs.section id="safety" title="Deployment safety">
        <p>Always validate and dry-run the selected environment before enabling provider writes:</p>
        <x-docs.terminal title="Deployment preflight" :commands="['ghostable validate --env production --json', 'ghostable deploy production --dry-run --json']" />
        <p>
            Local device deployments from a production-like environment require OS user confirmation. Non-interactive deployment systems use a scoped <code>GHOSTABLE_CI_TOKEN</code> credential instead.
        </p>
        <x-docs.callout type="security" title="The provider receives plaintext">
            Ghostable decrypts locally, then passes plaintext to the destination. Provider credentials, provider storage, deployment logs, temporary files, and the runner are outside Ghostable's repository-encryption boundary.
        </x-docs.callout>
    </x-docs.section>

    <x-docs.section id="local" title="Local env files">
        <p>
            With no provider target, <code>deploy</code> writes the selected environment to <code>.env</code> and replaces the file by default so stale values cannot survive from a previous deployment.
        </p>
        <x-docs.terminal
            title="Local deployment"
            :commands="[
                'ghostable deploy production --dry-run',
                'ghostable deploy production',
                'ghostable deploy local production --file .env --backup',
                'ghostable deploy local staging --merge --only APP_KEY --only DATABASE_URL',
            ]"
        />
        <p>
            Use the explicit <code>local</code> target when <code>deployTarget</code> in the manifest points at a provider but a script still needs a file. Pass <code>--merge</code> only when preserving unrelated existing keys is intentional.
        </p>
    </x-docs.section>

    <x-docs.section id="forge" title="Laravel Forge">
        <p>Forge deployment requires the Laravel Forge CLI on <code>PATH</code> unless <code>--dry-run</code> is used:</p>
        <x-docs.terminal
            title="Deploy to Forge"
            :commands="[
                'ghostable deploy laravel-forge production --forge-site example.com --dry-run',
                'ghostable deploy laravel-forge production --forge-site example.com',
            ]"
        />
        <p>
            Ghostable pulls the site's current env file with <code>forge env:pull</code>, merges selected Ghostable values into a restrictive temporary file, then pushes it with <code>forge env:push</code>. Use <code>--only</code> to limit keys.
        </p>
    </x-docs.section>

    <x-docs.section id="vapor" title="Laravel Vapor">
        <p>Vapor deployment requires the Vapor CLI on <code>PATH</code> unless dry-running:</p>
        <x-docs.terminal
            title="Deploy to Vapor"
            :commands="[
                'ghostable deploy laravel-vapor production --dry-run',
                'ghostable deploy laravel-vapor production --vapor-env prod-us',
            ]"
        />
        <p>
            Ghostable merges values into Vapor's temporary environment file and invokes <code>vapor env:push</code>. The Vapor environment defaults to the Ghostable environment name.
        </p>
    </x-docs.section>

    <x-docs.section id="cloud" title="Laravel Cloud">
        <p>Cloud deployment requires the Laravel Cloud CLI on <code>PATH</code> unless dry-running:</p>
        <x-docs.terminal
            title="Deploy to Laravel Cloud"
            :commands="[
                'ghostable deploy laravel-cloud production --dry-run',
                'ghostable deploy laravel-cloud production --cloud-env production-us --only APP_KEY --only DATABASE_URL',
            ]"
        />
        <p>
            Ghostable calls <code>cloud environment:variables</code> with <code>--action=set</code> for each selected key. Matching variables are updated and missing variables are added. The Cloud environment defaults to the Ghostable environment name.
        </p>
    </x-docs.section>

    <x-docs.section id="scripts" title="Deployment scripts" :border="false">
        <p>Install dependencies, load the token from protected storage, deploy values, and only then run commands that consume the environment:</p>
        <x-docs.terminal
            title="Forge deployment script"
            :commands="[
                'npm ci',
                'export GHOSTABLE_CI_TOKEN=&quot;$(cat $HOME/.ghostable-ci-token)&quot;',
                'npx ghostable deploy laravel-forge production --forge-site example.com',
                'npm run build',
                '$FORGE_PHP artisan migrate --force',
            ]"
        />
        <p>Store token files outside the application directory with restrictive permissions, and never print the token or generated plaintext.</p>
    </x-docs.section>
</x-docs.page>
