<x-docs.page
    route-name="docs.desktop.installation"
    title="Installation"
    product-name="Ghostable Desktop"
    section="Getting Started"
    description="Download the current macOS build, install it like a native application, activate your license, and verify the bundled CLI engine."
    :on-this-page="[
        ['label' => 'Requirements', 'href' => '#requirements'],
        ['label' => 'Install on macOS', 'href' => '#install'],
        ['label' => 'First launch', 'href' => '#first-launch'],
        ['label' => 'Activate a license', 'href' => '#activate'],
        ['label' => 'Verify the installation', 'href' => '#verify'],
        ['label' => 'Updates', 'href' => '#updates'],
    ]"
>
    <x-docs.section id="requirements" title="Requirements">
        <ul>
            <li>macOS 13 or newer.</li>
            <li>A local Git repository you can read and write.</li>
            <li>A Ghostable Desktop license key for project access.</li>
        </ul>
        <p>
            The macOS download is a universal DMG for Apple silicon and Intel Macs. Check the <a href="{{ route('download') }}">download page</a> for currently available operating systems; platforms still marked as coming soon cannot be installed yet.
        </p>
    </x-docs.section>

    <x-docs.section id="install" title="Install on macOS">
        <ol>
            <li>Open the <a href="{{ route('download') }}">Ghostable download page</a> and download the macOS DMG.</li>
            <li>Open the disk image and drag <strong>Ghostable</strong> into the <strong>Applications</strong> folder.</li>
            <li>Eject the disk image, then open Ghostable from Applications.</li>
        </ol>
        <x-docs.callout type="info" title="Signed distribution">
            Production releases are code signed and notarized for macOS. If Gatekeeper reports that the app is damaged or cannot verify its developer, download a fresh copy from Ghostable instead of bypassing the warning.
        </x-docs.callout>
    </x-docs.section>

    <x-docs.section id="first-launch" title="First launch">
        <p>
            Ghostable opens the Project Launcher. Use the gear button to open application settings, choose an appearance, set a default IDE, and enter the device name that project setup should suggest.
        </p>
        <p>
            The launcher is available without a license, but opening a project requires an active or currently valid offline entitlement.
        </p>
    </x-docs.section>

    <x-docs.section id="activate" title="Activate a license">
        <ol>
            <li>Open <strong>Settings</strong> from the launcher or the application menu.</li>
            <li>Select <strong>License</strong>.</li>
            <li>Paste the complete license key from your purchase email.</li>
            <li>Select <strong>Activate</strong> and wait for the plan and device status to appear.</li>
        </ol>
        <p>
            Activation binds one activation to this device. If you do not have a key, use <strong>Get a license</strong>. If a key was lost, use the <a href="{{ route('licenses.manage') }}">license management and recovery page</a>.
        </p>
    </x-docs.section>

    <x-docs.section id="verify" title="Verify the installation">
        <p>
            Open <strong>Settings → Info</strong> and record both the Desktop application version and bundled Ghostable CLI version. They are reported separately because the interface and engine can have different release numbers.
        </p>
        <p>
            Add a small repository from the launcher. A configured project should open its shared environments; an unconfigured repository should open the setup guide. If either path fails, use <a href="{{ route('docs.desktop.reference.troubleshooting') }}">Troubleshooting</a>.
        </p>
    </x-docs.section>

    <x-docs.section id="updates" title="Updates" :border="false">
        <p>
            Use <strong>Check for Updates</strong> in the application or License settings. Update eligibility is based on the license's updates-through date, while the license itself remains valid for versions covered by the purchase.
        </p>
        <p>
            Read <a href="{{ route('docs.desktop.reference.licensing') }}">Licensing & Updates</a> before moving activations between machines or renewing update eligibility.
        </p>
    </x-docs.section>
</x-docs.page>
