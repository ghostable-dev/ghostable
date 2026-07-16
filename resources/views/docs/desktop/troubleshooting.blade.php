<x-docs.page
    route-name="docs.desktop.reference.troubleshooting"
    title="Troubleshooting"
    product-name="Ghostable Desktop"
    section="Reference"
    description="Diagnose installation, licensing, project access, bundled CLI, local-file, validation, and update problems without risking project state."
    :on-this-page="[
        ['label' => 'Collect diagnostics first', 'href' => '#diagnostics'],
        ['label' => 'The app will not open', 'href' => '#app-open'],
        ['label' => 'License problems', 'href' => '#license'],
        ['label' => 'A project will not open', 'href' => '#project'],
        ['label' => 'CLI or command failures', 'href' => '#cli'],
        ['label' => 'Local-file problems', 'href' => '#files'],
        ['label' => 'Validation or Review surprises', 'href' => '#review'],
        ['label' => 'Updates', 'href' => '#updates'],
        ['label' => 'Contact support', 'href' => '#support'],
    ]"
>
    <x-docs.section id="diagnostics" title="Collect diagnostics first">
        <ul>
            <li>Open <strong>Application Settings → Info</strong> and record Desktop, build, and bundled CLI versions.</li>
            <li>Record the macOS version and whether the problem affects every project or one repository.</li>
            <li>Copy the exact error text, but redact secrets, license keys, activation tokens, private identities, and local usernames in paths.</li>
            <li>Run <code>git status --short</code> before retrying a write operation.</li>
        </ul>
    </x-docs.section>

    <x-docs.section id="app-open" title="The app will not open">
        <ul>
            <li>Confirm macOS 13 or newer and download a fresh DMG from the <a href="{{ route('download') }}">official download page</a>.</li>
            <li>Move Ghostable to Applications before launching it.</li>
            <li>If Gatekeeper rejects the signature or notarization, do not bypass the warning; replace the download and contact support with the exact message.</li>
            <li>If a window is off-screen or unresponsive, quit Ghostable normally, reopen it, and test the launcher before opening a project.</li>
        </ul>
    </x-docs.section>

    <x-docs.section id="license" title="License problems">
        <h3>Activation limit reached</h3>
        <p>Release an old device from its License settings or use <a href="{{ route('licenses.manage') }}">Manage licenses</a>. Seats and activations are distinct from project devices.</p>
        <h3>License is unverified or offline time expired</h3>
        <p>Connect to the internet, check system date and time, then choose <strong>Validate Now</strong>. A perpetual license can still need a fresh signed offline entitlement.</p>
        <h3>License key missing</h3>
        <p>Request recovery using the purchase email. Do not create a second purchase merely to work around a recoverable key.</p>
        <h3>Project remains blocked after activation</h3>
        <p>Close and reopen the project window after License settings reports a valid entitlement. If the plan is valid but the project still fails, collect both version numbers and the exact message.</p>
    </x-docs.section>

    <x-docs.section id="project" title="A project will not open">
        <ul>
            <li>Confirm the launcher path still exists and points to the repository root.</li>
            <li>Use the <strong>Setup</strong> badge as a hint, then verify <code>.ghostable/ghostable.yaml</code> exists in the selected folder.</li>
            <li>For a new clone or machine, complete the device join and access-grant workflow; a repository clone alone cannot decrypt values.</li>
            <li>Pull the latest project state and resolve Git conflicts before retrying.</li>
            <li>When moving a repository, update its launcher or Project Settings path instead of editing application metadata manually.</li>
        </ul>
    </x-docs.section>

    <x-docs.section id="cli" title="CLI or command failures">
        <p>
            Info should show a bundled CLI version. If it reports unavailable, reinstall the current Desktop build. Desktop uses its bundled engine for project commands; a different global <code>ghostable</code> binary does not repair a missing app bundle.
        </p>
        <p>
            Command errors preserve their CLI message. Check repository permissions, project identity access, the selected environment, Git conflict markers, and whether an input file is inside the project root.
        </p>
        <p>
            Reproduce read-only behavior with the matching CLI documentation when useful, but do not run an unfamiliar write command against production state merely to gather more output.
        </p>
    </x-docs.section>

    <x-docs.section id="files" title="Local-file problems">
        <ul>
            <li>If pull cannot write, check folder permissions and whether another process holds the file.</li>
            <li>If push shows unexpected changes, stop and compare the local file with the selected shared environment.</li>
            <li>If cleanup lists an important file, leave dry-run enabled and adjust the operation rather than confirming.</li>
            <li>If <code>.env.example</code> loses comments, restore it from Git and regenerate only after reviewing replace mode.</li>
        </ul>
    </x-docs.section>

    <x-docs.section id="review" title="Validation or Review surprises">
        <ul>
            <li>Confirm the environment selected in the sidebar matches the diagnostic.</li>
            <li>Inspect global rules and environment overrides for the key.</li>
            <li>Review the project's scan level and ignored paths.</li>
            <li>Treat a hard-coded secret finding as potentially real until the credential pattern and source are understood.</li>
            <li>Do not add a broad ignore or fake placeholder only to make the count reach zero.</li>
        </ul>
    </x-docs.section>

    <x-docs.section id="updates" title="Updates">
        <p>
            If no update appears, compare the installed version and updates-through date in License settings. <strong>Renewal required</strong> means the newer release is outside the included update period; it does not invalidate the already-covered version.
        </p>
        <p>
            If an eligible update fails to install, download the current build directly and replace the application after quitting Ghostable. Preserve the license key or recovery email before wiping application data.
        </p>
    </x-docs.section>

    <x-docs.section id="support" title="Contact support" :border="false">
        <p>
            Use the <a href="{{ route('contact') }}">contact form</a> with the diagnostics collected above. For a suspected vulnerability, use the <a href="{{ route('security.report') }}">security report form</a> instead.
        </p>
    </x-docs.section>
</x-docs.page>
