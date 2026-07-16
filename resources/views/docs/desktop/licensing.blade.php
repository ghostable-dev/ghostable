<x-docs.page
    route-name="docs.desktop.reference.licensing"
    title="Licensing & Updates"
    product-name="Ghostable Desktop"
    section="Reference"
    description="Understand one-time Desktop licenses, seats, activations, online and offline validation, device transfers, recovery, and update eligibility."
    :on-this-page="[
        ['label' => 'License model', 'href' => '#model'],
        ['label' => 'Plans and limits', 'href' => '#plans'],
        ['label' => 'Activate a device', 'href' => '#activate'],
        ['label' => 'Seats and activations', 'href' => '#seats'],
        ['label' => 'Offline use', 'href' => '#offline'],
        ['label' => 'Release or recover', 'href' => '#release-recover'],
        ['label' => 'Updates and renewal', 'href' => '#updates'],
        ['label' => 'License data', 'href' => '#license-data'],
    ]"
>
    <x-docs.section id="model" title="License model">
        <p>
            Ghostable Desktop is paid software sold with a one-time license. The license does not expire for application versions covered by the purchase, and it includes one year of eligible Desktop updates from the purchase date.
        </p>
        <p>
            Buying a Desktop license is separate from repository permissions. A licensed person still needs a Ghostable device identity and grants for each encrypted project environment.
        </p>
        <x-docs.callout type="info" title="No account required for purchase">
            Guest checkout sends the license key to the purchase email. An optional Ghostable account can claim licenses for team management and recovery, but project secrets and device grants remain repository-backed.
        </x-docs.callout>
    </x-docs.section>

    <x-docs.section id="plans" title="Plans and limits">
        <x-docs.command-table :commands="[
            ['command' => 'Personal — $49', 'description' => '1 seat and up to 2 active device activations.'],
            ['command' => 'Team 5 — $249', 'description' => 'Up to 5 seats and 5 active device activations.'],
            ['command' => 'Team 10 — $499', 'description' => 'Up to 10 seats and 10 active device activations.'],
            ['command' => 'Business', 'description' => 'Contact Ghostable for larger or custom licensing needs.'],
        ]" />
        <p>
            These are the current one-time plans documented by the application. The <a href="{{ route('licenses') }}">license purchase page</a> is authoritative for current pricing and terms at checkout.
        </p>
    </x-docs.section>

    <x-docs.section id="activate" title="Activate a device">
        <ol>
            <li>Open <strong>Application Settings → License</strong>.</li>
            <li>Paste the entire license key and choose <strong>Activate</strong>.</li>
            <li>Desktop registers a fingerprint for this installation and stores a protected activation token locally.</li>
            <li>Confirm that the panel reports the expected plan, seat allowance, activations, updates-through date, and offline-until date.</li>
        </ol>
        <p>
            A successful activation enables project windows. The launcher, license form, and Info page remain accessible when no license is active.
        </p>
{{-- ghostable:screenshot-placement {"id":"desktop-license-activation","provider":"ghostable-desktop-v3","shot_id":"license-activation","alt":"Ghostable Desktop license activation form","caption":"Enter the purchase key in Application Settings to activate this Desktop installation."} --}}
{{-- ghostable:screenshot-output desktop-license-activation:start --}}
<img
    class="block dark:hidden w-full rounded-xl"
    src="{{ asset('images/generated/screenshots/ghostable-desktop-v3/license-activation-light.png') }}"
    alt="Ghostable Desktop license activation form"
/>

<img
    class="hidden dark:block w-full rounded-xl"
    src="{{ asset('images/generated/screenshots/ghostable-desktop-v3/license-activation-dark.png') }}"
    alt="Ghostable Desktop license activation form"
/>
<p class="mt-3 text-sm text-zinc-500">Enter the purchase key in Application Settings to activate this Desktop installation.</p>
{{-- ghostable:screenshot-output desktop-license-activation:end --}}

    </x-docs.section>

    <x-docs.section id="seats" title="Seats and activations">
        <p>
            A <strong>seat</strong> represents licensed usage under the plan. An <strong>activation</strong> is a registered Desktop installation or device. Personal includes two activations for one seat so the licensed person can use more than one machine; team limits scale with the selected plan.
        </p>
        <p>
            Activations do not replace project access grants. Releasing a Desktop activation does not revoke that machine's repository identity, and revoking a project device does not automatically free a paid Desktop activation.
        </p>
    </x-docs.section>

    <x-docs.section id="offline" title="Offline use">
        <p>
            After online activation, Desktop caches a signed entitlement for offline use and attempts validation periodically. The default entitlement window is seven days and the client normally attempts validation every six hours; the <strong>Offline until</strong> value shown in License settings is authoritative for the entitlement actually issued.
        </p>
        <p>
            If the cache reaches its valid-until time without a successful validation, project access is blocked until Desktop can validate again. This does not mean the perpetual product license expired; it means the local cached proof is no longer current.
        </p>
        <x-docs.callout type="security" title="Signed offline entitlement">
            Desktop verifies the cached entitlement signature before trusting it. Editing the local license state cannot create a valid entitlement.
        </x-docs.callout>
    </x-docs.section>

    <x-docs.section id="release-recover" title="Release or recover a license">
        <p>
            Choose <strong>Release Device</strong> before selling, wiping, or retiring a machine. The release contacts the licensing service, removes this activation, and clears the local activation token.
        </p>
        <p>
            If the old machine is unavailable or the key is lost, open <a href="{{ route('licenses.manage') }}">Manage licenses</a> and request a signed recovery link for the purchase email. Team owners who claimed a license can manage it through their account.
        </p>
        <p>
            Contact support when an activation cannot be released and automated recovery does not restore access. Never send project secrets or automation tokens with a license request.
        </p>
    </x-docs.section>

    <x-docs.section id="updates" title="Updates and renewal">
        <p>
            <strong>Check for Updates</strong> compares the installed Desktop version with the current release and asks the license service whether that version is covered. If the license's updates-through date covers the release, Desktop can offer the update.
        </p>
        <p>
            After the included update year, the installed covered version remains licensed. A newer release may report <strong>renewal required</strong>. Renewing update eligibility extends access to newer versions; it is not a subscription required to keep using the already-covered version.
        </p>
    </x-docs.section>

    <x-docs.section id="license-data" title="License data and privacy" :border="false">
        <p>
            Activation and validation send the license key or protected activation token, application version, and device fingerprint needed to enforce the plan. Update checks send version and entitlement context. These requests are separate from Ghostable project operations and do not include environment names, keys, values, repository contents, or <code>.ghostable/</code> state.
        </p>
        <p>
            On macOS, the activation token is stored in Keychain under Ghostable's Desktop service. Other supported platforms use Electron-protected local storage when available. See <a href="{{ route('privacy') }}">Privacy</a> for the service policy.
        </p>
    </x-docs.section>
</x-docs.page>
