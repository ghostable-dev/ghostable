@inject('learn', '\App\Learn\LearnRepository')
@php
    $tutorial = $learn->findBySlug('linking-devices');
    $tutorialTitle = $tutorial['title'] ?? 'Linking and unlinking devices the right way';
    $tutorialDescription = $tutorial['description'] ?? 'Understand how device linking enables zero-knowledge encryption, and how to onboard or offboard teammates safely.';
    $tutorialKeywords = $tutorial['keywords'] ?? [];
    $tutorialImage = $tutorial['image'] ?? null;
    $tableOfContents = [
        ['href' => '#why-devices-matter', 'label' => 'Why devices matter'],
        ['href' => '#prerequisites', 'label' => 'Prerequisites'],
        ['href' => '#step-1', 'label' => '1. Link your device'],
        ['href' => '#step-2', 'label' => '2. Verify device status'],
        ['href' => '#step-3', 'label' => '3. Onboard a new teammate'],
        ['href' => '#step-4', 'label' => '4. Unlink and rotate on departure'],
        ['href' => '#step-5', 'label' => '5. Keep CI and tokens separate'],
        ['href' => '#anti-patterns', 'label' => 'What not to do'],
        ['href' => '#next-steps', 'label' => 'Next steps'],
    ];
@endphp

@push('meta')
    <x-seo-meta
        title="{{ $tutorialTitle }} - Ghostable Tutorials"
        description="{{ $tutorialDescription }}"
        :keywords="$tutorialKeywords"
        :image="$tutorialImage"
    />
@endpush

<x-layouts.guest title="{{ $tutorialTitle }}" canonical="{{ route('learn.linking-devices') }}">
    <div class="bg-white">
        <div class="px-6 lg:px-8 pt-16 pb-20">
            <div class="mx-auto max-w-6xl">
                <div class="lg:grid lg:grid-cols-[minmax(0,3fr)_320px] lg:items-start lg:gap-12">
                    <div class="space-y-8 max-w-3xl">
                        <header class="space-y-4">
                            <flux:breadcrumbs class="pb-2">
                                <flux:breadcrumbs.item href="{{ route('learn.index') }}" separator="slash">Learn</flux:breadcrumbs.item>
                                <flux:breadcrumbs.item separator="slash">Tutorials</flux:breadcrumbs.item>
                                <flux:breadcrumbs.item separator="slash">Linking devices</flux:breadcrumbs.item>
                            </flux:breadcrumbs>
                            <h1 class="text-4xl font-medium tracking-tighter text-gray-950 sm:text-6xl text-pretty">
                                {{ $tutorialTitle }}
                            </h1>
                            <p class="text-xl font-medium text-gray-600">
                                {{ $tutorialDescription }}
                            </p>
                        </header>

                        <x-site.on-this-page :items="$tableOfContents" variant="mobile" class="lg:hidden" />

                        <article class="prose prose-lg prose-slate max-w-none">
                            <div class="space-y-8">
                                <p>Ghostable is zero-knowledge: every secret is encrypted with keys that live on your device. Linking registers your workstation so Ghostable can share environment keys with it, and unlinking revokes those keys instantly. This tutorial shows you how to link, check, and remove devices for yourself and teammates.</p>

                                <h2 id="why-devices-matter">Why devices matter</h2>
                                <ul>
                                    <li>Encryption keys live in your OS keychain; Ghostable never sees plaintext env values.</li>
                                    <li>Only linked devices (or deploy tokens) can decrypt environment data.</li>
                                    <li>Revoking a device immediately cuts off access without rotating every secret.</li>
                                    <li>New teammates must link to receive shared environment keys.</li>
                                </ul>

                                <h2 id="prerequisites">Prerequisites</h2>
                                <ul>
                                    <li>Ghostable CLI installed.</li>
                                    <li>Logged in with an account that has access to the organization/project.</li>
                                    <li>OS keychain available (on WSL, install a keyring package before linking).</li>
                                </ul>

                                <h2 id="step-1">1. Link your device</h2>
                                <p>Run the device wizard on any new workstation or rotation host.</p>
