<x-docs.page
    route-name="docs.cli.reference.security"
    title="Security"
    section="Reference"
    description="Ghostable's current security posture, cryptographic model, trust boundaries, protected production access, residual risks, and responsible disclosure process."
    :on-this-page="[
        ['label' => 'Security status', 'href' => '#status'],
        ['label' => 'What zero-knowledge means', 'href' => '#zero-knowledge'],
        ['label' => 'Cryptographic model', 'href' => '#cryptography'],
        ['label' => 'Trust boundaries', 'href' => '#boundaries'],
        ['label' => 'Protected production access', 'href' => '#protected-access'],
        ['label' => 'Threats and residual risk', 'href' => '#threats'],
        ['label' => 'Operational responsibilities', 'href' => '#operations'],
        ['label' => 'Report a vulnerability', 'href' => '#report'],
    ]"
>
    <x-docs.section id="status" title="Security status">
        <x-docs.callout type="warning" title="Ghostable has not completed an external security audit">
            The current assurance evidence is repository-visible focused testing, stable cryptographic test vectors, and a public threat model. Do not represent Ghostable 3.x as formally audited, certified, or proven secure.
        </x-docs.callout>
        <p>
            Review the upstream <a href="https://github.com/ghostable-dev/ghostable/security/policy">security policy</a>, <a href="https://github.com/ghostable-dev/ghostable/blob/main/docs/security/threat-model.md">threat model</a>, and <a href="https://github.com/ghostable-dev/ghostable/blob/main/docs/security/test-vectors.md">test vectors</a> when evaluating Ghostable for a sensitive environment.
        </p>
    </x-docs.section>

    <x-docs.section id="zero-knowledge" title="What zero-knowledge means">
        <p>
            In Ghostable, zero-knowledge has a narrow storage meaning: plaintext secret values are encrypted locally before Ghostable writes repository-backed value records, and Ghostable does not operate a hosted service that receives those plaintext project secrets.
        </p>
        <p>
            It does not mean every component in a workflow is unable to see plaintext. Authorized local devices, process memory, generated env files, CI runners, shell or terminal tooling, and deployment providers may receive decrypted values when a user intentionally performs those operations.
        </p>
    </x-docs.section>

    <x-docs.section id="cryptography" title="Cryptographic model">
        <x-docs.command-table :commands="[
            ['command' => 'Ed25519', 'description' => 'Device signatures for public devices, policy, grants, events, key metadata, value payloads, and other signed records.'],
            ['command' => 'X25519', 'description' => 'Per-device key exchange used to grant environment access.'],
            ['command' => 'XChaCha20-Poly1305', 'description' => 'Authenticated value encryption with random 24-byte nonces.'],
            ['command' => 'HKDF-SHA256', 'description' => 'Derives separate environment encryption and HMAC material scoped to project and environment.'],
            ['command' => 'Environment key grants', 'description' => 'Environment keys are wrapped and shared to authorized devices through per-device encrypted grants.'],
        ]" />
        <p>Signatures provide origin and integrity checks. They do not make an authorized but harmful change safe to merge.</p>
    </x-docs.section>

    <x-docs.section id="boundaries" title="Trust boundaries">
        <ul>
            <li><strong>Local machine:</strong> private device identities and decrypted process memory exist outside the repository boundary.</li>
            <li><strong>Repository:</strong> encrypted values and signed metadata are committed, but repository writers can propose malicious policy or grant changes.</li>
            <li><strong>Automation:</strong> <code>GHOSTABLE_CI_TOKEN</code> is an out-of-band secret trusted for its configured grants.</li>
            <li><strong>Deployment providers:</strong> Forge, Vapor, Cloud, local files, and injected processes receive plaintext after decryption.</li>
        </ul>
    </x-docs.section>

    <x-docs.section id="protected-access" title="Protected production access">
        <p>
            Production-like local operations that write, print, inject, validate, or deploy decrypted values require user-presence verification. macOS uses LocalAuthentication with Touch ID biometric verification. Linux uses the local PAM-backed <code>sudo</code> confirmation, which may use fingerprint verification when configured. Windows requests Windows Hello or the machine's configured fallback.
        </p>
        <p>
            A non-interactive local session cannot satisfy this prompt. CI and deployment jobs must use a scoped automation credential. Dry runs that neither write nor print decrypted values do not require confirmation.
        </p>
    </x-docs.section>

    <x-docs.section id="threats" title="Threats and residual risk">
        <p>Ghostable is designed to resist:</p>
        <ul>
            <li>Passive repository readers without a valid device identity, environment key, or automation credential.</li>
            <li>Undetected modification of signed value, device, policy, access, activity, and key-metadata records.</li>
            <li>Continued authorized use of stale grants after reviewed revocation and key rotation.</li>
            <li>Non-interactive use of production-like local identities without a scoped automation token.</li>
        </ul>
        <p>Ghostable is not designed to fully resist:</p>
        <ul>
            <li>A compromised authorized device, process, terminal, editor, or CI runner.</li>
            <li>Plaintext exposure after values are written to files or passed to a deployment provider.</li>
            <li>Reviewers accepting malicious repository changes.</li>
            <li>Secrets placed in annotations, schema descriptions, change reasons, commit messages, or other plaintext metadata.</li>
        </ul>
    </x-docs.section>

    <x-docs.section id="operations" title="Operational responsibilities">
        <ul>
            <li>Protect local identity stores, CI tokens, and provider credentials.</li>
            <li>Never commit plaintext env files or private identity records.</li>
            <li>Review device, policy, access, suppression, and encrypted-value changes like code.</li>
            <li>Revoke lost or retired devices and rotate affected environment keys promptly.</li>
            <li>Use <code>env clean</code> to reduce local plaintext after sensitive work.</li>
            <li>Keep the CLI updated and reassess the threat model when your workflow changes.</li>
        </ul>
    </x-docs.section>

    <x-docs.section id="report" title="Report a vulnerability" :border="false">
        <p>
            Do not open a public GitHub issue for a suspected vulnerability. Email <a href="mailto:security@ghostable.dev">security@ghostable.dev</a> with the affected version, operating system, installation method, impact, reproduction steps, and redacted supporting evidence. The published policy commits to acknowledging reports within 24 hours.
        </p>
    </x-docs.section>
</x-docs.page>
