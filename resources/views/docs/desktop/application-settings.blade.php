<x-docs.page
    route-name="docs.desktop.reference.application-settings"
    title="Application Settings"
    product-name="Ghostable Desktop"
    section="Reference"
    description="Set local Desktop defaults, appearance, license state, and inspect application and bundled CLI information."
    :on-this-page="[
        ['label' => 'Open settings', 'href' => '#open'],
        ['label' => 'General', 'href' => '#general'],
        ['label' => 'Appearance', 'href' => '#appearance'],
        ['label' => 'License', 'href' => '#license'],
        ['label' => 'Info', 'href' => '#info'],
        ['label' => 'What settings do not sync', 'href' => '#local-only'],
    ]"
>
    <x-docs.section id="open" title="Open settings">
        <p>
            Select the gear in the Project Launcher or use the Ghostable application menu. Application Settings are distinct from the Settings page inside a project window.
        </p>
    </x-docs.section>

    <x-docs.section id="general" title="General">
        <h3>Default IDE</h3>
        <p>
            Choose the system default, Visual Studio Code, Cursor, Windsurf, Zed, Sublime Text, or a supported JetBrains application. Desktop uses this preference when an action opens the current project or file in an editor.
        </p>
        <h3>Default device name</h3>
        <p>
            Set the human-readable device name suggested during project setup or join flows. Use a name that identifies both the person or purpose and machine, such as <code>Sam MacBook Pro</code> or <code>Release workstation</code>.
        </p>
        <p>Changing the default does not rename device identities already created in existing projects.</p>
    </x-docs.section>

    <x-docs.section id="appearance" title="Appearance">
        <p>
            Choose <strong>System</strong>, <strong>Light</strong>, or <strong>Dark</strong>. System follows the current macOS appearance; explicit choices remain fixed until changed.
        </p>
    </x-docs.section>

    <x-docs.section id="license" title="License">
        <p>
            Activate a key, inspect the plan, seats and device activation status, view updates-through and offline-validation dates, validate now, check for updates, or release the current device activation.
        </p>
        <p>
            See <a href="{{ route('docs.desktop.reference.licensing') }}">Licensing & Updates</a> before releasing a device or diagnosing an offline entitlement.
        </p>
    </x-docs.section>

    <x-docs.section id="info" title="Info">
        <p>
            Info reports the Desktop application version, build information, and the bundled Ghostable CLI version or availability. Include both versions in a support request because interface behavior and engine behavior are released independently.
        </p>
    </x-docs.section>

    <x-docs.section id="local-only" title="What settings do not sync" :border="false">
        <p>
            General and appearance preferences are local to this Desktop installation. Launcher groups and repository paths are also local organization metadata. They are not committed under <code>.ghostable/</code> and do not consume or grant project access.
        </p>
    </x-docs.section>
</x-docs.page>
