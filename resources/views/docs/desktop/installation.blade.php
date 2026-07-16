<x-layouts.docs
    title="Install Ghostable Desktop"
    :canonical="route('docs.desktop.installation')"
    :on-this-page="[
        ['label' => 'Install the app', 'href' => '#install-the-app'],
        ['label' => 'Check your versions', 'href' => '#check-your-versions'],
    ]"
>
    <article>
        <header class="border-b border-gray-200 pb-10 dark:border-white/10">
            <p class="text-sm font-semibold text-brand-dark dark:text-brand-light">Getting started</p>
            <h1 class="mt-3 text-4xl font-semibold tracking-tight text-gray-950 sm:text-5xl dark:text-white">Install Ghostable Desktop</h1>
            <p class="mt-5 max-w-2xl text-lg leading-8 text-gray-600 dark:text-gray-300">
                Install the current Ghostable Desktop app and confirm which CLI engine it includes.
            </p>
        </header>

        <section id="install-the-app" class="scroll-mt-36 border-b border-gray-200 py-10 dark:border-white/10">
            <h2 class="text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">Install the app</h2>
            <p class="mt-4 text-base leading-7 text-gray-600 dark:text-gray-300">
                The installation guide for the current Ghostable Desktop app will live at this unversioned URL.
            </p>
            <flux:button href="{{ route('download') }}" variant="primary" icon:trailing="arrow-down-tray" class="mt-6">
                Download Ghostable Desktop
            </flux:button>
        </section>

        <section id="check-your-versions" class="scroll-mt-36 py-10">
            <h2 class="text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">Check your versions</h2>
            <p class="mt-4 text-base leading-7 text-gray-600 dark:text-gray-300">
                Ghostable Desktop diagnostics should report the Desktop version and bundled CLI version separately so support can match behavior to the correct documentation.
            </p>
        </section>
    </article>
</x-layouts.docs>
