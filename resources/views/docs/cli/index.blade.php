<x-layouts.docs
    title="Ghostable CLI 3.x Documentation"
    :canonical="route('docs.cli.index')"
    :on-this-page="[
        ['label' => 'CLI and Desktop', 'href' => '#cli-and-desktop'],
        ['label' => 'Start with the CLI', 'href' => '#start-with-the-cli'],
    ]"
>
    <article>
        <header class="border-b border-gray-200 pb-10 dark:border-white/10">
            <p class="text-sm font-semibold text-brand-dark dark:text-brand-light">Get started</p>
            <h1 class="mt-3 text-4xl font-semibold tracking-tight text-gray-950 sm:text-5xl dark:text-white">Ghostable CLI 3.x</h1>
            <p class="mt-5 max-w-2xl text-lg leading-8 text-gray-600 dark:text-gray-300">
                Versioned documentation for the CLI and the core behavior shared with Ghostable Desktop.
            </p>
        </header>

        <section id="cli-and-desktop" class="scroll-mt-36 border-b border-gray-200 py-10 dark:border-white/10">
            <h2 class="text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">CLI and Desktop</h2>
            <div class="mt-4 flex flex-col gap-4 text-base leading-7 text-gray-600 dark:text-gray-300">
                <p>
                    The CLI is Ghostable's core engine. It owns the versioned behavior for reading, validating, encrypting, and materializing environment configuration.
                </p>
                <p>
                    Ghostable Desktop provides a macOS interface around that engine. Desktop-specific setup and interface guidance lives in the separate Desktop section.
                </p>
            </div>
        </section>

        <section id="start-with-the-cli" class="scroll-mt-36 py-10">
            <h2 class="text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">Start with the CLI</h2>
            <p class="mt-4 text-base leading-7 text-gray-600 dark:text-gray-300">
                Install Ghostable CLI 3.x, confirm the installed version, and then open your first Ghostable-enabled repository.
            </p>

            <a
                href="{{ route('docs.cli.installation') }}"
                class="group mt-7 block rounded-xl border border-gray-200 bg-gray-50 p-6 transition hover:border-brand hover:bg-white dark:border-white/10 dark:bg-white/5 dark:hover:border-brand dark:hover:bg-white/[0.07]"
            >
                <p class="text-sm font-semibold text-brand-dark dark:text-brand-light">Getting started</p>
                <h3 class="mt-1 text-xl font-semibold text-gray-950 dark:text-white">Installation</h3>
                <p class="mt-2 text-gray-600 dark:text-gray-300">Install Ghostable CLI 3.x and confirm it is ready to use.</p>
                <span class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-gray-950 dark:text-white">
                    Continue to installation
                    <span aria-hidden="true" class="transition group-hover:translate-x-1">&rarr;</span>
                </span>
            </a>
        </section>
    </article>
</x-layouts.docs>
