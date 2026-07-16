<x-docs.page
    route-name="docs.desktop.reference.security"
    title="Security & Storage"
    product-name="Ghostable Desktop"
    section="Reference"
    description="Understand Desktop's trust boundaries, where local metadata and credentials live, which network requests occur, and how to handle plaintext safely."
    :on-this-page="[
        ['label' => 'Security boundary', 'href' => '#boundary'],
        ['label' => 'Repository state', 'href' => '#repository'],
        ['label' => 'Private identities', 'href' => '#identities'],
        ['label' => 'Desktop-local data', 'href' => '#desktop-data'],
        ['label' => 'Network behavior', 'href' => '#network'],
        ['label' => 'Plaintext exposure', 'href' => '#plaintext'],
        ['label' => 'Electron hardening', 'href' => '#electron'],
        ['label' => 'Report a security issue', 'href' => '#report'],
    ]"
>
    <x-docs.section id="boundary" title="Security boundary">
        <p>
            Ghostable Desktop is a local interface to the CLI engine. Project values are encrypted before repository storage and decrypted only on a device with the required identity and environment access. Ghostable does not operate a hosted service that receives those plaintext project secrets.
        </p>
        <p>
            That statement does not make every local workflow zero-risk. The operating system, clipboard, editor, generated files, terminal processes, CI runners, deployment providers, backups, and anyone controlling an authorized device remain separate trust boundaries.
        </p>
        <p>
            Read the <a href="{{ route('docs.cli.reference.security') }}">CLI Security reference</a> for cryptographic primitives, threat-model scope, audit status, and repository-level recovery constraints.
        </p>
    </x-docs.section>

    <x-docs.section id="repository" title="Repository state">
        <p>
            The <code>.ghostable/</code> directory contains the project manifest, encrypted environment values, public device records, wrapped environment keys, signed policy and activity, validation schema, and other engine state. It is designed to be committed and reviewed.
        </p>
        <p>
            Encrypted does not mean disposable. Back up repository history and preserve at least one authorized owner identity. Encrypted values cannot be recovered from Git alone when every valid private identity and usable key envelope is lost.
        </p>
    </x-docs.section>

    <x-docs.section id="identities" title="Private identities">
        <p>
            Project device private keys are stored outside the repository using the CLI's platform identity store. On macOS this normally uses Keychain; development or explicitly configured environments can use a protected file-backed keystore.
        </p>
        <p>
            A project identity is different from the Desktop activation token. Backing up or releasing one does not manage the other.
        </p>
    </x-docs.section>

    <x-docs.section id="desktop-data" title="Desktop-local data">
        <ul>
            <li>Launcher groups, project names, and local repository paths are stored as local application organization state.</li>
            <li>General and appearance preferences are stored in the Desktop application data directory.</li>
            <li>The protected license activation token is stored in macOS Keychain; signed entitlement metadata and validation status are cached in application data.</li>
            <li>Project plaintext is not intentionally copied into launcher metadata or license state.</li>
        </ul>
        <p>
            macOS backups may include some application metadata. Apply the same device encryption and backup controls you use for source repositories and developer credentials.
        </p>
    </x-docs.section>

    <x-docs.section id="network" title="Network behavior">
        <p>
            Core project management is local and repository-backed. Desktop makes network requests for license activation and validation, activation release, and update checks or downloads. Links such as purchase, documentation, privacy, or support open the configured Ghostable website in the system browser.
        </p>
        <p>
            Licensing requests do not need project environment values or repository contents. Deployment operations are separate: when you intentionally deploy, the destination provider or process receives the plaintext values necessary to run the application.
        </p>
    </x-docs.section>

    <x-docs.section id="plaintext" title="Plaintext exposure">
        <ul>
            <li>Revealing or editing a value exposes it to the current renderer, display, and operating-system process memory.</li>
            <li>Copying a value exposes it to the clipboard and any clipboard-history utility.</li>
            <li>Pulling a local file writes plaintext to disk.</li>
            <li>Opening a file or repository in an IDE extends trust to that editor and its plugins.</li>
            <li>Running or deploying with an environment extends trust to the child process, CI runner, and provider.</li>
        </ul>
        <p>
            Use full-disk encryption, a locked screen, trusted editor extensions, ignored local files, minimal project roles, scoped automation credentials, and credential rotation after suspected exposure.
        </p>
    </x-docs.section>

    <x-docs.section id="electron" title="Electron hardening">
        <p>
            Desktop project and settings windows run with renderer sandboxing, context isolation, and Node integration disabled. A narrow preload API exposes approved operations, and the main process validates window roles, project scope, command allowlists, and file paths before invoking the CLI.
        </p>
        <p>
            These controls reduce renderer-to-system risk but do not replace operating-system patching, signed updates, dependency review, or careful handling of untrusted repositories.
        </p>
    </x-docs.section>

    <x-docs.section id="report" title="Report a security issue" :border="false">
        <p>
            Use the <a href="{{ route('security.report') }}">security report form</a> or email <a href="mailto:security@ghostable.dev">security@ghostable.dev</a>. Include Desktop and bundled CLI versions, macOS version, impact, and reproduction steps. Redact license keys, project secrets, private identities, and automation tokens.
        </p>
    </x-docs.section>
</x-docs.page>
