@props([
    'autoOpen' => true,
    'dismissible' => false,
    'closable' => false,
])

<div id="x-cli-device-blocker" x-data x-init="$nextTick(() => { if (@js($autoOpen)) $flux.modal('device-setup-blocker').show() })">
    <flux:modal
        name="device-setup-blocker"
        :dismissible="$dismissible"
        :closable="$closable"
        class="w-full max-w-2xl"
    >
        <div class="space-y-6" x-data="{ setupPath: 'desktop' }">
            <div class="flex items-start justify-between gap-4 text-left">
                <flux:heading size="xl" class="font-semibold">Getting Started</flux:heading>
                <flux:button
                    href="{{ route('learn.linking-devices') }}"
                    target="_blank"
                    rel="noreferrer"
                    variant="outline"
                    size="sm"
                    icon:trailing="arrow-right"
                    class="whitespace-nowrap bg-zinc-50">
                    Read device guide
                </flux:button>
            </div>

            <div class="space-y-6">
                <div class="grid gap-3 sm:grid-cols-2">
                    <button
                        type="button"
                        @click="setupPath = 'desktop'"
                        :class="setupPath === 'desktop'
                            ? 'border-blue-300 bg-blue-50 text-blue-900 ring-2 ring-blue-100'
                            : 'border-zinc-200 bg-white text-zinc-700 hover:border-zinc-300 hover:bg-zinc-50'"
                        class="w-full cursor-pointer rounded-2xl border px-4 py-4 text-left transition"
                    >
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <flux:icon.computer-desktop variant="solid" class="size-4" />
                                <p class="text-sm font-semibold">Desktop</p>
                            </div>
                            <div>
                                <p :class="setupPath === 'desktop' ? 'text-blue-800/80' : 'text-zinc-500'" class="mt-1 text-sm">
                                    Recommended on Mac.
                                </p>
                            </div>
                        </div>
                    </button>

                    <button
                        type="button"
                        @click="setupPath = 'cli'"
                        :class="setupPath === 'cli'
                            ? 'border-zinc-800 bg-zinc-950 text-white ring-2 ring-zinc-800/20'
                            : 'border-zinc-200 bg-white text-zinc-700 hover:border-zinc-300 hover:bg-zinc-50'"
                        class="w-full cursor-pointer rounded-2xl border px-4 py-4 text-left transition"
                    >
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <flux:icon.command-line variant="solid" class="size-4" />
                                <p class="text-sm font-semibold">CLI</p>
                            </div>
                            <div>
                                <p :class="setupPath === 'cli' ? 'text-zinc-300' : 'text-zinc-500'" class="mt-1 text-sm">
                                    Terminal setup.
                                </p>
                            </div>
                        </div>
                    </button>
                </div>

                <div class="grid">
                    <div
                        :class="setupPath === 'desktop' ? 'visible opacity-100' : 'invisible pointer-events-none opacity-0'"
                        class="col-start-1 row-start-1 space-y-8 transition-opacity duration-150"
                    >
                        <flux:timeline size="lg" align="start">
                            <flux:timeline.item>
                                <flux:timeline.indicator>1</flux:timeline.indicator>
                                <flux:timeline.content>
                                    <div class="space-y-2 pb-2">
                                        <flux:heading size="lg">Download Ghostable Desktop for macOS</flux:heading>
                                        <flux:text>Start with the desktop client app for the fastest setup on Mac.</flux:text>
                                        <div class="flex flex-wrap gap-3">
                                            <flux:button variant="primary" icon="apple" href="{{ route('desktop.download') }}">
                                                Download Desktop for macOS
                                            </flux:button>
                                        </div>
                                    </div>
                                </flux:timeline.content>
                            </flux:timeline.item>

                            <flux:timeline.item>
                                <flux:timeline.indicator>2</flux:timeline.indicator>
                                <flux:timeline.content>
                                    <div class="space-y-2">
                                        <flux:heading size="lg">Sign in and link this device</flux:heading>
                                        <flux:text>
                                            Open the desktop app, sign in with this account, and register this machine as your trusted device. Once that is done, organization setup unlocks automatically.
                                        </flux:text>
                                        <flux:text size="sm">Device keys are created locally in the macOS keychain and stay on your machine.</flux:text>
                                    </div>
                                </flux:timeline.content>
                            </flux:timeline.item>
                        </flux:timeline>
                    </div>

                    <div
                        :class="setupPath === 'cli' ? 'visible opacity-100' : 'invisible pointer-events-none opacity-0'"
                        class="col-start-1 row-start-1 space-y-8 transition-opacity duration-150"
                    >
                        <flux:timeline size="lg" align="start">
                            <flux:timeline.item>
                                <flux:timeline.indicator>1</flux:timeline.indicator>
                                <flux:timeline.content>
                                    <div class="space-y-2 pb-2">
                                        <flux:heading size="lg">Install the Ghostable CLI</flux:heading>
                                        <flux:text>Use the CLI if you are on Linux, Windows, or prefer the terminal.</flux:text>
                                        <flux:input
                                            class="w-full font-mono text-xs"
                                            value="npm install @ghostable/cli@latest"
                                            readonly
                                            copyable/>
                                    </div>
                                </flux:timeline.content>
                            </flux:timeline.item>

                            <flux:timeline.item>
                                <flux:timeline.indicator>2</flux:timeline.indicator>
                                <flux:timeline.content>
                                    <div class="space-y-2">
                                        <flux:heading size="lg">Authenticate and link this device</flux:heading>
                                        <flux:text>The CLI will guide you through sign-in and trusted device registration.</flux:text>
                                        <flux:input
                                            class="w-full font-mono text-xs"
                                            value="npx ghostable login"
                                            readonly
                                            copyable/>
                                    </div>
                                </flux:timeline.content>
                            </flux:timeline.item>
                        </flux:timeline>
                    </div>
                </div>

                <flux:callout icon="question-mark-circle" color="blue" inline>
                    <flux:callout.heading>Why this step?</flux:callout.heading>
                    <flux:callout.text>
                        Zero-knowledge isn’t a marketing term—it’s a hard rule: <b class="underline">we never see your keys</b>. Linking your device tells Ghostable which machine is allowed to decrypt workspace data. The dashboard will unlock automatically once your device is verified.
                    </flux:callout.text>
                </flux:callout>
            </div>
        </div>
    </flux:modal>
</div>
