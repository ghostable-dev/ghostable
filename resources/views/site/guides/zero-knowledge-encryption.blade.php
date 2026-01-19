@inject('learn', '\App\Learn\LearnRepository')
@php
    $guide = $learn->findBySlug('zero-knowledge-encryption');
    $guideTitle = $guide['title'] ?? 'Zero-Knowledge Encryption: What It Means, How It Works, and Why It Matters';
    $guideDescription = $guide['description'] ?? 'A practical guide to zero-knowledge encryption: what it means, why it exists, how it works, and the tradeoffs to plan for.';
    $guideKeywords = $guide['keywords'] ?? [];
    $guideImage = $guide['image'] ?? null;
    $tableOfContents = [
        ['href' => '#what-zero-knowledge-means', 'label' => 'What "zero-knowledge" actually means'],
        ['href' => '#what-it-is-not', 'label' => 'What zero-knowledge is not'],
        ['href' => '#why-it-exists', 'label' => 'Why zero-knowledge exists'],
        ['href' => '#brief-history', 'label' => 'A brief history of zero-knowledge systems'],
        ['href' => '#what-it-offers', 'label' => 'What zero-knowledge actually offers'],
        ['href' => '#tradeoffs', 'label' => 'The tradeoffs and real risks'],
        ['href' => '#how-it-works', 'label' => 'How zero-knowledge works (conceptually)'],
        ['href' => '#key-management', 'label' => 'Key management: where it succeeds or fails'],
        ['href' => '#vs-encrypted-at-rest', 'label' => 'Zero-knowledge vs encrypted at rest'],
        ['href' => '#when-it-makes-sense', 'label' => 'When zero-knowledge makes sense'],
        ['href' => '#in-practice', 'label' => 'Zero-knowledge in practice'],
        ['href' => '#cost-of-doing-it-right', 'label' => 'The cost of doing it right'],
    ];
@endphp

@push('meta')
    <x-seo-meta
        title="{{ $guideTitle }}"
        description="{{ $guideDescription }}"
        :keywords="$guideKeywords"
        :image="$guideImage"
    />
    <x-article-schema
        title="{{ $guideTitle }}"
        description="{{ $guideDescription }}"
        :keywords="$guideKeywords"
        :image="$guideImage"
        :url="route('learn.zero-knowledge-encryption')"
        section="Guide"
    />
    <x-breadcrumb-schema :items="[
        ['name' => 'Learn', 'item' => route('learn.index')],
        ['name' => 'Guides', 'item' => route('learn.index')],
        ['name' => $guideTitle, 'item' => route('learn.zero-knowledge-encryption')],
    ]" />
@endpush