<pre><code class="language-bash">ghostable device link
</code></pre>
                                <p>What happens: the CLI mints signing + encryption keys locally, registers the public keys with Ghostable, and stores the private keys in your OS keychain. No secrets leave your machine.</p>

                                <h2 id="step-2">2. Verify device status</h2>
                                <p>Confirm that your local keys match what Ghostable knows about this machine.</p>
<pre><code class="language-bash">ghostable device status
</code></pre>
                                <p>You should see local fingerprints, device ID, platform, and remote status. If the device was revoked, link again to regain access.</p>

                                <h2 id="step-3">3. Onboard a new teammate</h2>
                                <ol>
                                    <li>Add them to the organization/project with the right role.</li>
                                    <li>They run <code>ghostable login</code> then <code>ghostable device link</code> on their workstation.</li>
                                    <li>Ghostable shares environment keys to their newly linked device; they can now pull and decrypt envs.</li>
                                </ol>
                                <p>Tip: ask new teammates to run <code>ghostable device status</code> and a quick <code>ghostable env pull</code> to confirm access.</p>

                                <h2 id="step-4">4. Unlink and rotate on departure</h2>
                                <p>When someone leaves or a laptop is compromised, revoke the device and clear local keys.</p>
<pre><code class="language-bash">ghostable device unlink
</code></pre>
                                <p>This deletes local key material and revokes the device server-side. Ghostable re-shares environment keys to remaining devices, so the revoked machine can no longer decrypt secrets. For sensitive environments, follow with targeted secret rotation.</p>

                                <h2 id="step-5">5. Keep CI and tokens separate</h2>
                                <p>CI runners should use deploy tokens, not human device identities. Devices are for people; tokens are scoped to specific environments and can be rotated independently.</p>
<pre><code class="language-bash"># create and use a deploy token instead of linking the CI host
ghostable deploy token create --env production --name "github-actions"
</code></pre>

                                <h2 id="anti-patterns">What not to do</h2>
                                <ul>
                                    <li>Do not share one linked device across multiple people or machines.</li>
                                    <li>Do not skip unlinking when hardware is lost or a teammate leaves.</li>
                                    <li>Do not rely on API login alone; without a linked device, decryption will fail.</li>
                                    <li>Do not use personal tokens in CI; prefer deploy tokens or service accounts.</li>
                                </ul>

                                <h2 id="next-steps">Next steps</h2>
                                <ul>
                                    <li>Audit devices in the dashboard and prune stale entries regularly.</li>
                                    <li>Pair device linking with validation so env changes are checked before deploys.</li>
                                    <li>Read the <flux:link href="https://docs.ghostable.dev/v2/the-basics/devices" target="_blank">devices guide</flux:link> and <flux:link href="https://docs.ghostable.dev/v2/the-basics/deploy-tokens" target="_blank">deploy token guide</flux:link> for deeper policy options.</li>
                                </ul>
                            </div>
                        </article>
                    </div>

                    <aside class="hidden lg:block space-y-4 lg:sticky lg:top-24">
                        @if(!empty($tutorial['tags']))
                            <x-site.tag-list :tags="$tutorial['tags']" variant="card" />
                        @endif

                        <x-site.on-this-page :items="$tableOfContents" />
                    </aside>
                </div>

                <div class="flex flex-wrap gap-3 pt-4">
                    <flux:button variant="primary" href="{{ route('learn.index') }}" icon="chevron-left">
                        Back to Learn
                    </flux:button>
                    <flux:button variant="ghost" href="https://docs.ghostable.dev/v2/the-basics/devices" target="_blank">
                        View devices docs
                    </flux:button>
                </div>
            </div>
        </div>
    </div>

    <livewire:account.livewire.mailing-list-signup-form/>
</x-layouts.guest>
