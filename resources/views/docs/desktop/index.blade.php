<x-layouts.docs
    title="Ghostable Desktop Documentation"
    :canonical="route('docs.desktop.index')"
    :on-this-page="[
        ['label' => 'Powered by the CLI', 'href' => '#powered-by-the-cli'],
        ['label' => 'Start with Desktop', 'href' => '#start-with-desktop'],
    ]"
>
    <article>
        <header class="border-b border-gray-200 pb-10 dark:border-white/10">
            <p class="text-sm font-semibold text-brand-dark dark:text-brand-light">Desktop</p>
            <h1 class="mt-3 text-4xl font-semibold tracking-tight text-gray-950 sm:text-5xl dark:text-white">Ghostable Desktop</h1>
            <p class="mt-5 max-w-2xl text-lg leading-8 text-gray-600 dark:text-gray-300">
                Documentation for the current Ghostable Desktop app and its bundled CLI engine.
            </p>
        </header>

        <section id="powered-by-the-cli" class="scroll-mt-36 border-b border-gray-200 py-10 dark:border-white/10">
            <h2 class="text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">Powered by the CLI</h2>
            <div class="mt-4 flex flex-col gap-4 text-base leading-7 text-gray-600 dark:text-gray-300">
                <p>
                    Ghostable Desktop runs the Ghostable CLI under the hood, so the versioned CLI documentation remains the source of truth for core behavior.
                </p>
                <p>
                    This section stays focused on the desktop application: installation, licensing, the macOS interface, and app-specific troubleshooting.
                </p>
            </div>
        </section>

        <section id="start-with-desktop" class="scroll-mt-36 py-10">
            <h2 class="text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">Start with Desktop</h2>
            <p class="mt-4 text-base leading-7 text-gray-600 dark:text-gray-300">
                Install the current macOS application, activate your license, and open a Ghostable-enabled repository.
            </p>

            <a
                href="{{ route('docs.desktop.installation') }}"
                class="group mt-7 block rounded-xl border border-gray-200 bg-gray-50 p-6 transition hover:border-brand hover:bg-white dark:border-white/10 dark:bg-white/5 dark:hover:border-brand dark:hover:bg-white/[0.07]"
            >
                <p class="text-sm font-semibold text-brand-dark dark:text-brand-light">Getting started</p>
                <h3 class="mt-1 text-xl font-semibold text-gray-950 dark:text-white">Installation</h3>
                <p class="mt-2 text-gray-600 dark:text-gray-300">Install the current Ghostable Desktop app for macOS.</p>
                <span class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-gray-950 dark:text-white">
                    Continue to installation
                    <span aria-hidden="true" class="transition group-hover:translate-x-1">&rarr;</span>
                </span>
            </a>
        </section>
    </article>
</x-layouts.docs>
