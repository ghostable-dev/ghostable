<x-docs.page
    route-name="docs.cli.installation"
    title="Installation"
    section="Getting Started"
    description="Install Ghostable CLI 3.x on macOS, Linux, or Windows, then verify which binary your project is using."
    :on-this-page="[
        ['label' => 'macOS', 'href' => '#macos'],
        ['label' => 'npm projects', 'href' => '#npm'],
        ['label' => 'Linux and Windows', 'href' => '#release-archives'],
        ['label' => 'Verify the installation', 'href' => '#verify'],
        ['label' => 'Updating', 'href' => '#updating'],
    ]"
>
    <x-docs.section id="macos" title="macOS">
        <p>The Homebrew cask installs the signed and notarized macOS release:</p>
        <x-docs.terminal
            title="Homebrew"
            :commands="[
                'brew tap ghostable-dev/ghostable',
                'brew install --cask ghostable',
            ]"
        />
        <p>The global binary is available as <code>ghostable</code> from any project directory.</p>
    </x-docs.section>

    <x-docs.section id="npm" title="npm projects">
        <p>
            Install the CLI as a project dependency when you want every developer and CI job to use the version declared by the repository:
        </p>
        <x-docs.terminal title="npm" :commands="['npm install @ghostable/cli']" />
        <p>
            npm places the binary at <code>node_modules/.bin/ghostable</code>. npm scripts resolve that binary automatically; outside a script, use <code>npx ghostable</code> or invoke the binary directly.
        </p>
    </x-docs.section>

    <x-docs.section id="release-archives" title="Linux and Windows">
        <p>
            Ghostable publishes release archives for macOS, Linux, and Windows on both <code>amd64</code> and <code>arm64</code>. Download the archive matching your operating system and architecture from the <a href="https://github.com/ghostable-dev/ghostable/releases/latest">latest GitHub release</a>, extract the binary, and place it on <code>PATH</code>.
        </p>
        <p>
            On Linux and other Unix systems, make the extracted file executable before moving it to a directory on <code>PATH</code>. On Windows, place <code>ghostable.exe</code> in a directory included in the user or system <code>PATH</code>.
        </p>
    </x-docs.section>

    <x-docs.section id="verify" title="Verify the installation">
        <x-docs.terminal title="Verify Ghostable" :commands="['ghostable --version', 'ghostable --help']" :output="['3.x.x']" />
        <p>
            If multiple copies are installed, confirm the executable resolved by your shell with <code>which ghostable</code> on macOS/Linux or <code>where ghostable</code> on Windows. A project-local npm installation may intentionally differ from a global installation.
        </p>
    </x-docs.section>

    <x-docs.section id="updating" title="Updating" :border="false">
        <x-docs.terminal
            title="Update Ghostable"
            :commands="[
                '# Homebrew',
                'brew upgrade --cask ghostable',
                '# npm',
                'npm install @ghostable/cli@latest',
            ]"
        />
        <p>Commit npm lockfile changes so the team and CI resolve the same CLI release.</p>
    </x-docs.section>
</x-docs.page>
