@push('meta')
    <x-seo-meta
        title="Trust Center"
        description="Ghostable’s SOC 2 alignment, security controls summary, and roadmap."
        :keywords="[
            'ghostable security',
            'trust center',
            'soc 2 alignment',
            'trust services criteria',
            'zero knowledge',
            'security controls'
        ]"/>
@endpush

<x-layouts.guest title="Trust Center" canonical="{{ route('trust') }}">
    <div class="bg-white">
        <div class="px-6 lg:px-8 py-16 sm:py-20">
            <div class="mx-auto max-w-3xl">
                <h1 class="mt-3 text-4xl font-semibold tracking-tight text-gray-950 sm:text-5xl">
                    Trust Center
                </h1>
                <p class="mt-4 text-lg text-gray-600">
                    Welcome to Ghostable’s Trust Center. Security and privacy are built into how we
                    operate, not bolted on. Use this page to understand our security posture and
                    reach out if you need supporting documentation.
                </p>
                <p class="mt-4 text-lg text-gray-600">
                    Ghostable is aligning with the
                    <flux:link
                        href="https://drata.com/glossary/trust-services-criteria"
                        target="_blank"
                        rel="noopener noreferrer"
                        variant="subtle"
                        class="font-semibold underline decoration-gray-300 underline-offset-4 hover:decoration-gray-500 text-gray-900">
                        SOC 2 Trust Services Criteria
                    </flux:link>
                    for Security, Availability, and Confidentiality. We are not yet audited or certified.
                </p>

                <div class="mt-10 grid gap-8">
                    <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                        <h2 class="text-xl font-semibold text-gray-900">SOC 2 Status</h2>
                        <ul class="mt-4 space-y-2 text-gray-600 list-disc pl-5">
                            <li>Type II in progress.</li>
                            <li>Target coverage period: Q1 2026 (dates TBD).</li>
                            <li>Auditor selection: in progress.</li>
                            <li>Policies and control documentation maintained internally.</li>
                            <li>Evidence collection underway with quarterly cadence.</li>
                        </ul>
                    </section>

                    <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                        <h2 class="text-xl font-semibold text-gray-900">Scope</h2>
                        <ul class="mt-4 space-y-2 text-gray-600 list-disc pl-5">
                            <li>Systems: Ghostable web app, API, desktop client, CLI, admin dashboard, and core infrastructure services used to operate the platform.</li>
                            <li>Trust Services Criteria: Security, Availability, Confidentiality.</li>
                        </ul>
                    </section>

                    <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                        <h2 class="text-xl font-semibold text-gray-900">Controls summary</h2>
                        <ul class="mt-4 space-y-2 text-gray-600 list-disc pl-5">
                            <li>Access control with least privilege and periodic access reviews.</li>
                            <li>Audit logging for sensitive and administrative actions.</li>
                            <li>Change management with source control and CI checks.</li>
                            <li>Vulnerability management with dependency monitoring.</li>
                            <li>Incident response procedures with tabletop exercises.</li>
                            <li>Vendor management for critical third-party services.</li>
                        </ul>
                        <p class="mt-4 text-sm text-gray-500">
                            Reviewers who want a concern-to-control mapping can use the
                            <flux:link href="https://docs.ghostable.dev/fundamentals/v2/security-and-operations/security-controls-matrix" target="_blank">
                                security controls matrix
                            </flux:link>.
                        </p>
                    </section>

                    <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                        <h2 class="text-xl font-semibold text-gray-900">Zero-Knowledge Architecture</h2>
                        <p class="mt-3 text-gray-600">
                            Encryption and decryption happen locally in trusted clients, including the desktop app and CLI. Only ciphertext and
                            non-sensitive metadata are stored. This changes how certain controls are
                            implemented, but not the security objectives they serve. For a deeper
                            walkthrough, see our <flux:link href="{{ route('learn.zero-knowledge-encryption') }}">zero-knowledge guide</flux:link>.
                        </p>
                    </section>

                    <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                        <h2 class="text-xl font-semibold text-gray-900">Release integrity</h2>
                        <p class="mt-3 text-gray-600">
                            Ghostable publishes verifiable release evidence for security review workflows, including checksums, software bill of materials artifacts, and signed build provenance where supported. For desktop releases, we also document code-signing and notarization verification steps so teams can validate what they install.
                        </p>
                        <p class="mt-4 text-sm text-gray-500">
                            See
                            <flux:link href="https://docs.ghostable.dev/fundamentals/v2/security-and-operations/supply-chain-verification" target="_blank">
                                supply chain verification
                            </flux:link>
                            for the current verification workflow.
                        </p>
                    </section>

                    <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                        <h2 class="text-xl font-semibold text-gray-900">External security monitoring</h2>
                        <p class="mt-3 text-gray-600">
                            Ghostable supports signed audit webhook delivery so organizations can forward security-relevant events into their own monitoring stack. Delivery health, retries, failure state, and dead-letter status are exposed so teams can validate that security telemetry is flowing as expected.
                        </p>
                        <p class="mt-4 text-sm text-gray-500">
                            We document integration patterns for Datadog, Splunk, and Elastic in the
                            <flux:link href="https://docs.ghostable.dev/fundamentals/v2/security-and-operations/siem-audit-webhook-templates" target="_blank">
                                SIEM audit webhook templates
                            </flux:link>.
                        </p>
                    </section>

                    <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                        <h2 class="text-xl font-semibold text-gray-900">Roadmap</h2>
                        <p class="mt-3 text-gray-600">
                            We plan to complete a SOC 2 Type II audit after the coverage period and will
                            share updates once a report is available.
                        </p>
                        <p class="mt-3 text-sm text-gray-500">
                            Audit status: No third-party SOC 2 report has been issued.
                        </p>
                    </section>
                </div>

                <div class="mt-10 rounded-2xl border border-gray-200 bg-gray-50 p-6">
                    <p class="text-sm text-gray-700">
                        Questions? Contact support at
                        <a class="font-semibold text-gray-900 underline decoration-gray-300 underline-offset-4 hover:decoration-gray-500"
                           href="mailto:support@ghostable.dev">
                            support@ghostable.dev
                        </a>.
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-layouts.guest>
