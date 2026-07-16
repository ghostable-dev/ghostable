@props([
    'routeName',
    'title',
    'section',
    'description',
    'onThisPage' => [],
    'productName' => 'Ghostable CLI 3.x',
])

@php
    $documentTitle = $title === $productName ? $title : $title.' | '.$productName;
    $cliPages = [
        ['route' => 'docs.cli.index', 'label' => 'Introduction', 'source' => 'resources/views/docs/cli/index.blade.php'],
        ['route' => 'docs.cli.installation', 'label' => 'Installation', 'source' => 'resources/views/docs/cli/installation.blade.php'],
        ['route' => 'docs.cli.new-projects', 'label' => 'New Projects', 'source' => 'resources/views/docs/cli/new-projects.blade.php'],
        ['route' => 'docs.cli.existing-projects', 'label' => 'Existing Projects', 'source' => 'resources/views/docs/cli/existing-projects.blade.php'],
        ['route' => 'docs.cli.team-onboarding', 'label' => 'Team Onboarding', 'source' => 'resources/views/docs/cli/team-onboarding.blade.php'],
        ['route' => 'docs.cli.workflows.projects', 'label' => 'Repository & Storage', 'source' => 'resources/views/docs/cli/repository-storage.blade.php'],
        ['route' => 'docs.cli.workflows.environments', 'label' => 'Environments', 'source' => 'resources/views/docs/cli/environments.blade.php'],
        ['route' => 'docs.cli.workflows.variable-promotions', 'label' => 'Variables & Promotions', 'source' => 'resources/views/docs/cli/variables.blade.php'],
        ['route' => 'docs.cli.workflows.devices', 'label' => 'Access & Devices', 'source' => 'resources/views/docs/cli/access-devices.blade.php'],
        ['route' => 'docs.cli.workflows.daily-development', 'label' => 'Daily Development', 'source' => 'resources/views/docs/cli/daily-workflow.blade.php'],
        ['route' => 'docs.cli.workflows.review', 'label' => 'Review & Secret Scanning', 'source' => 'resources/views/docs/cli/review.blade.php'],
        ['route' => 'docs.cli.workflows.hygiene', 'label' => 'Hygiene & Rotation', 'source' => 'resources/views/docs/cli/hygiene.blade.php'],
        ['route' => 'docs.cli.workflows.deploy-tokens', 'label' => 'Automation Credentials', 'source' => 'resources/views/docs/cli/automation-credentials.blade.php'],
        ['route' => 'docs.cli.automation.continuous-integration', 'label' => 'Continuous Integration', 'source' => 'resources/views/docs/cli/continuous-integration.blade.php'],
        ['route' => 'docs.cli.automation.deployments', 'label' => 'Deployments', 'source' => 'resources/views/docs/cli/deployments.blade.php'],
        ['route' => 'docs.cli.reference.validation', 'label' => 'Validation', 'source' => 'resources/views/docs/cli/validation.blade.php'],
        ['route' => 'docs.cli.reference.commands', 'label' => 'Command Reference', 'source' => 'resources/views/docs/cli/command-reference.blade.php'],
        ['route' => 'docs.cli.reference.configuration', 'label' => 'Configuration', 'source' => 'resources/views/docs/cli/configuration.blade.php'],
        ['route' => 'docs.cli.reference.security', 'label' => 'Security', 'source' => 'resources/views/docs/cli/security.blade.php'],
        ['route' => 'docs.cli.reference.backups', 'label' => 'Backups & Offline', 'source' => 'resources/views/docs/cli/backups.blade.php'],
        ['route' => 'docs.cli.reference.agents', 'label' => 'Agent Integration', 'source' => 'resources/views/docs/cli/agent-integration.blade.php'],
        ['route' => 'docs.cli.reference.troubleshooting', 'label' => 'Troubleshooting', 'source' => 'resources/views/docs/cli/troubleshooting.blade.php'],
    ];
    $currentPageIndex = array_search($routeName, array_column($cliPages, 'route'), true);
    $currentPage = $currentPageIndex === false ? null : $cliPages[$currentPageIndex];
    $previousPage = $currentPageIndex !== false && $currentPageIndex > 0 ? $cliPages[$currentPageIndex - 1] : null;
    $nextPage = $currentPageIndex !== false && $currentPageIndex < count($cliPages) - 1 ? $cliPages[$currentPageIndex + 1] : null;
@endphp

<x-layouts.docs
    :title="$documentTitle"
    :heading="$title"
    :canonical="route($routeName)"
    :description="$description"
    :on-this-page="$onThisPage"
>
    <article>
        <header class="border-b border-gray-200 pb-10 dark:border-white/10">
            <p class="text-sm font-semibold text-brand-dark dark:text-brand-light">{{ $section }}</p>
            <h1 class="mt-3 text-4xl font-semibold tracking-tight text-gray-950 sm:text-5xl dark:text-white">{{ $title }}</h1>
            <p class="mt-5 max-w-3xl text-lg leading-8 text-gray-600 dark:text-gray-300">
                {{ $description }}
            </p>
        </header>

        {{ $slot }}

        @if($currentPage)
            <footer data-docs-page-navigation class="mt-14 border-t border-gray-200 pt-8 dark:border-white/10">
                <a
                    data-docs-edit-link
                    href="{{ 'https://github.com/ghostable-dev/ghostable/edit/main/'.$currentPage['source'] }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-2 text-sm font-medium text-gray-500 transition-colors hover:text-gray-950 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand dark:text-gray-400 dark:hover:text-white"
                >
                    <flux:icon.pencil-square variant="mini" class="size-4" />
                    Edit this page
                </a>

                <nav aria-label="Previous and next documentation pages" class="mt-7 grid gap-4 sm:grid-cols-2">
                    @if($previousPage)
                        <a
                            data-docs-previous-page
                            href="{{ route($previousPage['route']) }}"
                            class="group rounded-xl border border-gray-200 p-5 transition-colors hover:border-brand/50 hover:bg-brand/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand dark:border-white/10 dark:hover:border-brand/50 dark:hover:bg-brand/10"
                        >
                            <span class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">
                                <flux:icon.arrow-left variant="mini" class="size-3.5 transition-transform group-hover:-translate-x-0.5" />
                                Previous
                            </span>
                            <span class="mt-2 block font-semibold text-gray-950 dark:text-white">{{ $previousPage['label'] }}</span>
                        </a>
                    @else
                        <span aria-hidden="true"></span>
                    @endif

                    @if($nextPage)
                        <a
                            data-docs-next-page
                            href="{{ route($nextPage['route']) }}"
                            class="group rounded-xl border border-gray-200 p-5 text-right transition-colors hover:border-brand/50 hover:bg-brand/5 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand dark:border-white/10 dark:hover:border-brand/50 dark:hover:bg-brand/10"
                        >
                            <span class="flex items-center justify-end gap-2 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">
                                Next
                                <flux:icon.arrow-right variant="mini" class="size-3.5 transition-transform group-hover:translate-x-0.5" />
                            </span>
                            <span class="mt-2 block font-semibold text-gray-950 dark:text-white">{{ $nextPage['label'] }}</span>
                        </a>
                    @endif
                </nav>
            </footer>
        @endif
    </article>
</x-layouts.docs>
