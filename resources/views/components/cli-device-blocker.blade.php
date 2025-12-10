@props([
    'autoOpen' => true,
    'dismissible' => false,
    'closable' => false,
])

<div id="x-cli-device-blocker" x-data x-init="$nextTick(() => { if (@js($autoOpen)) $flux.modal('cli-device-blocker').show() })">
    <flux:modal
        name="cli-device-blocker"
        :dismissible="$dismissible"
        :closable="$closable"
        class="w-full max-w-2xl"
    >
        <div class="space-y-6">
            <div class="space-y-3 text-left">
                <flux:heading size="xl" class="font-semibold">Getting Started</flux:heading>
            </div>

            <div class="space-y-10">
                <div class="pl-8 ml-4 border-l border-dashed border-zinc-200">
                    <div class="flex -ml-12 items-center gap-4">
                        <div class="size-8 rounded-lg bg-zinc-100 text-sm font-semibold text-zinc-600 flex items-center justify-center">1</div>
                        <p class="text-base font-semibold text-zinc-900">Install the Ghostable CLI</p>
                    </div>
                    <p class="mb-5 text-zinc-600">Ghostable can be installed via npm from your project root:</p>
                    <div
                        x-data="{ copied: false, timer: null, copy() { navigator.clipboard?.writeText('npm install @ghostable/cli@latest'); this.copied = true; clearTimeout(this.timer); this.timer = setTimeout(() => this.copied = false, 1000); } }"
                        class="relative my-6 overflow-hidden rounded-2xl border border-zinc-200 bg-zinc-50 shadow-sm"
                    >
                        <button
                            type="button"
                            class="absolute top-3 right-3 inline-flex h-8 items-center rounded-md bg-white px-2 text-sm font-medium text-zinc-700 shadow ring-1 ring-zinc-200 hover:bg-zinc-50"
                            @click="copy"
                        >
                            <flux:tooltip content="Copy to clipboard">
                                <template x-if="!copied">
                                    <flux:icon.clipboard-document/>
                                </template>
                                <template x-if="copied">
                                    <flux:icon.check-circle class="text-emerald-600"/>
                                </template>
                            </flux:tooltip>
                        </button>
                        <pre class="overflow-x-auto px-4 py-4 text-sm font-mono text-zinc-900">npm install @ghostable/cli@latest</pre>
                    </div>
                </div>

                <div class="pl-8 ml-4 border-l border-dashed border-zinc-200">
                    <div class="flex -ml-12 items-center gap-4">
                        <div class="size-8 rounded-lg bg-zinc-100 text-sm font-semibold text-zinc-600 flex items-center justify-center">2</div>
                        <p class="text-base font-semibold text-zinc-900">Authenticate</p>
                    </div>
                    <p class="mb-5 text-zinc-600">On your first login, Ghostable will prompt you to link this device.</p>
                    <div
                        x-data="{ copied: false, timer: null, copy() { navigator.clipboard?.writeText('npx ghostable login'); this.copied = true; clearTimeout(this.timer); this.timer = setTimeout(() => this.copied = false, 1000); } }"
                        class="relative my-6 overflow-hidden rounded-2xl border border-zinc-200 bg-zinc-50 shadow-sm"
                    >
                        <button
                            type="button"
                            class="absolute top-3 right-3 inline-flex h-8 items-center rounded-md bg-white px-2 text-sm font-medium text-zinc-700 shadow ring-1 ring-zinc-200 hover:bg-zinc-50"
                            @click="copy"
                        >
                            <flux:tooltip content="Copy to clipboard">
                                <template x-if="!copied">
                                    <flux:icon.clipboard-document/>
                                </template>
                                <template x-if="copied">
                                    <flux:icon.check-circle/>
                                </template>
                            </flux:tooltip>
                        </button>
                        <pre class="overflow-x-auto px-4 py-4 text-sm font-mono text-zinc-900">npx ghostable login</pre>
                    </div>
                    <p>This establishes the keys that stay on your machine and never leave it.</p>
                </div>

                <flux:callout icon="question-mark-circle" color="blue" inline>
                    <flux:callout.heading>Why this step?</flux:callout.heading>
                    <flux:callout.text>
                        Zero-knowledge isn’t a marketing term—it’s a hard rule: <b class="underline">we never see your keys</b>. Linking your device tells Ghostable which machine is allowed to decrypt workspace data. The dashboard will unlock automatically once your device is verified.
                    </flux:callout.text>
                </flux:callout>
            </div>

            <div class="flex flex-wrap justify-center gap-3">
                <flux:button href="https://docs.ghostable.dev/v2/getting-started/installation" target="_blank" rel="noreferrer">Read setup docs</flux:button>
                <flux:button variant="ghost" href="https://www.npmjs.com/package/@ghostable/cli" target="_blank" rel="noreferrer">
                    View on npm
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