<x-layouts.guest title="{{ $guideTitle }}" canonical="{{ route('learn.zero-knowledge-encryption') }}">
    <div class="bg-white">
        <div class="px-6 lg:px-8 pt-16 pb-20">
            <div class="mx-auto max-w-6xl">
                <div class="lg:grid lg:grid-cols-[minmax(0,3fr)_320px] lg:items-start lg:gap-12">
                    <div class="space-y-8 max-w-3xl">
                        <header class="space-y-4">
                            <flux:breadcrumbs class="pb-2">
                                <flux:breadcrumbs.item href="{{ route('learn.index') }}" separator="slash">Learn</flux:breadcrumbs.item>
                                <flux:breadcrumbs.item separator="slash">Zero-Knowledge Encryption</flux:breadcrumbs.item>
                            </flux:breadcrumbs>
                            <h1 class="text-4xl font-medium tracking-tighter text-gray-950 sm:text-6xl text-pretty">
                                {{ $guideTitle }}
                            </h1>
                            <p class="text-xl font-medium text-gray-600">
                                {{ $guideDescription }}
                            </p>
                        </header>

                        <x-site.on-this-page :items="$tableOfContents" variant="mobile" class="lg:hidden" />

                        <article class="prose prose-lg prose-slate max-w-none">
                            <div class="space-y-8">
                                <p>Zero-knowledge encryption is widely discussed, frequently misunderstood, and often misrepresented. In practice, it is neither a marketing feature nor a blanket security guarantee. It is an architectural decision that fundamentally changes how trust, access, and responsibility are handled in software systems.</p>
                                <p>This guide explains what zero-knowledge actually means, why it exists, how it works at a conceptual level, and what tradeoffs it introduces. The goal is clarity, not hype, so teams can decide when zero-knowledge is appropriate and when it is not.</p>

                                <hr>

                                <h2 id="what-zero-knowledge-means">What "zero-knowledge" actually means</h2>
                                <p>In a zero-knowledge system, the service provider is unable to access customer data in plaintext. Data is encrypted before it leaves the user device, and the keys required to decrypt that data are never available to the service itself. Even with full access to databases, logs, and infrastructure, the provider cannot read customer secrets.</p>
                                <figure>
                                    <img src="{{ cdn_asset('learn/zero-knowledge-is.jpg') }}" alt="Zero-knowledge is mathematically enforced limits" />
                                </figure>
                                <p>This distinction matters because zero-knowledge is enforced by cryptography rather than internal policy. If a system allows server-side decryption under any circumstances&mdash;whether for support, recovery, or administration&mdash;it is not zero-knowledge, regardless of how rarely that access is used.</p>

                                <hr>

                                <h2 id="what-it-is-not">What zero-knowledge is not</h2>
                                <p>Zero-knowledge is often grouped together with other security concepts, which leads to incorrect assumptions. It does not mean a system is immune to compromise, that data can always be recovered, or that users are protected from poor operational decisions. It also does not function as a compliance certification or a replacement for broader security controls.</p>
                                <figure>
                                    <img src="{{ cdn_asset('learn/zero-knowledge-is-not.jpg') }}" alt="Zero-knowledge is not policy-based trust" />
                                </figure>
                                <p>What zero-knowledge does is narrow a very specific risk: unauthorized access to plaintext data by the service provider or its infrastructure. It reduces one class of failure without eliminating all others.</p>

                                <hr>

                                <h2 id="why-it-exists">Why zero-knowledge exists</h2>
                                <p>Traditional cloud systems rely on trust. Data may be encrypted at rest, but the service retains the ability to decrypt it when needed. This simplifies features like search, recovery, and support, but it creates a central point of failure. When systems are breached, the exposure is often broad and immediate.</p>
                                <p>Zero-knowledge systems exist to reduce the impact of inevitable failure. Rather than assuming servers will remain secure indefinitely, they assume compromise will eventually occur and design systems where stolen data is useless without keys that never leave the client. The shift is not from insecurity to security, but from policy-based trust to mathematically enforced limits.</p>

                                <hr>

                                <h2 id="brief-history">A brief history of zero-knowledge systems</h2>
                                <p>The ideas behind zero-knowledge are not new. Cryptographic research into proving statements without revealing underlying information (see <flux:link href="https://en.wikipedia.org/wiki/Zero-knowledge_proof" target="_blank">zero-knowledge proofs</flux:link>) dates back decades, and those principles gradually found their way into practical systems.</p>
                                <figure>
                                    <img src="{{ cdn_asset('learn/zero-knowledge-proof-systems-book.jpg') }}" alt="Book cover for zero-knowledge proof systems" />
                                </figure>
                                <p>Early applications included password hashing and encrypted backups, followed by privacy-focused messaging tools and client-side encryption models. As cloud adoption expanded and insider threats became more visible, zero-knowledge approaches gained relevance in infrastructure and developer tooling. What was once niche is now increasingly expected for high-risk data.</p>

                                <hr>

                                <h2 id="what-it-offers">What zero-knowledge actually offers</h2>
                                <p>The primary benefit of zero-knowledge encryption is a changed failure model. If infrastructure is compromised, attackers obtain encrypted data without the ability to decrypt it. This dramatically reduces the blast radius of breaches and limits downstream damage.</p>
                                <p>Zero-knowledge also mitigates insider risk. Because decryption is not possible on the server, employees cannot access customer secrets, whether intentionally or accidentally. From a compliance perspective, this creates clearer boundaries around data access and can meaningfully reduce audit scope. It also enables safer collaboration by allowing secrets to be shared without re-exposing plaintext across systems.</p>

                                <hr>

                                <h2 id="tradeoffs">The tradeoffs and real risks</h2>
                                <p>Zero-knowledge introduces real costs that should not be minimized. The most significant risk is key loss. If encryption keys are lost and no recovery mechanism exists, the associated data is permanently unrecoverable. There is no override, reset, or support intervention that can reverse this outcome.</p>
                                <p>Operational complexity also increases. Client-side encryption, key hierarchies, device trust, and recovery flows must be designed carefully. Poor implementations can quietly undermine zero-knowledge guarantees, and no amount of cryptography compensates for weak access hygiene or compromised developer machines. For baseline storage and crypto hygiene, reference the <flux:link href="https://cheatsheetseries.owasp.org/cheatsheets/Cryptographic_Storage_Cheat_Sheet.html" target="_blank">OWASP Cryptographic Storage Cheat Sheet</flux:link>.</p>

                                <hr>

                                <h2 id="how-it-works">How zero-knowledge works (conceptually)</h2>
                                <p>Most zero-knowledge systems rely on client-side encryption combined with layered key management. Data is encrypted on the user device using a data encryption key, which is then encrypted by one or more higher-level keys associated with users, teams, or devices. Only encrypted data and encrypted keys are stored on the server.</p>
                                <p>Decryption occurs exclusively on authorized clients that already possess the required keys. The server coordinates access and storage but never participates in decryption. Conceptually, the server behaves as a secure warehouse: it stores encrypted containers and enforces access rules, but it never holds the keys needed to open them.</p>

                                <hr>

                                <h2 id="key-management">Key management: where zero-knowledge succeeds or fails</h2>
                                <p>In practice, most zero-knowledge failures are not cryptographic but operational. Systems break when convenience is prioritized over clear boundaries, such as storing keys server-side "temporarily," adding recovery paths that bypass encryption, or granting administrative access that undermines the model. For deeper key lifecycle guidance, see <flux:link href="https://csrc.nist.gov/publications/detail/sp/800-57-part-1/rev-5/final" target="_blank">NIST SP 800-57 Part 1</flux:link>.</p>
                                <p>Strong zero-knowledge designs make tradeoffs explicit. They favor scoped keys, intentional recovery mechanisms, and permission-aware decryption, even when those choices introduce friction. Convenience can be added later; broken trust models are much harder to repair. If you want to see how device trust impacts key safety, start with our <flux:link href="{{ route('learn.linking-devices') }}">device linking guide</flux:link>.</p>

                                <hr>

                                <h2 id="vs-encrypted-at-rest">Zero-knowledge vs encrypted at rest</h2>
                                <p>The difference between zero-knowledge encryption and encryption at rest is frequently misunderstood, yet critical.</p>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Feature</th>
                                            <th>Encrypted at rest</th>
                                            <th>Zero-knowledge</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><strong>Server can decrypt data</strong></td>
                                            <td>Yes</td>
                                            <td>No</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Insider access possible</strong></td>
                                            <td>Yes</td>
                                            <td>Cryptographically blocked</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Breach impact</strong></td>
                                            <td>High</td>
                                            <td>Limited</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Key ownership</strong></td>
                                            <td>Provider</td>
                                            <td>User or device</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Recovery simplicity</strong></td>
                                            <td>Easy</td>
                                            <td>Requires planning</td>
                                        </tr>
                                    </tbody>
                                </table>
                                <p>Encryption at rest protects against lost disks. Zero-knowledge protects against compromised systems.</p>

                                <hr>

                                <h2 id="when-it-makes-sense">When zero-knowledge makes sense</h2>
                                <p>Zero-knowledge is most appropriate for data that is sensitive, high-impact, and does not require server-side processing in plaintext. Secrets, credentials, API keys, <flux:link href="{{ route('learn.laravel-multi-environment-secrets') }}">environment variables</flux:link>, and encryption keys are natural fits.</p>
                                <p>It is less suitable for searchable content, analytics pipelines, or systems that require server-side inspection or transformation. Zero-knowledge should be a deliberate architectural choice, not a default.</p>

                                <hr>

                                <h2 id="in-practice">Zero-knowledge in practice</h2>
                                <p>Modern zero-knowledge systems must operate across browsers, CLIs, multiple devices, and teams. This requires balancing cryptographic guarantees with real-world usability concerns such as onboarding, rotation, and access changes.</p>
                                <p>When done well, zero-knowledge becomes an architectural commitment rather than a feature. It shapes how recovery works, how teams collaborate, and how trust is distributed across a system.</p>

                                <hr>

                                <h2 id="cost-of-doing-it-right">The cost of doing it right</h2>
                                <p>Zero-knowledge reduces catastrophic risk by limiting who can access sensitive data, but it increases the need for operational discipline. Responsibility shifts from the provider to the user, and mistakes become harder to undo.</p>
                                <p>That tradeoff is not always justified. But when the data matters, and when trust boundaries need to be explicit, zero-knowledge is often worth the cost.</p>

                                <p class="mt-8 text-gray-600">
                                    Want to apply zero-knowledge principles to env secrets? <flux:link href="{{ route('register') }}">Start with Ghostable.</flux:link>
                                </p>
                            </div>
                        </article>
                    </div>

                    <aside class="hidden lg:block space-y-4 lg:sticky lg:top-24">
                        @if(!empty($guide['tags']))
                            <x-site.tag-list :tags="$guide['tags']" variant="card" />
                        @endif

                        <x-site.on-this-page :items="$tableOfContents" />
                    </aside>
                </div>

                <div class="flex flex-wrap gap-3 pt-4">
                    <flux:button variant="primary" href="{{ route('learn.index') }}" icon="chevron-left">
                        Back to Learn
                    </flux:button>
                </div>
            </div>
        </div>

        <livewire:account.livewire.mailing-list-signup-form/>
    </div>
</x-layouts.guest>
