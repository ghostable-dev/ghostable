@php
    $faqItems = [
        [
            'question' => 'Why not just keep environment variables in my CI/CD platform?',
            'answer' => 'Because deployment platforms are good at last-mile delivery, not day-to-day environment management. Ghostable gives your team a place to review, validate, edit, and track config before it gets handed off to automation.',
        ],
        [
            'question' => 'Do I need the CLI?',
            'answer' => 'No. Use the desktop app for daily work. Use the CLI for scripting, CI, deploy hooks, and non-macOS workflows.',
        ],
        [
            'question' => 'Does Ghostable work with my stack?',
            'answer' => 'Yes. Ghostable fits environment-driven workflows across Laravel, Node, Python, Ruby, Go, and similar stacks.',
        ],
        [
            'question' => 'Can I bring my existing .env files?',
            'answer' => 'Yes. Ghostable supports importing a local .env file and exporting an environment back to a local file.',
        ],
        [
            'question' => 'Can I validate config before deploy?',
            'answer' => 'Yes. Ghostable uses shared <code>.ghostable</code> schema files so the same rules can be used across desktop workflows and CLI-based automation.',
        ],
        [
            'question' => 'How does Ghostable stay zero-knowledge?',
            'answer' => 'Environment data is encrypted before it leaves a trusted client. Human access is tied to linked devices, and automation uses scoped deploy tokens.',
        ],
    ];

    $trustedClientPasswordSeed = 'q7M2x9Lp4Rk8Vn3D';

    $automationTranscript = [
        [
            'command' => 'ghostable env validate --env production',
            'output' => [
                '✅ Environment file passed validation.',
            ],
        ],
        [
            'command' => 'ghostable env deploy',
            'output' => [
                '✔ Bundle fetched.',
                '✅ Wrote 24 keys → /Users/developer/Projects/app/.env',
                'Ghostable 👻 deployed (local).',
            ],
        ],
    ];
@endphp

@push('meta')
    <x-seo-meta
        title="Ghostable Desktop | Desktop-First Environment Management"
        description="Review variables, validate changes, and track history without touching .env files."
        :keywords="[
            'desktop-first env management',
            'desktop secrets management',
            '.env management',
            '.env files',
            'environment variables',
            'config validation',
            'deploy tokens',
            'CLI',
            'zero-knowledge',
            'Ghostable Desktop'
        ]"
    />
    <x-faq-schema :items="$faqItems" />
@endpush

@push('head')
    <script>
        document.documentElement.classList.add('js');
    </script>

    <style>
        .js .trust-step-reveal [data-trust-part] {
            opacity: 0;
            transform: translate3d(0, 1.5rem, 0) scale(0.985);
            filter: blur(6px);
            transition-duration: 720ms;
            transition-property: opacity, transform, filter;
            transition-timing-function: cubic-bezier(0.22, 1, 0.36, 1);
            transition-delay: var(--trust-delay, 0ms);
        }

        .js .trust-step-reveal [data-trust-part="number"] {
            transform: translate3d(0, 1rem, 0) scale(0.94);
        }

        .js .trust-step-reveal.is-visible [data-trust-part] {
            opacity: 1;
            transform: translate3d(0, 0, 0) scale(1);
            filter: blur(0);
        }

        .js .trust-step-reveal.is-instant [data-trust-part] {
            transition-duration: 0ms;
        }

        .js [data-typed-cursor],
        .js [data-terminal-cursor] {
            opacity: 0;
            color: rgba(161, 161, 170, 0.72);
        }

        .js .trust-step-reveal.is-active [data-typed-cursor],
        .js .trust-step-reveal.is-active [data-terminal-cursor] {
            animation: ghostable-typed-cursor 1s steps(1, end) infinite;
            color: var(--color-brand);
            opacity: 1;
        }

        .js [data-terminal-prompt] {
            color: rgba(161, 161, 170, 0.72);
            transition: color 220ms ease;
        }

        .js .trust-step-reveal.is-active [data-terminal-prompt] {
            color: var(--color-brand);
        }

        .js [data-terminal-heading] {
            color: rgba(244, 244, 245, 0.88);
            transition: color 220ms ease;
        }

        .js [data-terminal-heading-icon] {
            color: rgba(113, 113, 122, 1);
            transition: color 220ms ease;
        }

        .js .trust-step-reveal.is-active [data-terminal-heading],
        .js .trust-step-reveal.is-active [data-terminal-heading-icon] {
            color: var(--color-brand);
        }

        [data-terminal-viewport] {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        [data-terminal-viewport]::-webkit-scrollbar {
            display: none;
        }

        .js [data-trust-focus-surface] {
            border-color: rgba(255, 255, 255, 0.12);
            box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.04), 0 10px 24px rgba(0, 0, 0, 0.14);
            transition-duration: 260ms;
            transition-property: border-color, box-shadow;
            transition-timing-function: ease;
        }

        .js .trust-step-reveal.is-active [data-trust-focus-surface] {
            border-color: color-mix(in srgb, var(--color-brand) 72%, transparent);
            box-shadow: 0 0 0 1px color-mix(in srgb, var(--color-brand) 18%, transparent), 0 12px 28px color-mix(in srgb, var(--color-brand) 12%, transparent);
        }

        .js [data-encrypted-sync-demo] [data-sync-track] {
            stroke: rgba(161, 161, 170, 0.24);
            transition-duration: 320ms;
            transition-property: stroke, opacity, filter;
            transition-timing-function: ease;
        }

        .js .trust-step-reveal.is-active [data-encrypted-sync-demo] [data-sync-track] {
            filter: drop-shadow(0 0 12px color-mix(in srgb, var(--color-brand) 16%, transparent));
            stroke: color-mix(in srgb, var(--color-brand) 26%, transparent);
        }

        .js [data-encrypted-sync-demo] [data-sync-particle] {
            fill: rgba(212, 212, 216, 0.84);
            opacity: 0;
            transition-duration: 220ms;
            transition-property: opacity, fill;
            transition-timing-function: ease;
        }

        .js .trust-step-reveal.is-active [data-encrypted-sync-demo] [data-sync-particle] {
            fill: var(--color-brand-light);
            opacity: 1;
        }

        .js [data-encrypted-sync-focus-card] {
            transition-duration: 260ms;
            transition-property: border-color, box-shadow, transform;
            transition-timing-function: ease;
        }

        .js .trust-step-reveal.is-active [data-encrypted-sync-focus-card] {
            border-color: color-mix(in srgb, var(--color-brand) 36%, transparent);
            box-shadow: 0 0 0 2px color-mix(in srgb, var(--color-brand) 20%, transparent), 0 22px 44px color-mix(in srgb, var(--color-brand) 14%, transparent), 0 8px 16px rgba(0, 0, 0, 0.3);
            transform: translate3d(0, -2px, 0);
        }

        .js .positioning-step-reveal {
            opacity: 0;
            transform: translate3d(0, 2rem, 0) scale(0.985);
            filter: blur(8px);
            transition-duration: 760ms;
            transition-property: opacity, transform, filter;
            transition-timing-function: cubic-bezier(0.22, 1, 0.36, 1);
        }

        .js .positioning-step-reveal.is-visible {
            opacity: 1;
            transform: translate3d(0, 0, 0) scale(1);
            filter: blur(0);
        }

        .js [data-positioning-surface] {
            border-color: rgba(228, 228, 231, 0.92);
            box-shadow: 0 18px 50px rgba(15, 23, 42, 0.08);
            transition: border-color 260ms ease, box-shadow 260ms ease, transform 260ms ease;
        }

        .js .positioning-step-reveal.is-active [data-positioning-surface] {
            border-color: color-mix(in srgb, var(--color-brand) 28%, transparent);
            box-shadow: 0 30px 90px color-mix(in srgb, var(--color-brand) 8%, transparent);
            transform: translate3d(0, -2px, 0);
        }

        .js [data-positioning-chat-thread].positioning-step-reveal {
            opacity: 1;
            transform: none;
            filter: none;
            transition: none;
        }

        .js [data-positioning-chat-thread] [data-positioning-chat-item] {
            opacity: 0;
            filter: blur(6px);
            transform: translate3d(0, calc(var(--positioning-parallax-y, 0px) + 1.75rem), 0) rotate(var(--positioning-rotation, 0deg));
            transition-duration: 720ms;
            transition-property: opacity, transform, filter;
            transition-timing-function: cubic-bezier(0.22, 1, 0.36, 1);
            transition-delay: var(--positioning-chat-delay, 0ms);
        }

        .js [data-positioning-chat-thread].is-visible [data-positioning-chat-item] {
            opacity: 1;
            filter: blur(0);
            transform: translate3d(0, var(--positioning-parallax-y, 0px), 0) rotate(var(--positioning-rotation, 0deg));
        }

        .js [data-positioning-float] {
            transform: translate3d(0, var(--positioning-parallax-y, 0px), 0) rotate(var(--positioning-rotation, 0deg));
            transition: transform 260ms ease;
            will-change: transform;
        }

        [data-walter-eye-overlay] {
            --walter-eye-mouse-x: 0px;
            --walter-eye-mouse-y: 0px;
            transform: translate3d(
                var(--walter-eye-mouse-x),
                var(--walter-eye-mouse-y),
                0
            );
            transition: transform 150ms ease-out;
            will-change: transform;
        }

        @keyframes ghostable-typed-cursor {
            0%, 49% {
                opacity: 1;
            }

            50%, 100% {
                opacity: 0;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .js .trust-step-reveal [data-trust-part] {
                opacity: 1;
                transform: none;
                filter: none;
                transition: none;
            }

            .js [data-typed-cursor],
            .js [data-terminal-cursor] {
                animation: none;
            }

            .js [data-encrypted-sync-demo] [data-sync-particle] {
                display: none;
            }

            .js .positioning-step-reveal {
                opacity: 1;
                transform: none;
                filter: none;
                transition: none;
            }

            .js [data-positioning-chat-thread] [data-positioning-chat-item] {
                opacity: 1;
                filter: none;
                transform: none;
                transition: none;
            }

            .js [data-positioning-float] {
                transform: rotate(var(--positioning-rotation, 0deg));
                transition: none;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            const animationControllers = [];
            const typedValue = document.querySelector('[data-typed-value]');
            const positioningSteps = document.querySelectorAll('[data-positioning-step]');
            const positioningFloatLayers = document.querySelectorAll('[data-positioning-float]');
            const positioningContrast = document.querySelector('[data-positioning-contrast]');
            const walterEyeStage = document.querySelector('[data-walter-eye-stage]');
            const walterEyeFigure = walterEyeStage?.querySelector('[data-walter-eye-figure]');

            const showAllPositioningSteps = () => {
                positioningSteps.forEach((positioningStep) => {
                    positioningStep.classList.add('is-visible');
                });
            };

            const showPositioningStep = (positioningStep) => {
                positioningStep.classList.add('is-visible');
            };

            const hidePositioningStep = (positioningStep) => {
                positioningStep.classList.remove('is-visible');
            };

            let activePositioningStep = null;

            const setActivePositioningStep = (nextActivePositioningStep) => {
                if (activePositioningStep === nextActivePositioningStep) {
                    return;
                }

                activePositioningStep = nextActivePositioningStep;

                positioningSteps.forEach((positioningStep) => {
                    positioningStep.classList.toggle('is-active', positioningStep === activePositioningStep);
                });
            };

            const updateActivePositioningStep = () => {
                const viewportCenter = window.innerHeight / 2;
                const activeBandTop = window.innerHeight * 0.24;
                const activeBandBottom = window.innerHeight * 0.76;
                let nextActivePositioningStep = null;
                let smallestDistance = Number.POSITIVE_INFINITY;

                positioningSteps.forEach((positioningStep) => {
                    if (!positioningStep.classList.contains('is-visible')) {
                        return;
                    }

                    const positioningStepRect = positioningStep.getBoundingClientRect();
                    const positioningStepCenter = positioningStepRect.top + (positioningStepRect.height / 2);

                    if (positioningStepCenter < activeBandTop || positioningStepCenter > activeBandBottom) {
                        return;
                    }

                    const distanceFromViewportCenter = Math.abs(positioningStepCenter - viewportCenter);

                    if (distanceFromViewportCenter < smallestDistance) {
                        smallestDistance = distanceFromViewportCenter;
                        nextActivePositioningStep = positioningStep;
                    }
                });

                setActivePositioningStep(nextActivePositioningStep);
            };

            const updatePositioningParallax = () => {
                if (prefersReducedMotion || !positioningContrast) {
                    positioningFloatLayers.forEach((positioningFloatLayer) => {
                        positioningFloatLayer.style.setProperty('--positioning-parallax-y', '0px');
                    });

                    return;
                }

                const contrastRect = positioningContrast.getBoundingClientRect();
                const contrastCenter = contrastRect.top + (contrastRect.height / 2);
                const viewportCenter = window.innerHeight / 2;
                const normalizedOffset = (viewportCenter - contrastCenter) / Math.max(window.innerHeight, 1);

                positioningFloatLayers.forEach((positioningFloatLayer) => {
                    const parallaxDepth = Number.parseFloat(positioningFloatLayer.dataset.positioningDepth ?? '0');
                    const parallaxOffset = Math.max(Math.min(normalizedOffset * parallaxDepth * 220, 32), -32);

                    positioningFloatLayer.style.setProperty('--positioning-parallax-y', `${parallaxOffset.toFixed(2)}px`);
                });
            };

            const walterEyeOverlay = walterEyeStage?.querySelector('[data-walter-eye-overlay]');
            let walterEyeMouseOffsetX = 0;
            let walterEyeMouseOffsetY = 0;

            const applyWalterEyeOffsets = () => {
                if (!walterEyeOverlay) {
                    return;
                }

                walterEyeOverlay.style.setProperty('--walter-eye-mouse-x', `${walterEyeMouseOffsetX}px`);
                walterEyeOverlay.style.setProperty('--walter-eye-mouse-y', `${walterEyeMouseOffsetY}px`);
            };

            if (walterEyeFigure && !prefersReducedMotion) {
                const walterEyeMaxTravel = 5;
                const walterEyeClamp = (value, minimum, maximum) => Math.min(Math.max(value, minimum), maximum);
                const walterEyeFigureIsVisible = () => {
                    const walterEyeRect = walterEyeFigure.getBoundingClientRect();

                    return walterEyeRect.bottom > 0
                        && walterEyeRect.top < window.innerHeight
                        && walterEyeRect.right > 0
                        && walterEyeRect.left < window.innerWidth;
                };

                const updateWalterEyePointer = (clientX, clientY) => {
                    if (!walterEyeFigureIsVisible()) {
                        walterEyeMouseOffsetX = 0;
                        walterEyeMouseOffsetY = 0;
                        applyWalterEyeOffsets();

                        return;
                    }

                    const walterEyeRect = walterEyeFigure.getBoundingClientRect();
                    const walterEyeCenterX = walterEyeRect.left + (walterEyeRect.width / 2);
                    const walterEyeCenterY = walterEyeRect.top + (walterEyeRect.height / 2);
                    const normalizedX = (clientX - walterEyeCenterX) / Math.max(walterEyeRect.width / 2, 1);
                    const normalizedY = (clientY - walterEyeCenterY) / Math.max(walterEyeRect.height / 2, 1);

                    walterEyeMouseOffsetX = walterEyeClamp(normalizedX * walterEyeMaxTravel, -walterEyeMaxTravel, walterEyeMaxTravel);
                    walterEyeMouseOffsetY = walterEyeClamp(normalizedY * walterEyeMaxTravel, -walterEyeMaxTravel, walterEyeMaxTravel);
                    applyWalterEyeOffsets();
                };

                window.addEventListener('pointermove', (event) => {
                    updateWalterEyePointer(event.clientX, event.clientY);
                });

                window.addEventListener('pointerleave', () => {
                    walterEyeMouseOffsetX = 0;
                    walterEyeMouseOffsetY = 0;
                    applyWalterEyeOffsets();
                });

                window.addEventListener('scroll', () => {
                    if (walterEyeFigureIsVisible()) {
                        return;
                    }

                    if (walterEyeMouseOffsetX === 0 && walterEyeMouseOffsetY === 0) {
                        return;
                    }

                    walterEyeMouseOffsetX = 0;
                    walterEyeMouseOffsetY = 0;
                    applyWalterEyeOffsets();
                }, { passive: true });
            }

            applyWalterEyeOffsets();

            if (typedValue) {
                const typedTrustStep = typedValue.closest('[data-trust-step]');
                const typedLength = Number.parseInt(typedValue.dataset.typedLength ?? '16', 10);
                const typedAlphabet = typedValue.dataset.typedAlphabet ?? 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
                const createRandomTypedValue = (length) => {
                    const characters = [];

                    if (window.crypto?.getRandomValues) {
                        const randomValues = new Uint32Array(length);

                        window.crypto.getRandomValues(randomValues);

                        randomValues.forEach((randomValue) => {
                            characters.push(typedAlphabet[randomValue % typedAlphabet.length]);
                        });
                    } else {
                        for (let index = 0; index < length; index += 1) {
                            characters.push(typedAlphabet[Math.floor(Math.random() * typedAlphabet.length)]);
                        }
                    }

                    return characters.join('');
                };
                const nextRandomTypedValue = (currentValue = '') => {
                    let nextValue = currentValue;
                    let attempts = 0;

                    while (nextValue === currentValue && attempts < 5) {
                        nextValue = createRandomTypedValue(typedLength);
                        attempts += 1;
                    }

                    return nextValue;
                };
                let currentValue = typedValue.dataset.typedSeed ?? nextRandomTypedValue();

                typedValue.textContent = currentValue;

                if (!prefersReducedMotion) {
                    const typedController = {
                        characterIndex: currentValue.length,
                        hasStarted: false,
                        isDeleting: false,
                        isPlaying: false,
                        stepElement: typedTrustStep,
                        timeoutId: null,
                        schedule(delay) {
                            this.timeoutId = window.setTimeout(() => {
                                if (!this.isPlaying) {
                                    return;
                                }

                                typedValue.textContent = currentValue.slice(0, this.characterIndex);

                                let nextDelay = this.isDeleting ? 48 : 82;

                                if (!this.isDeleting && this.characterIndex === currentValue.length) {
                                    this.isDeleting = true;
                                    nextDelay = 1350;
                                } else if (this.isDeleting && this.characterIndex === 0) {
                                    this.isDeleting = false;
                                    currentValue = nextRandomTypedValue(currentValue);
                                    nextDelay = 260;
                                } else {
                                    this.characterIndex += this.isDeleting ? -1 : 1;
                                }

                                this.schedule(nextDelay);
                            }, delay);
                        },
                    start() {
                        if (this.isPlaying) {
                            return;
                        }

                        if (!this.isDeleting && this.characterIndex === currentValue.length) {
                            this.isDeleting = true;
                            this.characterIndex = Math.max(this.characterIndex - 1, 0);
                        }

                        this.isPlaying = true;
                        this.schedule(this.hasStarted ? 120 : 240);
                        this.hasStarted = true;
                    },
                        stop() {
                            this.isPlaying = false;
                            window.clearTimeout(this.timeoutId);
                            this.timeoutId = null;
                        },
                    };

                    animationControllers.push(typedController);
                }
            }

            const encryptedSyncDemo = document.querySelector('[data-encrypted-sync-demo]');

            if (encryptedSyncDemo) {
                const syncTrackElements = [...encryptedSyncDemo.querySelectorAll('[data-sync-track]')];
                const syncTrackMap = new Map(syncTrackElements.map((syncTrackElement) => [syncTrackElement.id, syncTrackElement]));
                const syncParticles = [...encryptedSyncDemo.querySelectorAll('[data-sync-particle]')]
                    .map((syncParticleElement) => {
                        const syncTrackElement = syncTrackMap.get(syncParticleElement.dataset.syncTrackRef ?? '');

                        if (!syncTrackElement) {
                            return null;
                        }

                        return {
                            element: syncParticleElement,
                            path: syncTrackElement,
                            length: syncTrackElement.getTotalLength(),
                            baseRadius: Number.parseFloat(syncParticleElement.dataset.syncBaseRadius ?? syncParticleElement.getAttribute('r') ?? '3.2'),
                            direction: syncParticleElement.dataset.syncDirection === 'reverse' ? -1 : 1,
                            phase: Number.parseFloat(syncParticleElement.dataset.syncPhase ?? '0'),
                            speed: Number.parseFloat(syncParticleElement.dataset.syncSpeed ?? '0.09'),
                        };
                    })
                    .filter(Boolean);

                if (!prefersReducedMotion && syncParticles.length) {
                    const syncController = {
                        frameId: null,
                        isPlaying: false,
                        renderFrame: null,
                        startedAt: 0,
                        stepElement: encryptedSyncDemo.closest('[data-trust-step]'),
                        start() {
                            if (this.isPlaying) {
                                return;
                            }

                            this.isPlaying = true;
                            this.startedAt = 0;
                            this.frameId = window.requestAnimationFrame(this.renderFrame);
                        },
                        stop() {
                            this.isPlaying = false;

                            if (this.frameId) {
                                window.cancelAnimationFrame(this.frameId);
                                this.frameId = null;
                            }

                            syncParticles.forEach((syncParticle) => {
                                syncParticle.element.style.opacity = '0';
                                syncParticle.element.setAttribute('cx', '720');
                                syncParticle.element.setAttribute('cy', '208');
                                syncParticle.element.setAttribute('r', syncParticle.baseRadius.toFixed(2));
                            });
                        },
                    };

                    syncController.renderFrame = (timestamp) => {
                        if (!syncController.isPlaying) {
                            return;
                        }

                        if (!syncController.startedAt) {
                            syncController.startedAt = timestamp;
                        }

                        const elapsedSeconds = (timestamp - syncController.startedAt) / 1000;

                        syncParticles.forEach((syncParticle, index) => {
                            const cycle = ((elapsedSeconds * syncParticle.speed) + syncParticle.phase) % 1;
                            const progress = syncParticle.direction === 1 ? cycle : 1 - cycle;
                            const point = syncParticle.path.getPointAtLength(progress * syncParticle.length);
                            const edgeFade = Math.sin(progress * Math.PI);
                            const shimmer = 0.78 + (0.24 * Math.sin((elapsedSeconds * 3.1) + (index * 0.85)));

                            syncParticle.element.setAttribute('cx', point.x.toFixed(1));
                            syncParticle.element.setAttribute('cy', point.y.toFixed(1));
                            syncParticle.element.setAttribute('r', (syncParticle.baseRadius * shimmer).toFixed(2));
                            syncParticle.element.style.opacity = Math.max(edgeFade, 0.16).toFixed(3);
                        });

                        syncController.frameId = window.requestAnimationFrame(syncController.renderFrame);
                    };

                    syncController.stop();
                    animationControllers.push(syncController);
                }
            }

            const terminalDemos = document.querySelectorAll('[data-terminal-demo]');

            terminalDemos.forEach((terminalDemo) => {
                const transcript = JSON.parse(terminalDemo.dataset.terminalScript ?? '[]');
                const terminalViewport = terminalDemo.querySelector('[data-terminal-viewport]');
                const terminalLines = terminalDemo.querySelector('[data-terminal-lines]');
                const terminalCommand = terminalDemo.querySelector('[data-terminal-command]');
                const terminalTrustStep = terminalDemo.closest('[data-trust-step]');

                if (!terminalViewport || !terminalLines || !terminalCommand || !transcript.length) {
                    return;
                }

                const pinTerminalViewport = (behavior = 'auto') => {
                    const shouldAnimateScroll = behavior === 'smooth' && !prefersReducedMotion;

                    if (shouldAnimateScroll) {
                        terminalViewport.scrollTo({
                            top: terminalViewport.scrollHeight,
                            behavior: 'smooth',
                        });

                        return;
                    }

                    terminalViewport.scrollTop = terminalViewport.scrollHeight;
                };

                const appendCommandLine = (command) => {
                    const line = document.createElement('div');
                    const prompt = document.createElement('span');
                    const commandText = document.createElement('span');

                    line.className = 'flex items-start gap-2 text-white/88';
                    prompt.className = 'shrink-0';
                    prompt.dataset.terminalPrompt = '';
                    prompt.textContent = '$';
                    commandText.textContent = command;
                    line.append(prompt, commandText);
                    terminalLines.append(line);
                    pinTerminalViewport('smooth');
                };

                const appendOutputLine = (message) => {
                    const line = document.createElement('div');

                    line.className = 'pl-5 text-zinc-400';
                    line.textContent = message;
                    terminalLines.append(line);
                    pinTerminalViewport('smooth');
                };

                const renderFullTranscript = () => {
                    terminalLines.innerHTML = '';
                    terminalCommand.textContent = '';

                    transcript.forEach((step) => {
                        appendCommandLine(step.command);
                        step.output.forEach((message) => appendOutputLine(message));
                    });

                    pinTerminalViewport();
                };

                if (prefersReducedMotion) {
                    renderFullTranscript();

                    return;
                }

                const terminalController = {
                    characterIndex: 0,
                    hasStarted: false,
                    isPlaying: false,
                    isResetting: false,
                    isShowingOutput: false,
                    outputIndex: 0,
                    stepElement: terminalTrustStep,
                    stepIndex: 0,
                    timeoutId: null,
                    reset() {
                        terminalLines.innerHTML = '';
                        terminalCommand.textContent = '';
                        this.stepIndex = 0;
                        this.characterIndex = 0;
                        this.outputIndex = 0;
                        this.isShowingOutput = false;
                        this.isResetting = false;
                        terminalViewport.scrollTop = 0;
                    },
                    schedule(delay) {
                        this.timeoutId = window.setTimeout(() => {
                            if (!this.isPlaying) {
                                return;
                            }

                            if (this.isResetting) {
                                this.reset();
                                this.schedule(850);

                                return;
                            }

                            const currentStep = transcript[this.stepIndex];

                            if (!currentStep) {
                                this.isResetting = true;
                                this.schedule(1500);

                                return;
                            }

                            if (!this.isShowingOutput) {
                                terminalCommand.textContent = currentStep.command.slice(0, this.characterIndex);
                                pinTerminalViewport();

                                if (this.characterIndex < currentStep.command.length) {
                                    this.characterIndex += 1;
                                    this.schedule(34);

                                    return;
                                }

                                appendCommandLine(currentStep.command);
                                terminalCommand.textContent = '';
                                this.isShowingOutput = true;
                                this.outputIndex = 0;
                                this.schedule(220);

                                return;
                            }

                            if (this.outputIndex < currentStep.output.length) {
                                appendOutputLine(currentStep.output[this.outputIndex]);
                                this.outputIndex += 1;
                                this.schedule(200);

                                return;
                            }

                            this.stepIndex += 1;
                            this.characterIndex = 0;
                            this.isShowingOutput = false;
                            this.schedule(480);
                        }, delay);
                    },
                    start() {
                        if (this.isPlaying) {
                            return;
                        }

                        if (this.hasStarted) {
                            this.reset();
                        }

                        this.isPlaying = true;
                        this.schedule(this.hasStarted ? 180 : 380);
                        this.hasStarted = true;
                    },
                    stop() {
                        this.isPlaying = false;
                        window.clearTimeout(this.timeoutId);
                        this.timeoutId = null;
                    },
                };

                terminalController.reset();
                animationControllers.push(terminalController);
            });

            const trustSteps = document.querySelectorAll('[data-trust-step]');

            const showAllTrustSteps = () => {
                trustSteps.forEach((trustStep) => {
                    trustStep.classList.remove('is-instant');
                    trustStep.classList.add('is-visible');
                });
            };

            const showTrustStep = (trustStep, animate = true) => {
                if (!animate) {
                    trustStep.classList.add('is-instant');
                }

                trustStep.classList.add('is-visible');

                if (!animate) {
                    requestAnimationFrame(() => {
                        requestAnimationFrame(() => {
                            trustStep.classList.remove('is-instant');
                        });
                    });
                }
            };

            const hideTrustStep = (trustStep) => {
                trustStep.classList.remove('is-instant', 'is-visible');
            };

            let activeTrustStep = null;

            const setActiveTrustStep = (nextActiveTrustStep) => {
                if (activeTrustStep === nextActiveTrustStep) {
                    return;
                }

                if (activeTrustStep) {
                    activeTrustStep.classList.remove('is-active');
                }

                activeTrustStep = nextActiveTrustStep;

                trustSteps.forEach((trustStep) => {
                    trustStep.classList.toggle('is-active', trustStep === activeTrustStep);
                });

                animationControllers.forEach((animationController) => {
                    if (animationController.stepElement === activeTrustStep) {
                        animationController.start();

                        return;
                    }

                    animationController.stop();
                });
            };

            const updateActiveTrustStep = () => {
                const viewportCenter = window.innerHeight / 2;
                const activeBandTop = window.innerHeight * 0.28;
                const activeBandBottom = window.innerHeight * 0.72;
                let nextActiveTrustStep = null;
                let smallestDistance = Number.POSITIVE_INFINITY;

                trustSteps.forEach((trustStep) => {
                    const trustStepRect = trustStep.getBoundingClientRect();

                    if (!trustStep.classList.contains('is-visible')) {
                        return;
                    }

                    const trustStepCenter = trustStepRect.top + (trustStepRect.height / 2);

                    if (trustStepCenter < activeBandTop || trustStepCenter > activeBandBottom) {
                        return;
                    }

                    const distanceFromViewportCenter = Math.abs(trustStepCenter - viewportCenter);

                    if (distanceFromViewportCenter < smallestDistance) {
                        smallestDistance = distanceFromViewportCenter;
                        nextActiveTrustStep = trustStep;
                    }
                });

                setActiveTrustStep(nextActiveTrustStep);
            };

            if (prefersReducedMotion || !('IntersectionObserver' in window)) {
                showAllPositioningSteps();
                updatePositioningParallax();

                if (!trustSteps.length) {
                    return;
                }

                showAllTrustSteps();

                return;
            }

            let hasQueuedActiveTrustStepUpdate = false;

            const queueActiveTrustStepUpdate = () => {
                if (hasQueuedActiveTrustStepUpdate) {
                    return;
                }

                hasQueuedActiveTrustStepUpdate = true;

                window.requestAnimationFrame(() => {
                    hasQueuedActiveTrustStepUpdate = false;
                    updateActiveTrustStep();
                });
            };

            let hasQueuedActivePositioningStepUpdate = false;

            const queueActivePositioningStepUpdate = () => {
                if (hasQueuedActivePositioningStepUpdate) {
                    return;
                }

                hasQueuedActivePositioningStepUpdate = true;

                window.requestAnimationFrame(() => {
                    hasQueuedActivePositioningStepUpdate = false;
                    updateActivePositioningStep();
                });
            };

            let lastScrollY = window.scrollY;
            let scrollDirection = 'down';

            window.addEventListener('scroll', () => {
                const currentScrollY = window.scrollY;

                if (currentScrollY === lastScrollY) {
                    return;
                }

                scrollDirection = currentScrollY > lastScrollY ? 'down' : 'up';
                lastScrollY = currentScrollY;
                queueActivePositioningStepUpdate();
                queueActiveTrustStepUpdate();
                updatePositioningParallax();
            }, { passive: true });

            window.addEventListener('resize', () => {
                queueActivePositioningStepUpdate();
                queueActiveTrustStepUpdate();
                updatePositioningParallax();
                applyWalterEyeOffsets();
            });

            const positioningStepObserver = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        showPositioningStep(entry.target);
                        queueActivePositioningStepUpdate();

                        return;
                    }

                    if (scrollDirection === 'up') {
                        hidePositioningStep(entry.target);
                    }

                    queueActivePositioningStepUpdate();
                });
            }, {
                threshold: 0.24,
                rootMargin: '-4% 0px -10% 0px',
            });

            positioningSteps.forEach((positioningStep) => {
                positioningStepObserver.observe(positioningStep);
            });

            queueActivePositioningStepUpdate();
            updatePositioningParallax();

            if (!trustSteps.length) {
                return;
            }

            const trustStepObserver = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        showTrustStep(entry.target, scrollDirection === 'down');
                        queueActiveTrustStepUpdate();

                        return;
                    }

                    if (scrollDirection === 'up') {
                        hideTrustStep(entry.target);
                    }

                    queueActiveTrustStepUpdate();
                });
            }, {
                threshold: 0.28,
                rootMargin: '-6% 0px -12% 0px',
            });

            trustSteps.forEach((trustStep) => {
                trustStepObserver.observe(trustStep);
            });

            queueActivePositioningStepUpdate();
            queueActiveTrustStepUpdate();
            updatePositioningParallax();
        });
    </script>
@endpush

<x-layouts.guest
    title="Desktop-First Environment Management"
    canonical="{{ route('home') }}"
    :show-promo-banner="false"
>
    <div class="bg-white text-zinc-950">
        <section class="overflow-hidden border-b border-white/10 bg-[linear-gradient(180deg,#090b11_0%,#07090d_100%)] text-white">
            <div
                class="pointer-events-none absolute inset-x-0 top-0 h-[38rem] bg-[radial-gradient(62%_42%_at_50%_0%,color-mix(in_srgb,var(--color-brand)_18%,transparent),transparent_72%)]"
            ></div>

            <div class="relative mx-auto max-w-7xl px-6 pb-0 pt-20 sm:pt-24 lg:px-8 lg:pt-28">
                <div class="max-w-5xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-zinc-400">
                        Stop passing around .env files.
                    </p>

                    <h1 class="mt-6 max-w-5xl text-5xl font-medium tracking-[-0.07em] text-white sm:text-6xl sm:leading-[0.94] lg:text-[5.5rem] lg:leading-[0.92]">
                        <span class="lg:block">Desktop-First</span>
                        <span class="lg:block">Environment Management</span>
                    </h1>

                    <p class="mt-8 max-w-3xl text-lg leading-8 text-zinc-300 sm:text-xl">
                        Review variables, validate changes, and track history without touching .env files.
                    </p>

                    <div class="mt-10 flex flex-wrap items-center gap-4">
                        <a
                            href="{{ route('desktop.download') }}"
                            class="inline-flex items-center justify-center rounded-lg bg-brand px-6 py-3 text-base font-semibold text-white shadow-[0_14px_36px_color-mix(in_srgb,var(--color-brand)_28%,transparent)] transition hover:-translate-y-0.5 hover:shadow-[0_16px_40px_color-mix(in_srgb,var(--color-brand)_34%,transparent)]"
                        >
                            Download Desktop for macOS
                        </a>

                        <flux:button
                            variant="ghost"
                            class="!border !border-white/15 !bg-white/5 !px-6 !py-3 !text-base !font-semibold !text-white hover:!bg-white/10"
                            href="{{ route('register') }}"
                        >
                            Sign up
                        </flux:button>
                    </div>

                </div>

                @php
                    $heroWorkspaceRows = [
                        ['key' => 'APP_DEBUG', 'value' => 'true'],
                        ['key' => 'APP_ENV', 'value' => 'local', 'active' => true],
                        ['key' => 'APP_FAKER_LOCALE', 'value' => 'en_US'],
                        ['key' => 'APP_FALLBACK_LOCALE', 'value' => 'en'],
                        ['key' => 'APP_KEY', 'value' => '••••••'],
                        ['key' => 'APP_LOCALE', 'value' => 'en'],
                        ['key' => 'APP_MAINTENANCE_DRIVER', 'value' => 'file'],
                        ['key' => 'APP_NAME', 'value' => 'Ghostable'],
                        ['key' => 'APP_URL', 'value' => 'https://ghostable.dev'],
                        ['key' => 'AWS_ACCESS_KEY_ID', 'value' => '••••••'],
                        ['key' => 'AWS_BUCKET', 'value' => 'app-assets'],
                        ['key' => 'AWS_DEFAULT_REGION', 'value' => 'us-east-1'],
                        ['key' => 'AWS_SECRET_ACCESS_KEY', 'value' => '••••••'],
                        ['key' => 'AWS_USE_PATH_STYLE_ENDPOINT', 'value' => 'false'],
                        ['key' => 'BCRYPT_ROUNDS', 'value' => '12'],
                        ['key' => 'BROADCAST_CONNECTION', 'value' => 'log'],
                        ['key' => 'CACHE_STORE', 'value' => 'database'],
                        ['key' => 'DB_CONNECTION', 'value' => 'sqlite'],
                        ['key' => 'FILESYSTEM_DISK', 'value' => 'local'],
                        ['key' => 'GHOSTABLE_API', 'value' => 'https://ghostable.test/api/v2'],
                        ['key' => 'GHOSTABLE_KEYCHAIN_PROFILE', 'value' => '••••••'],
                        ['key' => 'LOG_CHANNEL', 'value' => 'stack'],
                        ['key' => 'LOG_DEPRECATIONS_CHANNEL', 'value' => 'null'],
                        ['key' => 'LOG_LEVEL', 'value' => 'debug'],
                        ['key' => 'LOG_STACK', 'value' => 'single'],
                    ];
                    $heroWorkspaceColumns = '15rem minmax(0, 1fr) 26rem';
                    $heroTableColumns = '1.15fr 1.45fr 0.6fr 0.72fr';
                    $heroMetaColumns = 'minmax(0, 1fr) auto';
                @endphp

                <div class="relative left-1/2 mt-14 w-[min(calc(100vw-2rem),96rem)] -translate-x-1/2 [--hero-workspace-scale:0.3] sm:w-[min(calc(100vw-3rem),96rem)] sm:[--hero-workspace-scale:0.4] md:[--hero-workspace-scale:0.52] lg:[--hero-workspace-scale:0.67] xl:[--hero-workspace-scale:0.73] 2xl:[--hero-workspace-scale:0.75]" data-hero-workspace>
                    <div class="relative h-[calc(64rem*var(--hero-workspace-scale))] overflow-hidden rounded-t-[2rem] border border-white/12 bg-[#1a1a1a] shadow-[0_50px_120px_rgba(0,0,0,0.6)]">
                        <div class="absolute left-0 top-0 w-[2046px] origin-top-left scale-[var(--hero-workspace-scale)]">
                            <div class="grid min-h-[64rem]" style="grid-template-columns: {{ $heroWorkspaceColumns }};">
                                <aside data-hero-sidebar class="border-r border-white/10 bg-[#1b1b1b] px-3 pb-7 pt-5">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <span class="h-5 w-5 rounded-full bg-[#8f4642] shadow-[0_0_0_1px_rgba(0,0,0,0.16)]"></span>
                                        <span class="h-5 w-5 rounded-full bg-[#90733a] shadow-[0_0_0_1px_rgba(0,0,0,0.16)]"></span>
                                        <span class="h-5 w-5 rounded-full bg-[#45734e] shadow-[0_0_0_1px_rgba(0,0,0,0.16)]"></span>
                                    </div>

                                    <div class="grid h-8 w-8 place-items-center rounded-xl border border-white/10 bg-white/[0.04] text-zinc-500">
                                        <flux:icon.rectangle-group class="h-4 w-4"/>
                                    </div>
                                </div>

                                <div class="mt-10 space-y-1">
                                    <div class="flex items-center gap-4 px-3 py-2.5">
                                        <div class="grid h-9 w-9 place-items-center rounded-xl text-brand">
                                            <flux:icon.key class="h-5 w-5"/>
                                        </div>
                                        <span class="text-[1.08rem] font-semibold text-brand">Variables</span>
                                    </div>

                                    <div class="flex items-center gap-4 rounded-[1.35rem] px-3 py-2.5 text-zinc-200">
                                        <div class="grid h-9 w-9 place-items-center rounded-xl text-zinc-400">
                                            <flux:icon.clock class="h-5 w-5"/>
                                        </div>
                                        <span class="text-[1.08rem] font-semibold">Activity</span>
                                    </div>

                                    <div class="flex items-center gap-4 rounded-[1.35rem] px-3 py-2.5 text-zinc-200">
                                        <div class="grid h-9 w-9 place-items-center rounded-xl text-zinc-400">
                                            <flux:icon.check-badge class="h-5 w-5"/>
                                        </div>
                                        <span class="text-[1.08rem] font-semibold">Validation</span>
                                    </div>

                                    <div class="flex items-center gap-4 rounded-[1.35rem] px-3 py-2.5 text-zinc-200">
                                        <div class="grid h-9 w-9 place-items-center rounded-xl text-zinc-400">
                                            <flux:icon.command-line class="h-5 w-5"/>
                                        </div>
                                        <span class="text-[1.08rem] font-semibold">Deploy Tokens</span>
                                    </div>

                                    <div class="flex items-center gap-4 rounded-[1.35rem] px-3 py-2.5 text-zinc-200">
                                        <div class="grid h-9 w-9 place-items-center rounded-xl text-zinc-400">
                                            <flux:icon.cog-6-tooth class="h-5 w-5"/>
                                        </div>
                                        <span class="text-[1.08rem] font-semibold">Settings</span>
                                    </div>
                                </div>
                                </aside>

                                <section data-hero-table class="border-r border-white/10">
                                <div class="flex items-center justify-between gap-4 border-b border-white/10 px-6 py-4">
                                    <div class="min-w-0 text-[1.05rem] font-semibold tracking-[-0.03em] text-white/88 sm:text-[1.18rem]">
                                        <span class="truncate">Ghostable <span class="text-zinc-500">›</span> Apollo <span class="text-zinc-500">›</span> production</span>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <div class="flex items-center gap-2 rounded-full border border-white/10 bg-white/[0.03] p-1.5">
                                            <button class="grid h-10 w-10 place-items-center rounded-full text-zinc-300">
                                                <flux:icon.arrow-path class="h-5 w-5"/>
                                            </button>
                                            <button class="grid h-10 w-10 place-items-center rounded-full text-zinc-300">
                                                <flux:icon.arrow-up-tray class="h-5 w-5"/>
                                            </button>
                                            <button class="grid h-10 w-10 place-items-center rounded-full text-zinc-300">
                                                <flux:icon.plus class="h-5 w-5"/>
                                            </button>
                                        </div>

                                        <div class="grid h-11 w-11 place-items-center rounded-full border border-white/10 bg-white/[0.03] text-zinc-300">
                                            <flux:icon.rectangle-group class="h-5 w-5"/>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-5 px-6 py-5">
                                    <div class="flex items-center justify-between gap-4">
                                        <div class="flex h-12 w-[28rem] items-center gap-3 rounded-[1.15rem] border border-white/[0.05] bg-white/[0.07] px-4 text-zinc-400">
                                            <flux:icon.magnifying-glass class="h-5 w-5"/>
                                            <span class="text-[0.98rem] font-medium">Search by key</span>
                                        </div>

                                        <div class="flex items-center justify-end gap-3">
                                            <div class="inline-flex items-center rounded-xl bg-white/[0.08] p-1 text-[0.86rem] font-semibold text-zinc-400">
                                                <span class="rounded-lg bg-white/60 px-5 py-1.5 text-zinc-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.22)]">Table</span>
                                                <span class="px-4 py-1.5">Grouped</span>
                                            </div>

                                            <button class="grid h-10 w-10 place-items-center rounded-xl text-zinc-400">
                                                <flux:icon.adjustments-horizontal class="h-5 w-5"/>
                                            </button>

                                            <button class="grid h-10 w-10 place-items-center rounded-xl text-zinc-400">
                                                <flux:icon.chevron-down class="h-5 w-5"/>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="overflow-hidden rounded-[1.5rem] border border-white/10 bg-[#1d1d1d] shadow-[inset_0_1px_0_rgba(255,255,255,0.03)]">
                                        <div class="grid border-b border-white/10 px-5 py-3 text-left text-[0.74rem] font-semibold uppercase tracking-[0.14em] text-zinc-500" style="grid-template-columns: {{ $heroTableColumns }};">
                                            <span>Key</span>
                                            <span>Value</span>
                                            <span>Version</span>
                                            <span>Updated</span>
                                        </div>

                                        <div class="divide-y divide-white/[0.03]">
                                            @foreach ($heroWorkspaceRows as $row)
                                                <div @class([
                                                    'grid px-5 py-3 text-left text-[0.92rem] transition',
                                                    'bg-brand text-white shadow-[inset_0_1px_0_rgba(255,255,255,0.14)]' => $row['active'] ?? false,
                                                    'text-zinc-200 odd:bg-white/[0.04] even:bg-transparent' => ! ($row['active'] ?? false),
                                                ]) style="grid-template-columns: {{ $heroTableColumns }};">
                                                    <span class="truncate font-medium">{{ $row['key'] }}</span>
                                                    <span class="truncate {{ ($row['active'] ?? false) ? 'text-white/95' : 'text-zinc-300' }}">{{ $row['value'] }}</span>
                                                    <span class="{{ ($row['active'] ?? false) ? 'text-white/95' : 'text-zinc-400' }}">1</span>
                                                    <span class="{{ ($row['active'] ?? false) ? 'text-white/95' : 'text-zinc-400' }}">1 min ago</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                </section>

                                <aside data-hero-detail class="px-4 py-5">
                                <div class="flex items-center justify-between gap-4">
                                    <div class="grid h-11 w-11 place-items-center rounded-full border border-white/10 bg-white/[0.03] text-zinc-300">
                                        <flux:icon.rectangle-group class="h-5 w-5"/>
                                    </div>
                                </div>

                                <div class="mt-6 inline-flex w-full items-center rounded-full bg-white/[0.07] p-1 text-[0.86rem] font-semibold">
                                    <span class="flex-1 rounded-full bg-white/[0.12] px-3 py-2 text-center text-white">Info</span>
                                    <span class="flex-1 px-3 py-2 text-center text-zinc-500">Validation</span>
                                    <span class="flex-1 px-3 py-2 text-center text-zinc-500">History</span>
                                </div>

                                <div class="mt-6">
                                    <div class="text-[1.22rem] font-semibold text-white/90">Variable</div>
                                    <p class="mt-1 text-[0.9rem] text-zinc-400">Review and edit the selected environment variable.</p>
                                </div>

                                <div class="mt-4 overflow-hidden rounded-[1.5rem] border border-white/10 bg-white/[0.03]">
                                    <div class="grid items-center gap-4 border-b border-white/10 px-5 py-5" style="grid-template-columns: {{ $heroMetaColumns }};">
                                        <span class="text-[0.95rem] font-semibold text-white/88">Key</span>
                                        <span class="flex items-center gap-2 font-mono text-[0.92rem] text-zinc-300">
                                            <flux:icon.lock-closed class="h-4 w-4 text-zinc-500"/>
                                            APP_ENV
                                        </span>
                                    </div>

                                    <div class="border-b border-white/10 px-5 py-5">
                                        <div class="text-[0.95rem] font-semibold text-white/88">Value</div>
                                        <div class="mt-4 min-h-[8.5rem] rounded-[1.15rem] bg-white/[0.05] px-5 py-4 font-mono text-[1.02rem] text-white/92 shadow-[inset_0_1px_0_rgba(255,255,255,0.03)]">
                                            local
                                        </div>
                                        <div class="mt-4 text-[0.82rem] text-zinc-400">Current value stored for this environment variable.</div>
                                    </div>

                                    <div class="grid gap-4 px-5 py-5" style="grid-template-columns: {{ $heroMetaColumns }};">
                                        <div>
                                            <div class="text-[0.95rem] font-semibold text-white/88">Suggested Values</div>
                                            <div class="mt-2 text-[0.82rem] text-zinc-400">Common values for this variable.</div>
                                        </div>
                                        <div class="text-right font-mono text-[0.92rem] text-zinc-300">
                                            <div>local</div>
                                            <div>staging</div>
                                            <div>production</div>
                                            <div>testing</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-5 text-[1.05rem] font-semibold text-white/88">Details</div>

                                <div class="mt-4 overflow-hidden rounded-[1.5rem] border border-white/10 bg-white/[0.03]">
                                    <div class="grid gap-4 border-b border-white/10 px-5 py-5" style="grid-template-columns: {{ $heroMetaColumns }};">
                                        <span class="text-[0.95rem] font-semibold text-white/88">Version</span>
                                        <span class="text-zinc-300">1</span>
                                    </div>
                                    <div class="grid gap-4 border-b border-white/10 px-5 py-5" style="grid-template-columns: {{ $heroMetaColumns }};">
                                        <span class="text-[0.95rem] font-semibold text-white/88">Updated</span>
                                        <span class="text-zinc-300">Mar 19, 2026 at 4:01 PM</span>
                                    </div>
                                    <div class="grid gap-4 border-b border-white/10 px-5 py-5" style="grid-template-columns: {{ $heroMetaColumns }};">
                                        <span class="text-[0.95rem] font-semibold text-white/88">Updated By</span>
                                        <span class="text-zinc-300">will@ghostable.dev</span>
                                    </div>
                                    <div class="grid gap-4 px-5 py-5" style="grid-template-columns: {{ $heroMetaColumns }};">
                                        <span class="text-[0.95rem] font-semibold text-white/88">Status</span>
                                        <span class="font-semibold text-white/88">Active</span>
                                    </div>
                                </div>

                                <div class="mt-5 flex items-start gap-3 rounded-[1.25rem] border border-white/10 bg-white/[0.03] px-4 py-4">
                                    <flux:icon.information-circle class="mt-0.5 h-5 w-5 shrink-0 text-zinc-500"/>
                                    <div>
                                        <div class="text-[0.95rem] font-semibold text-white/88">Insight</div>
                                        <div class="mt-1 text-[0.82rem] leading-6 text-zinc-400">Framework guidance for this variable and recent validation context stays attached to the active environment value.</div>
                                    </div>
                                </div>
                                </aside>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="relative bg-white" data-walter-eye-stage>
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <section
                    class="trust-block relative left-1/2 mb-16 w-screen -translate-x-1/2 overflow-hidden border-b border-zinc-200/70 sm:mb-20"
                    style="background-image: url('{{ asset('images/the-business-of-laravel-background.jpg') }}'); background-size: cover; background-position: center;"
                >
                    <div class="absolute inset-0 bg-[linear-gradient(100deg,rgba(6,9,15,0.9)_0%,rgba(10,14,21,0.84)_42%,rgba(10,14,21,0.58)_100%)]"></div>

                    <div class="relative mx-auto max-w-7xl px-6 py-14 lg:px-8 lg:py-16">
                        <div class="grid gap-6 lg:grid-cols-2 lg:items-center">
                            <div class="max-w-xl">
                                <h2 class="text-3xl font-medium tracking-[-0.04em] text-white sm:text-[2.4rem] sm:leading-[1.02]">
                                    Why We Built Ghostable
                                </h2>

                                <p class="mt-4 text-base leading-8 text-zinc-100 sm:text-lg">
                                    Built by the former co-founder of
                                    <a href="https://curricula.com" target="_blank" rel="noopener noreferrer" class="font-semibold text-white underline decoration-white/35 underline-offset-4 hover:decoration-white">
                                        Curricula
                                    </a>,
                                    a cybersecurity training platform acquired by
                                    <a href="https://huntress.com" target="_blank" rel="noopener noreferrer" class="font-semibold text-white underline decoration-white/35 underline-offset-4 hover:decoration-white">
                                        Huntress
                                    </a>. Joe sat down with Matt Stauffer on
                                    <a href="https://www.youtube.com/watch?v=JBduPv2jajY" target="_blank" rel="noopener noreferrer" class="font-semibold text-white underline decoration-white/35 underline-offset-4 hover:decoration-white">
                                        The Business of Laravel
                                    </a>
                                    to discuss why he built Ghostable and the origin story behind the product.
                                </p>
                            </div>

                            <div class="lg:justify-self-end lg:w-full">
                                <div class="w-full overflow-hidden rounded-xl border border-white/20 bg-black/40 shadow-[0_20px_50px_rgba(15,23,42,0.4)]">
                                    <div class="aspect-video">
                                        <iframe
                                            class="h-full w-full"
                                            src="https://www.youtube.com/embed/JBduPv2jajY?si=k3x7q_9eSN0l00Ge"
                                            title="The Business of Laravel interview about Ghostable"
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                            referrerpolicy="strict-origin-when-cross-origin"
                                            loading="lazy"
                                            allowfullscreen
                                        ></iframe>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <div class="mx-auto mt-10 max-w-5xl text-center sm:mt-12">
                    <h2 class="text-4xl font-medium tracking-[-0.065em] text-zinc-950 sm:text-6xl sm:leading-[0.95]">
                        Most secrets tools solve storage.
                        <span class="block">The daily work is still a mess.</span>
                    </h2>

                    <p class="mx-auto mt-8 max-w-4xl text-lg leading-8 text-zinc-600 sm:text-xl">
                        The painful part is not where secrets live. It is everything around them: figuring out what changed, keeping staging from drifting, validating config before deploys, and not turning every env edit into a terminal ritual. Ghostable is built for that reality. A CI dashboard is great for delivery. A terminal is great for automation. Day-to-day environment management deserves its own workspace.
                    </p>
                </div>
            </div>

            <div class="relative mt-16 min-h-[46rem] sm:min-h-[52rem] lg:min-h-[58rem]" data-positioning-contrast>
                <div class="pointer-events-none absolute inset-0 z-0 hidden lg:block" aria-hidden="true">
                    <div class="mx-auto relative h-full max-w-7xl px-6 lg:px-8">
                        <div class="absolute left-[4.75rem] top-[23.5rem] w-[16rem] rotate-[12deg] opacity-40">
                            <div class="rounded-[1.35rem] rounded-bl-md border-2 border-zinc-200/90 bg-white/72 px-4 py-3 shadow-[0_12px_28px_rgba(15,23,42,0.07)] backdrop-blur-sm">
                                <p class="text-[0.92rem] leading-7 text-zinc-500">
                                    Can someone share the Google Maps API key? Gotta debug this map thing locally real quick.
                                </p>
                            </div>
                        </div>

                        <div class="absolute right-[5.25rem] top-[24.75rem] w-[16.5rem] rotate-[-10deg] opacity-36">
                            <div class="rounded-[1.35rem] rounded-br-md border-2 border-zinc-200/90 bg-white/70 px-4 py-3 shadow-[0_12px_28px_rgba(15,23,42,0.07)] backdrop-blur-sm">
                                <p class="text-[0.92rem] leading-7 text-zinc-500">
                                    Just hardcoding the Twilio key in the code for 5 mins to test SMS. I'll revert before commit.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pointer-events-none absolute inset-0 z-[1] hidden lg:block" aria-hidden="true">
                    <div class="mx-auto relative h-full max-w-7xl px-6 lg:px-8">
                        <div class="absolute left-[2.75rem] top-[37.5rem] w-[14rem] rotate-[-9deg] opacity-24">
                            <div class="rounded-[1.25rem] rounded-tr-md border-2 border-zinc-200/80 bg-white/60 px-4 py-3 shadow-[0_10px_24px_rgba(15,23,42,0.05)] backdrop-blur-sm">
                                <p class="text-[0.86rem] leading-6 text-zinc-500">
                                    Which deploy actually picked up the new <span class="font-mono text-zinc-700">SENTRY_DSN</span>? The errors are still grouped under the old project.
                                </p>
                            </div>
                        </div>

                        <div class="absolute right-[3rem] top-[38.25rem] w-[13.5rem] rotate-[8deg] opacity-22">
                            <div class="rounded-[1.25rem] rounded-tl-md border-2 border-zinc-200/75 bg-white/56 px-4 py-3 shadow-[0_10px_24px_rgba(15,23,42,0.05)] backdrop-blur-sm">
                                <p class="text-[0.86rem] leading-6 text-zinc-500">
                                    Did anyone update <span class="font-mono text-zinc-700">QUEUE_CONNECTION</span>? Jobs are stuck on sync again after the deploy.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pointer-events-none absolute inset-x-0 bottom-0 z-10 flex justify-center">
                    <div class="pointer-events-auto relative w-full max-w-[32rem] sm:max-w-[41rem] lg:max-w-[51rem] xl:max-w-[56rem]" data-walter-eye-figure>
                        <img
                            src="{{ asset('images/illustrations/walter-head-explode.png') }}"
                            alt="Overwhelmed developer illustration"
                            class="h-auto w-full"
                            loading="lazy"
                        >

                        <img
                            src="{{ asset('images/illustrations/walter-eyes.png') }}"
                            alt=""
                            aria-hidden="true"
                            data-walter-eye-overlay
                            class="pointer-events-none absolute inset-0 h-full w-full"
                            loading="lazy"
                        >
                    </div>
                </div>

                <div class="relative z-30 mx-auto max-w-7xl px-6 pt-8 sm:px-6 sm:pt-10 lg:px-8 lg:pt-6">
                        <article
                            data-positioning-step
                            data-positioning-chat-thread
                            class="positioning-step-reveal relative mx-auto flex max-w-[36rem] flex-col gap-5 sm:max-w-[42rem] lg:absolute lg:left-1/2 lg:top-12 lg:block lg:h-[20.5rem] lg:w-[50rem] lg:max-w-none lg:-translate-x-1/2"
                        >
                            <div
                                data-positioning-chat-item
                                class="lg:absolute lg:left-[-2.75rem] lg:top-[-0.35rem] lg:w-[22.75rem]"
                                style="--positioning-rotation: -15deg; --positioning-chat-delay: 0ms;"
                            >
                                <div data-positioning-surface class="max-w-[88%] rounded-[1.55rem] rounded-bl-md border-2 border-zinc-300 bg-white px-5 py-4 shadow-[0_16px_36px_rgba(15,23,42,0.1)]">
                                    <p class="text-[1.05rem] leading-8 text-zinc-700">
                                        When was the last time we rotated the Stripe key? Seeing a bunch of 401s in prod logs all of a sudden.
                                    </p>
                                </div>
                            </div>

                            <div
                                data-positioning-chat-item
                                class="self-end lg:absolute lg:left-[29.5rem] lg:top-0 lg:w-[21.25rem]"
                                style="--positioning-rotation: 11deg; --positioning-chat-delay: 180ms;"
                            >
                                <div data-positioning-surface class="max-w-[90%] rounded-[1.55rem] rounded-br-md border-2 border-zinc-300 bg-white px-5 py-4 shadow-[0_16px_36px_rgba(15,23,42,0.1)] lg:max-w-none">
                                    <p class="text-[1.05rem] leading-8 text-zinc-700">
                                        Production needs the mail token rotated before tonight. Who actually has access to do that?
                                    </p>
                                </div>
                            </div>

                            {{--
                                <div
                                    data-positioning-chat-item
                                    class="lg:absolute lg:left-[11.5rem] lg:top-[11.35rem] lg:w-[24rem]"
                                    style="--positioning-rotation: -4deg;"
                                >
                                    <div data-positioning-surface class="max-w-[92%] rounded-[1.55rem] rounded-bl-md border-2 border-zinc-300 bg-white px-5 py-4 shadow-[0_16px_36px_rgba(15,23,42,0.1)] lg:max-w-none">
                                        <p class="text-[1.05rem] leading-8 text-zinc-700">
                                            My local says <span class="font-mono text-zinc-950">APP_ENV=production</span> again. Which file are we supposed to trust?
                                        </p>
                                    </div>
                                </div>
                            --}}
                        </article>
                </div>
            </div>

            <div class="h-[10px] w-full bg-orange-500/90"></div>
        </section>

        <section class="border-t border-zinc-200 bg-zinc-50 py-24 sm:py-28">
            <div class="mx-auto max-w-[108rem] px-6 lg:px-8">
                <div class="mx-auto max-w-4xl text-center">
                    <h2 class="text-4xl font-medium tracking-[-0.065em] text-zinc-950 sm:text-6xl sm:leading-[0.95]">
                        What gets easier with Ghostable
                    </h2>
                </div>

                <div class="mx-auto mt-14 grid max-w-6xl gap-5 lg:grid-cols-2">
                    <article class="flex h-full flex-col overflow-hidden rounded-[1.75rem] border border-zinc-200 bg-white p-6 shadow-[0_20px_60px_rgba(15,23,42,0.05)] sm:p-7">
                        <div>
                            <h3 class="text-[1.85rem] font-medium tracking-[-0.055em] text-zinc-950 leading-[0.98] sm:text-[2.05rem]">
                                Find the variable. Fix the variable. Move on.
                            </h3>

                            <p class="mt-4 text-[0.95rem] leading-6 text-zinc-600 sm:text-base">
                                Browse organizations, projects, and environments in one place. Search by key, switch between table and grouped views, inspect metadata, and import or export .env files without hunting through dashboards, repos, and old messages.
                            </p>
                        </div>

                        <div class="-mx-6 -mb-6 mt-6 flex-1 sm:-mx-7 sm:-mb-7">
                            {{-- ghostable:graphic-placeholder {"id":"home-v2-value-find-variable","visual_type":"product screenshot","must_show":"A desktop environment window with search visible, variable rows on screen, and clear navigation between projects or environments. Optional controls for grouped or table view and import or export can also be visible.","communication_goal":"Show that finding and editing variables is fast, obvious, and centralized.","emotional_goal":"This image should communicate speed and flow. The visitor should feel they can stay in their thinking zone instead of context-switching between tools."} --}}
                            <div class="h-full overflow-hidden rounded-b-[1.75rem] border-t border-zinc-200 bg-zinc-900 shadow-[0_20px_60px_rgba(15,23,42,0.08)]">
                                <div class="p-3.5 sm:p-4">
                                    <div class="overflow-hidden rounded-[1.2rem] border border-white/10 bg-zinc-900">
                                        <div class="flex items-center justify-between gap-3 border-b border-white/10 px-3.5 py-3 text-left">
                                            <span class="text-[0.8rem] font-medium text-white/88">Key</span>
                                            <span class="flex items-center gap-2 font-mono text-[0.72rem] text-zinc-300">
                                                <flux:icon.lock-closed variant="solid" class="h-3.5 w-3.5 text-zinc-500"/>
                                                STRIPE_SECRET_KEY
                                            </span>
                                        </div>
                                        <div class="px-3.5 py-3.5">
                                            <div class="text-[0.8rem] font-medium text-white/88">Value</div>
                                            <div class="mt-2.5 rounded-[0.95rem] border border-white/10 bg-zinc-800 px-3.5 py-3.5 font-mono text-[0.86rem] text-white/88">
                                                sk_live_demo_7b9x2k4qf3m8n1p
                                            </div>
                                            <div class="mt-2.5 text-[0.74rem] text-zinc-500">
                                                Current value stored for this environment variable.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-4 overflow-hidden rounded-[1.2rem] border border-white/10 bg-zinc-900">
                                        <div class="border-b border-white/10 px-3.5 py-2.5 text-left text-[0.8rem] font-medium text-white/88">
                                            Details
                                        </div>
                                        <div class="grid grid-cols-[1fr_auto] gap-3 border-b border-white/10 px-3.5 py-3 text-left text-[0.8rem]">
                                            <span class="text-white/88">Version</span>
                                            <span class="text-zinc-300">7</span>
                                        </div>
                                        <div class="grid grid-cols-[1fr_auto] gap-3 border-b border-white/10 px-3.5 py-3 text-left text-[0.8rem]">
                                            <span class="text-white/88">Updated</span>
                                            <span class="text-zinc-300">Feb 23, 2026</span>
                                        </div>
                                        <div class="grid grid-cols-[1fr_auto] gap-3 border-b border-white/10 px-3.5 py-3 text-left text-[0.8rem]">
                                            <span class="text-white/88">Updated By</span>
                                            <span class="text-zinc-300">will@ghostable.dev</span>
                                        </div>
                                        <div class="grid grid-cols-[1fr_auto] gap-3 px-3.5 py-3 text-left text-[0.8rem]">
                                            <span class="text-white/88">Status</span>
                                            <span class="inline-flex items-center rounded-full border border-brand bg-brand px-2 py-0.5 text-[0.68rem] font-medium text-white shadow-[0_10px_24px_color-mix(in_srgb,var(--color-brand)_22%,transparent)]">Active</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>

                    <article class="flex h-full flex-col overflow-hidden rounded-[1.75rem] border border-zinc-200 bg-white p-6 shadow-[0_20px_60px_rgba(15,23,42,0.05)] sm:p-7">
                        <div>
                            <h3 class="text-[1.85rem] font-medium tracking-[-0.055em] text-zinc-950 leading-[0.98] sm:text-[2.05rem]">
                                Catch bad config before it becomes a staging-only mystery.
                            </h3>

                            <p class="mt-4 text-[0.95rem] leading-6 text-zinc-600 sm:text-base">
                                Run validation against the same Ghostable schema files your project already uses. Define global rules, add environment-specific overrides, and catch missing keys, broken values, and bad assumptions before they reach a deploy.
                            </p>
                        </div>

                        <div class="-mx-6 -mb-6 mt-6 flex-1 sm:-mx-7 sm:-mb-7">
                            {{-- ghostable:graphic-placeholder {"id":"home-v2-value-validation","visual_type":"validation-focused product screenshot","must_show":"A validation view, modal, or detail pane showing flagged issues such as missing keys, invalid values, failed rules, or environment-specific overrides. The UI should make it obvious that validation is part of the workflow.","communication_goal":"Show that Ghostable catches configuration mistakes before deployment and makes validation visible and actionable.","emotional_goal":"The developer should feel safety and foresight, the release of tension that comes from catching problems early instead of being surprised later."} --}}
                            <div class="h-full overflow-hidden rounded-b-[1.75rem] border-t border-zinc-200 bg-zinc-900 shadow-[0_20px_60px_rgba(15,23,42,0.08)]">
                                <div class="space-y-3.5 p-3.5 sm:p-4">
                                    <div class="overflow-hidden rounded-[1.2rem] border border-white/10 bg-zinc-950">
                                        <div class="flex items-center justify-between gap-3 border-b border-white/10 px-3.5 py-3">
                                            <div>
                                                <div class="font-mono text-[0.82rem] font-medium text-white/88">APP_DEBUG</div>
                                                <div class="mt-1 text-[0.68rem] text-zinc-400">2 rules</div>
                                            </div>
                                            <button class="rounded-2xl border border-white/10 bg-zinc-800 px-2.5 py-1 text-[0.68rem] font-medium text-zinc-200">Remove Key</button>
                                        </div>
                                        <div class="space-y-2.5 px-3.5 py-3.5">
                                            <div class="flex items-center gap-2.5">
                                                <div class="min-w-0 flex-1 rounded-[0.95rem] border border-white/10 bg-zinc-800 px-3.5 py-2.5 font-mono text-[0.8rem] text-white/82">boolean</div>
                                                <div class="grid h-8 w-8 place-items-center rounded-lg bg-zinc-800 text-lg text-zinc-300">−</div>
                                                <div class="grid h-8 w-8 place-items-center rounded-lg bg-zinc-800 text-lg text-zinc-300">+</div>
                                            </div>
                                            <div class="flex items-center gap-2.5">
                                                <div class="min-w-0 flex-1 rounded-[0.95rem] border border-white/10 bg-zinc-800 px-3.5 py-2.5 font-mono text-[0.8rem] text-white/82">in:false</div>
                                                <div class="grid h-8 w-8 place-items-center rounded-lg bg-zinc-800 text-lg text-zinc-300">−</div>
                                                <div class="grid h-8 w-8 place-items-center rounded-lg bg-zinc-800 text-lg text-zinc-300">+</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="overflow-hidden rounded-[1.2rem] border border-white/10 bg-zinc-950">
                                        <div class="flex items-center justify-between gap-3 border-b border-white/10 px-3.5 py-3">
                                            <div>
                                                <div class="font-mono text-[0.82rem] font-medium text-white/88">QUEUE_CONNECTION</div>
                                                <div class="mt-1 text-[0.68rem] text-zinc-400">1 rule</div>
                                            </div>
                                            <button class="rounded-2xl border border-white/10 bg-zinc-800 px-2.5 py-1 text-[0.68rem] font-medium text-zinc-200">Remove Key</button>
                                        </div>
                                        <div class="flex items-center gap-2.5 px-3.5 py-3.5">
                                            <div class="min-w-0 flex-1 rounded-[0.95rem] border border-white/10 bg-zinc-800 px-3.5 py-2.5 font-mono text-[0.8rem] text-white/82">in:sync,database,redis</div>
                                            <div class="grid h-8 w-8 place-items-center rounded-lg bg-zinc-800 text-lg text-zinc-300">−</div>
                                            <div class="grid h-8 w-8 place-items-center rounded-lg bg-zinc-800 text-lg text-zinc-300">+</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>

                    <article class="flex h-full flex-col overflow-hidden rounded-[1.75rem] border border-zinc-200 bg-white p-6 shadow-[0_20px_60px_rgba(15,23,42,0.05)] sm:p-7">
                        <div>
                            <h3 class="text-[1.85rem] font-medium tracking-[-0.055em] text-zinc-950 leading-[0.98] sm:text-[2.05rem]">
                                Know what changed before you start guessing.
                            </h3>

                            <p class="mt-4 text-[0.95rem] leading-6 text-zinc-600 sm:text-base">
                                Review organization, project, and environment activity. Open a variable, inspect its history, and restore an older value when something looks off instead of turning config management into archaeology.
                            </p>
                        </div>

                        <div class="-mx-6 -mb-6 mt-6 flex-1 sm:-mx-7 sm:-mb-7">
                            {{-- ghostable:graphic-placeholder {"id":"home-v2-value-history","visual_type":"history / activity screenshot","must_show":"A variable detail view or history panel showing timestamps, changes, actor names, previous values or versions, and a restore action.","communication_goal":"Show that Ghostable makes change tracking visible, understandable, and recoverable.","emotional_goal":"The image should communicate transparency and control over time. The visitor should feel: I can see exactly what happened and fix it without guessing."} --}}
                            <div class="h-full overflow-hidden rounded-b-[1.75rem] border-t border-zinc-200 bg-zinc-900 shadow-[0_20px_60px_rgba(15,23,42,0.08)]">
                                <div class="p-3.5 sm:p-4">
                                    <div class="space-y-4">
                                        <div class="overflow-hidden rounded-[1.25rem] border border-white/10 bg-zinc-900">
                                            <div class="flex items-start justify-between gap-3 px-4 py-3.5">
                                                <div>
                                                    <div class="flex items-center gap-3">
                                                        <span class="text-[0.98rem] font-medium text-white/90">Version 2</span>
                                                        <span class="inline-flex items-center rounded-full border border-brand bg-brand px-2 py-0.5 text-[0.66rem] font-medium text-white shadow-[0_10px_24px_color-mix(in_srgb,var(--color-brand)_22%,transparent)]">Current</span>
                                                    </div>
                                                    <div class="mt-2.5 text-[0.72rem] text-zinc-400">By will@ghostable.dev</div>
                                                    <div class="mt-2 flex items-center gap-3 text-[0.72rem] text-zinc-400">
                                                        <span>Updated</span>
                                                        <span class="text-zinc-600">|</span>
                                                        <span>Mar 18, 2026 at 4:22 PM</span>
                                                    </div>
                                                </div>

                                                <button class="rounded-2xl bg-zinc-800 px-3 py-1.5 text-[0.72rem] font-medium text-white/88">
                                                    Restore
                                                </button>
                                            </div>
                                            <div class="border-t border-white/10 px-4 py-3.5">
                                                <div class="flex items-center gap-3 rounded-[1rem] bg-zinc-800 px-3.5 py-3.5">
                                                    <flux:icon.eye class="h-4 w-4 shrink-0 text-zinc-500"/>
                                                    <span class="font-mono text-[0.8rem] text-zinc-200">sk_live_mock_4f9x2m8q7p1v6k3d</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="overflow-hidden rounded-[1.25rem] border border-white/10 bg-zinc-900">
                                            <div class="flex items-start justify-between gap-3 px-4 py-3.5">
                                                <div>
                                                    <div class="text-[0.98rem] font-medium text-white/90">Version 1</div>
                                                    <div class="mt-2.5 text-[0.72rem] text-zinc-400">By james@ghostable.dev</div>
                                                    <div class="mt-2 flex items-center gap-3 text-[0.72rem] text-zinc-400">
                                                        <span>Created</span>
                                                        <span class="text-zinc-600">|</span>
                                                        <span>Feb 23, 2026 at 10:51 AM</span>
                                                    </div>
                                                </div>

                                                <button class="rounded-2xl bg-zinc-800 px-3 py-1.5 text-[0.72rem] font-medium text-white/88">
                                                    Restore
                                                </button>
                                            </div>
                                            <div class="border-t border-white/10 px-4 py-3.5">
                                                <div class="flex items-center gap-3 rounded-[1rem] bg-zinc-800 px-3.5 py-3.5">
                                                    <flux:icon.eye class="h-4 w-4 shrink-0 text-zinc-500"/>
                                                    <span class="font-mono text-[0.8rem] text-zinc-200">••••••••</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>

                    <article class="flex h-full flex-col overflow-hidden rounded-[1.75rem] border border-zinc-200 bg-white p-6 shadow-[0_20px_60px_rgba(15,23,42,0.05)] sm:p-7">
                        <div>
                            <h3 class="text-[1.85rem] font-medium tracking-[-0.055em] text-zinc-950 leading-[0.98] sm:text-[2.05rem]">
                                Keep automation in its lane.
                            </h3>

                            <p class="mt-4 text-[0.95rem] leading-6 text-zinc-600 sm:text-base">
                                Issue deploy tokens from the desktop app when CI needs access. Use the CLI when the work belongs in scripts, pipelines, or non-macOS workflows. Humans get a UI. Automation gets credentials and commands.
                            </p>
                        </div>

                        <div class="-mx-6 -mb-6 mt-6 flex-1 sm:-mx-7 sm:-mb-7">
                            {{-- ghostable:graphic-placeholder {"id":"home-v2-value-automation","visual_type":"split workflow visual","must_show":"A dominant Ghostable Desktop interface on one side and a smaller supporting CLI or CI automation panel on the other. The desktop side should clearly feel like the main human workspace, while the automation side should feel utilitarian and secondary.","communication_goal":"Show that Ghostable separates human workflow from machine workflow instead of forcing both through the same interface.","emotional_goal":"This image should communicate clear boundaries and respect for different workflows. Humans get the beautiful tool. Machines get the clean interface they need."} --}}
                            <div class="h-full overflow-hidden rounded-b-[1.75rem] border-t border-zinc-200 bg-zinc-900 shadow-[0_20px_60px_rgba(15,23,42,0.08)]">
                                <div class="p-4 sm:p-[1.125rem]">
                                    <div class="text-left">
                                        <div class="text-[0.9rem] font-medium text-white/88">Token Details</div>
                                    </div>

                                    <div class="mt-3 overflow-hidden rounded-[1.25rem] border border-white/10 bg-zinc-950">
                                        <div class="grid grid-cols-[1fr_auto] gap-3 border-b border-white/10 px-4 py-3.5 text-left">
                                            <span class="text-[0.8rem] font-medium text-zinc-300">Token ID</span>
                                            <span class="font-mono text-[0.82rem] text-white/88">tok_01jq8t2qv3x9m4z7c6</span>
                                        </div>
                                    </div>

                                    <div class="mt-7 text-left">
                                        <div class="text-[0.9rem] font-medium text-white/88">Secrets</div>
                                    </div>

                                    <div class="mt-3 overflow-hidden rounded-[1.25rem] border border-white/10 bg-zinc-950">
                                        <div class="border-b border-white/10 px-4 py-4">
                                            <div class="flex items-center justify-between gap-4">
                                                <div class="text-[0.8rem] font-medium text-white/88">Deploy Seed</div>
                                                <button class="rounded-xl bg-brand px-3 py-1.5 text-[0.72rem] font-medium text-white shadow-[0_10px_24px_color-mix(in_srgb,var(--color-brand)_28%,transparent)]">Copy</button>
                                            </div>
                                            <div class="mt-3 rounded-[0.95rem] bg-zinc-800 px-4 py-3.5 font-mono text-[0.8rem] text-white/88">
                                                MmJNeE9pQ2hMek5qWTR5VmxCSGRqQnRNVEE9
                                            </div>
                                        </div>

                                        <div class="px-4 py-4">
                                            <div class="flex items-center justify-between gap-4">
                                                <div class="text-[0.8rem] font-medium text-white/88">Environment Variables</div>
                                                <button class="rounded-xl bg-brand px-3 py-1.5 text-[0.72rem] font-medium text-white shadow-[0_10px_24px_color-mix(in_srgb,var(--color-brand)_28%,transparent)]">Copy All</button>
                                            </div>
                                            <div class="mt-3 rounded-[0.95rem] bg-zinc-800 px-4 py-3.5 font-mono text-[0.76rem] leading-6 text-white/82">
                                                <div>DEPLOY_TOKEN=tok_01jq8t2qv3x9m4z7c6</div>
                                                <div>DEPLOY_TARGET=production-web</div>
                                                <div>DEPLOY_SEED=MmJNeE9pQ2hMek5qWTR5VmxCSGRqQn...</div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <section class="border-t border-white/10 bg-zinc-950 pb-28 pt-28 text-white sm:pb-32 sm:pt-32 lg:pb-36 lg:pt-36">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="mx-auto max-w-5xl text-center">
                    <h2 class="mx-auto max-w-none text-[2.55rem] font-medium tracking-[-0.055em] text-white sm:text-[3.5rem] sm:leading-[0.96] lg:text-[3.95rem] lg:whitespace-nowrap">
                        Zero-knowledge, without the theater.
                    </h2>

                    <p class="mx-auto mt-8 max-w-2xl text-lg leading-8 text-zinc-300 sm:text-xl">
                        Ghostable encrypts environment data on trusted clients before it is stored. Linked devices handle human access. Deploy tokens handle automation. Plaintext values and private keys stay with the client that actually needs them.
                    </p>
                </div>

                <!-- ghostable:graphic-placeholder {"id":"home-v2-security-model","visual_type":"security architecture illustration or restrained product-support visual","must_show":"Trusted client devices, encrypted data flow, and scoped automation or deploy-token access. Avoid generic shield or lock symbolism unless it is very secondary.","communication_goal":"Show that Ghostable security is grounded in actual engineering choices: client-side encryption, trusted devices, and scoped automation access.","emotional_goal":"The image should communicate quiet, serious trust. The tone should feel like professional restraint and real engineering confidence, not security theater."} -->
                {{--
                    <div>
                        <x-site.graphic-placeholder
                            identifier="home-v2-security-model"
                            tone="dark"
                            label="Security graphic placeholder"
                            title="Trusted clients, encrypted storage, and scoped automation access"
                            copy="Placeholder for the actual security model rather than a generic lock icon."
                            class="min-h-[18rem] sm:min-h-[22rem]"
                        />
                    </div>
                --}}

                <div class="mx-auto mt-16 max-w-4xl sm:mt-20">
                    <div class="relative mx-auto max-w-5xl">
                        <div class="absolute left-1/2 top-6 hidden h-[calc(100%-3rem)] w-px -translate-x-1/2 bg-white/10 md:block"></div>

                        <div class="space-y-6">
                            <article data-trust-step class="trust-step-reveal relative mx-auto max-w-4xl text-center">
                                <div data-trust-part="number" style="--trust-delay: 0ms;" class="relative z-10 mx-auto flex h-12 w-12 items-center justify-center rounded-full border border-white/15 bg-zinc-950 text-sm font-semibold text-white shadow-[0_10px_24px_rgba(0,0,0,0.25)]">
                                    <flux:icon.computer-desktop variant="solid" class="h-5 w-5"/>
                                </div>

                                <div class="relative z-10 mx-auto mt-4 max-w-2xl bg-zinc-950 px-4 sm:px-6">
                                    <div data-trust-part="copy" style="--trust-delay: 90ms;">
                                        <h4 class="text-xl font-medium tracking-[-0.035em] text-white sm:text-[1.65rem]">
                                            Trusted Client (Human Access)
                                        </h4>
                                        <p class="mx-auto mt-3 max-w-xl text-sm leading-7 text-zinc-300 sm:text-base">
                                            Plaintext values and private keys stay on the trusted client. This is where humans review and manage environment data.
                                        </p>
                                    </div>

                                    <div data-trust-part="visual" style="--trust-delay: 180ms;" class="mx-auto mt-6 max-w-[34rem]">
                                        <div class="overflow-hidden rounded-[1.4rem] border border-white/10 bg-zinc-900 shadow-[0_20px_40px_rgba(0,0,0,0.4),0_8px_16px_rgba(0,0,0,0.3)]">
                                            <div class="flex items-center justify-between gap-4 border-b border-white/10 px-4 py-3.5 text-left">
                                                <span class="text-[0.82rem] font-medium text-white/88">Key</span>
                                                <span class="flex items-center gap-2 font-mono text-[0.76rem] text-zinc-300">
                                                    <flux:icon.lock-closed variant="solid" class="h-4 w-4 text-zinc-500"/>
                                                    DB_PASSWORD
                                                </span>
                                            </div>
                                            <div class="px-4 py-4 text-left">
                                                <div class="text-[0.82rem] font-medium text-white/88">Value</div>
                                                <div data-trust-focus-surface class="mt-3 rounded-[1rem] border bg-zinc-800 px-4 py-4 font-mono text-[0.92rem] text-white/88">
                                                    <span data-typed-value data-typed-length="16" data-typed-seed="{{ $trustedClientPasswordSeed }}">{{ $trustedClientPasswordSeed }}</span><span data-typed-cursor aria-hidden="true" class="-ml-[0.14em] inline-block text-brand">|</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </article>

                            <article data-trust-step class="trust-step-reveal relative mx-auto max-w-4xl text-center">
                                <div data-trust-part="number" style="--trust-delay: 0ms;" class="relative z-10 mx-auto flex h-12 w-12 items-center justify-center rounded-full border border-white/15 bg-zinc-950 text-sm font-semibold text-white shadow-[0_10px_24px_rgba(0,0,0,0.25)]">
                                    <flux:icon.lock-closed variant="solid" class="h-5 w-5"/>
                                </div>

                                <div class="relative z-10 mx-auto mt-4 max-w-xl px-4 pb-8 sm:px-6 lg:pb-10">
                                    <div data-trust-part="copy" style="--trust-delay: 90ms;" class="mx-auto max-w-xl bg-zinc-950">
                                        <h4 class="text-xl font-medium tracking-[-0.035em] text-white sm:text-[1.65rem]">
                                            Encrypted Sync / Storage
                                        </h4>
                                        <p class="mx-auto mt-3 max-w-xl text-sm leading-7 text-zinc-300 sm:text-base">
                                            Data is encrypted at the edge before it is stored or synced. Ghostable only sees encrypted data, not plaintext values.
                                        </p>
                                    </div>

                                    <div data-trust-part="visual" style="--trust-delay: 180ms;" class="relative mx-auto mt-8 max-w-[62rem] pb-4 text-left lg:mt-[3.5rem] lg:pb-6">
                                        <div data-radiant-lines="encrypted-sync" data-encrypted-sync-demo aria-hidden="true" class="pointer-events-none absolute left-1/2 top-[120px] hidden h-[25rem] w-screen -translate-x-1/2 -translate-y-1/2 lg:block">
                                            <svg class="absolute inset-0 h-full w-full" viewBox="0 0 1440 420" fill="none" preserveAspectRatio="none">
                                                <defs>
                                                    <filter id="encrypted-sync-particle-glow" x="-120%" y="-120%" width="340%" height="340%">
                                                        <feGaussianBlur stdDeviation="2.4" result="blur"/>
                                                        <feMerge>
                                                            <feMergeNode in="blur"/>
                                                            <feMergeNode in="SourceGraphic"/>
                                                        </feMerge>
                                                    </filter>
                                                </defs>

                                                <g fill="none">
                                                    <path data-sync-track d="M0 208C250 186 495 190 720 208C945 226 1190 230 1440 208" stroke-width="1.14" stroke-linecap="round"/>
                                                    <path id="encrypted-sync-left-1" data-sync-track d="M720 208C644 180 520 106 0 18" stroke-width="1.06" stroke-linecap="round"/>
                                                    <path id="encrypted-sync-left-2" data-sync-track d="M720 208C648 188 536 138 0 54" stroke-width="1.04" stroke-linecap="round"/>
                                                    <path id="encrypted-sync-left-3" data-sync-track d="M720 208C652 196 552 170 0 98" stroke-width="1.02" stroke-linecap="round"/>
                                                    <path id="encrypted-sync-left-4" data-sync-track d="M720 208C646 206 540 204 0 154" stroke-width="1.08" stroke-linecap="round"/>
                                                    <path id="encrypted-sync-left-5" data-sync-track d="M720 208C648 218 536 232 0 256" stroke-width="1.08" stroke-linecap="round"/>
                                                    <path id="encrypted-sync-left-6" data-sync-track d="M720 208C652 232 548 286 0 324" stroke-width="1.04" stroke-linecap="round"/>
                                                    <path id="encrypted-sync-left-7" data-sync-track d="M720 208C660 246 566 344 0 394" stroke-width="0.98" stroke-linecap="round"/>
                                                    <path id="encrypted-sync-right-1" data-sync-track d="M720 208C796 180 920 106 1440 18" stroke-width="1.06" stroke-linecap="round"/>
                                                    <path id="encrypted-sync-right-2" data-sync-track d="M720 208C792 188 904 138 1440 54" stroke-width="1.04" stroke-linecap="round"/>
                                                    <path id="encrypted-sync-right-3" data-sync-track d="M720 208C788 196 888 170 1440 98" stroke-width="1.02" stroke-linecap="round"/>
                                                    <path id="encrypted-sync-right-4" data-sync-track d="M720 208C794 206 900 204 1440 154" stroke-width="1.08" stroke-linecap="round"/>
                                                    <path id="encrypted-sync-right-5" data-sync-track d="M720 208C792 218 904 232 1440 256" stroke-width="1.08" stroke-linecap="round"/>
                                                    <path id="encrypted-sync-right-6" data-sync-track d="M720 208C788 232 892 286 1440 324" stroke-width="1.04" stroke-linecap="round"/>
                                                    <path id="encrypted-sync-right-7" data-sync-track d="M720 208C780 246 874 344 1440 394" stroke-width="0.98" stroke-linecap="round"/>
                                                </g>

                                                <g filter="url(#encrypted-sync-particle-glow)">
                                                    <circle data-sync-particle data-sync-track-ref="encrypted-sync-left-1" data-sync-speed="0.088" data-sync-phase="0.06" data-sync-base-radius="3.4" cx="720" cy="208" r="3.4"/>
                                                    <circle data-sync-particle data-sync-track-ref="encrypted-sync-left-2" data-sync-direction="reverse" data-sync-speed="0.074" data-sync-phase="0.24" data-sync-base-radius="2.8" cx="720" cy="208" r="2.8"/>
                                                    <circle data-sync-particle data-sync-track-ref="encrypted-sync-left-4" data-sync-speed="0.094" data-sync-phase="0.18" data-sync-base-radius="3.1" cx="720" cy="208" r="3.1"/>
                                                    <circle data-sync-particle data-sync-track-ref="encrypted-sync-left-6" data-sync-direction="reverse" data-sync-speed="0.082" data-sync-phase="0.12" data-sync-base-radius="2.9" cx="720" cy="208" r="2.9"/>
                                                    <circle data-sync-particle data-sync-track-ref="encrypted-sync-left-7" data-sync-speed="0.068" data-sync-phase="0.42" data-sync-base-radius="2.6" cx="720" cy="208" r="2.6"/>
                                                    <circle data-sync-particle data-sync-track-ref="encrypted-sync-right-1" data-sync-direction="reverse" data-sync-speed="0.086" data-sync-phase="0.14" data-sync-base-radius="3.4" cx="720" cy="208" r="3.4"/>
                                                    <circle data-sync-particle data-sync-track-ref="encrypted-sync-right-2" data-sync-speed="0.076" data-sync-phase="0.3" data-sync-base-radius="2.8" cx="720" cy="208" r="2.8"/>
                                                    <circle data-sync-particle data-sync-track-ref="encrypted-sync-right-4" data-sync-direction="reverse" data-sync-speed="0.092" data-sync-phase="0.08" data-sync-base-radius="3.1" cx="720" cy="208" r="3.1"/>
                                                    <circle data-sync-particle data-sync-track-ref="encrypted-sync-right-6" data-sync-speed="0.08" data-sync-phase="0.38" data-sync-base-radius="2.9" cx="720" cy="208" r="2.9"/>
                                                    <circle data-sync-particle data-sync-track-ref="encrypted-sync-right-7" data-sync-direction="reverse" data-sync-speed="0.066" data-sync-phase="0.5" data-sync-base-radius="2.6" cx="720" cy="208" r="2.6"/>
                                                </g>
                                            </svg>
                                        </div>

                                        <div class="pointer-events-none absolute -left-12 top-8 hidden w-[21.5rem] overflow-hidden rounded-[1.5rem] border border-white/10 bg-zinc-900 shadow-[0_20px_40px_rgba(0,0,0,0.4),0_8px_16px_rgba(0,0,0,0.3)] sm:w-[23rem] lg:block lg:-rotate-[6deg]">
                                            <div class="flex items-start justify-between gap-3 border-b border-white/10 px-4 pb-3.5 pt-3.5">
                                                <div class="flex items-center gap-3">
                                                    <div class="font-mono text-[0.64rem] text-white/40">
                                                        v14
                                                    </div>
                                                    <div class="font-mono text-[0.72rem] text-white/72 sm:text-[0.76rem]">
                                                        REDIS_URL
                                                    </div>
                                                </div>
                                                <div class="whitespace-nowrap font-mono text-[0.62rem] text-white/50 sm:text-[0.64rem]">
                                                    XChaCha20-Poly1305
                                                </div>
                                            </div>

                                            <div class="px-4 py-4">
                                                <p class="line-clamp-4 overflow-hidden break-all font-mono text-[0.7rem] leading-6 text-white/26 sm:text-[0.72rem]">
                                                    5bcf17aa42d0f98e61c3b27d11f43e7a8c2b741ec8139f42e0aa71d54b2d88ca06e31fb714ab69cd0e5a74c9f6ab31e4f80a2f5a47bc19d27cbfe12a2a7f05bc91d43c7a6e52bb1caa7fd403f6a12b8e0d4f7a86cc31a4ef1bd9c25a56b3ff12
                                                </p>
                                            </div>
                                        </div>

                                        <div class="pointer-events-none absolute -right-12 top-8 hidden w-[21.5rem] overflow-hidden rounded-[1.5rem] border border-white/10 bg-zinc-900 shadow-[0_20px_40px_rgba(0,0,0,0.4),0_8px_16px_rgba(0,0,0,0.3)] sm:w-[23rem] lg:block lg:rotate-[6deg]">
                                            <div class="flex items-start justify-between gap-3 border-b border-white/10 px-4 pb-3.5 pt-3.5">
                                                <div class="flex items-center gap-3">
                                                    <div class="font-mono text-[0.64rem] text-white/40">
                                                        v27
                                                    </div>
                                                    <div class="font-mono text-[0.72rem] text-white/72 sm:text-[0.76rem]">
                                                        STRIPE_SECRET_KEY
                                                    </div>
                                                </div>
                                                <div class="whitespace-nowrap font-mono text-[0.62rem] text-white/50 sm:text-[0.64rem]">
                                                    XChaCha20-Poly1305
                                                </div>
                                            </div>

                                            <div class="px-4 py-4">
                                                <p class="line-clamp-4 overflow-hidden break-all font-mono text-[0.7rem] leading-6 text-white/26 sm:text-[0.72rem]">
                                                    c8139f42e0aa71d54b2d88ca06e31fb714ab69cd5bcf17aa42d0f98e61c3b27d7f4a90c2b1d64e18c8aa5d72f9231ab4dfe11847c75f61a29f6cb820d0f14a2f3ae1bc77d94f8a23c0bd116f2e8c491ab77fd0034c512fa89e16bb7f52cda1e4
                                                </p>
                                            </div>
                                        </div>

                                        <div data-encrypted-sync-focus-card class="relative z-10 mx-auto w-[21.5rem] overflow-hidden rounded-[1.5rem] border border-white/10 bg-zinc-900 shadow-[0_20px_40px_rgba(0,0,0,0.4),0_8px_16px_rgba(0,0,0,0.3)] sm:w-[23rem] lg:translate-y-4">
                                            <div class="flex items-start justify-between gap-4 border-b border-white/10 px-4 pb-3.5 pt-3.5">
                                                <div class="flex items-center gap-3">
                                                    <div class="font-mono text-[0.64rem] text-white/40 sm:text-[0.68rem]">
                                                        v19
                                                    </div>
                                                    <div class="font-mono text-[0.72rem] text-white/72 sm:text-[0.76rem]">
                                                        DATABASE_PASSWORD
                                                    </div>
                                                </div>
                                                <div class="whitespace-nowrap font-mono text-[0.62rem] text-white/50 sm:text-[0.64rem]">
                                                    XChaCha20-Poly1305
                                                </div>
                                            </div>

                                            <div class="px-4 py-4">
                                                <p class="line-clamp-4 overflow-hidden break-all font-mono text-[0.7rem] leading-6 text-white/30 sm:text-[0.72rem]">
                                                    7f4a90c2b1d64e18c8aa5d72f9231ab4dfe11847c75f61a29f6cb820d0f14a2f0e5a74c9f6ab31e4f80a2f5a47bc19d2b1fa84ce9d13a7617cbfe12a2a7f05bcd91e5f43cafe8b7236cb3d49a8ff2c7e14ab69cd5bcf17aa42d0f98e61c3b27d
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </article>

                            <article data-trust-step class="trust-step-reveal relative mx-auto max-w-4xl text-center">
                                <div data-trust-part="number" style="--trust-delay: 0ms;" class="relative z-10 mx-auto flex h-12 w-12 items-center justify-center rounded-full border border-white/15 bg-zinc-950 text-sm font-semibold text-white shadow-[0_10px_24px_rgba(0,0,0,0.25)]">
                                    <flux:icon.command-line class="h-5 w-5"/>
                                </div>

                                <div class="relative z-10 mx-auto mt-4 max-w-[46rem] bg-zinc-950 px-4 sm:px-6">
                                    <div data-trust-part="copy" style="--trust-delay: 90ms;" class="mx-auto max-w-xl">
                                        <h4 class="text-xl font-medium tracking-[-0.035em] text-white sm:text-[1.65rem]">
                                            Scoped Automation Access
                                        </h4>
                                        <p class="mx-auto mt-3 max-w-xl text-sm leading-7 text-zinc-300 sm:text-base">
                                            Automation uses scoped deploy tokens and limited machine access instead of broad human-style access.
                                        </p>
                                    </div>

                                    <div data-trust-part="visual" style="--trust-delay: 180ms;" class="mx-auto mt-6 w-full max-w-[46rem]">
                                        <div class="mx-auto w-full max-w-[39rem] overflow-hidden rounded-[1.4rem] border border-white/10 bg-zinc-900 shadow-[0_20px_40px_rgba(0,0,0,0.4),0_8px_16px_rgba(0,0,0,0.3)]">
                                            <div class="flex items-center justify-between gap-4 border-b border-white/10 px-4 py-3.5 text-left">
                                                <span data-terminal-heading class="flex items-center gap-2 text-[0.82rem] font-medium">
                                                    <flux:icon.command-line data-terminal-heading-icon class="h-4 w-4"/>
                                                    Ghostable CLI
                                                </span>
                                                <span class="font-mono text-[0.76rem] text-zinc-300">Scoped token session</span>
                                            </div>
                                            <div class="bg-zinc-950 px-4 py-4 text-left">
                                                <div data-terminal-demo="scoped-automation" data-terminal-script='@json($automationTranscript)' class="font-mono text-[0.78rem] leading-6 sm:text-[0.8rem]">
                                                    <div data-terminal-viewport class="h-[10.75rem] overflow-y-auto">
                                                        <div class="flex min-h-full flex-col justify-end space-y-2">
                                                            <div data-terminal-lines class="space-y-2">
                                                                @foreach ($automationTranscript as $terminalStep)
                                                                    <div class="flex items-start gap-2 text-white/88">
                                                                        <span data-terminal-prompt class="shrink-0">$</span>
                                                                        <span>{{ $terminalStep['command'] }}</span>
                                                                    </div>
                                                                    @foreach ($terminalStep['output'] as $terminalOutput)
                                                                        <div class="pl-5 text-zinc-400">{{ $terminalOutput }}</div>
                                                                    @endforeach
                                                                @endforeach
                                                            </div>
                                                            <div class="flex items-start gap-2 text-white/88">
                                                                <span data-terminal-prompt class="shrink-0">$</span>
                                                                <span data-terminal-command class="min-h-[1.5rem]"></span><span data-terminal-cursor aria-hidden="true" class="-ml-[0.14em] inline-block text-brand">|</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{--
        <section class="border-t border-zinc-200 bg-white py-24 sm:py-28">
            <div class="mx-auto max-w-7xl px-6 lg:px-8">
                <div class="grid gap-8 rounded-[2.5rem] border border-zinc-200 bg-zinc-50 p-8 shadow-[0_20px_60px_rgba(15,23,42,0.05)] lg:grid-cols-[1fr_0.88fr] lg:items-center lg:p-10">
                    <div class="max-w-3xl">
                        <h2 class="text-4xl font-medium tracking-[-0.065em] text-zinc-950 sm:text-6xl sm:leading-[0.95]">
                            Stop babysitting .env files
                        </h2>

                        <p class="mt-6 text-lg leading-8 text-zinc-600 sm:text-xl">
                            Download Ghostable Desktop for macOS and manage environment configuration where it actually happens: in the hands-on work of reviewing, editing, validating, and tracking changes. Create an account, bring in your environments, and keep CI and the terminal for automation, not for day-to-day env management.
                        </p>

                        <div class="mt-8 flex flex-wrap items-center gap-4">
                            <a
                                href="{{ route('desktop.download') }}"
                                class="inline-flex items-center justify-center rounded-lg bg-brand px-6 py-3 text-base font-semibold text-white shadow-[0_14px_36px_color-mix(in_srgb,var(--color-brand)_24%,transparent)] transition hover:-translate-y-0.5 hover:shadow-[0_16px_40px_color-mix(in_srgb,var(--color-brand)_30%,transparent)]"
                            >
                                Download Desktop for macOS
                            </a>

                            <flux:button
                                variant="ghost"
                                class="!border !border-zinc-200 !bg-white !px-6 !py-3 !text-base !font-semibold !text-zinc-950 hover:!bg-zinc-100"
                                href="{{ route('register') }}"
                            >
                                Register
                            </flux:button>
                        </div>
                    </div>

                    <div>
                        <!-- ghostable:graphic-placeholder {"id":"home-v2-cta-product-shot","visual_type":"closing product shot","must_show":"A clean, confident view of the Ghostable Desktop app with enough visible UI to reinforce that this is a real, usable workspace.","communication_goal":"Reinforce the desktop-first argument and make the next step feel obvious and low-friction.","emotional_goal":"The visitor should feel decisive readiness: I can start fixing this today, and it will actually feel good."} -->
                        <x-site.graphic-placeholder
                            identifier="home-v2-cta-product-shot"
                            label="Closing graphic placeholder"
                            title="One clear product shot that makes the next step obvious"
                            copy="Placeholder for the final desktop image that closes the page with confidence."
                            class="min-h-[16rem] sm:min-h-[20rem]"
                        />
                    </div>
                </div>
            </div>
        </section>
        --}}

        <section class="border-t border-zinc-200 bg-white py-24 sm:py-28">
            <div class="mx-auto max-w-3xl px-6 lg:px-8">
                <div class="max-w-xl">
                    <h2 class="text-[1.85rem] font-medium tracking-[-0.045em] text-zinc-950 sm:text-[2.2rem] sm:leading-[1.02]">
                        Frequently asked questions
                    </h2>
                </div>

                <div class="mt-8">
                    <flux:accordion
                        exclusive
                        transition
                        class="[&_[data-flux-accordion-item]]:!border-b-0 [&_[data-flux-accordion-item]]:!border-transparent [&_[data-flux-accordion-heading]]:!text-base sm:[&_[data-flux-accordion-heading]]:!text-[1.05rem]">
                        @foreach($faqItems as $item)
                            <flux:accordion.item>
                                <flux:accordion.heading>{{ $item['question'] }}</flux:accordion.heading>
                                <flux:accordion.content>
                                    <div class="max-w-[34rem] pr-8 text-[0.95rem] leading-7 text-zinc-600 sm:pr-12 sm:text-[0.98rem]">
                                        {!! $item['answer'] !!}
                                    </div>
                                </flux:accordion.content>
                            </flux:accordion.item>
                        @endforeach
                    </flux:accordion>
                </div>
            </div>
        </section>

        {{--
        <section class="bg-black py-20 text-white sm:py-24 lg:py-28">
            <div class="relative mx-auto w-full max-w-7xl px-6 lg:px-8">
                <div class="max-w-[56rem]">
                    <h2 class="text-4xl font-medium tracking-[-0.065em] text-white sm:text-5xl sm:leading-[0.96] lg:text-[3.65rem]">
                        Move environment management into a real workspace.
                    </h2>

                    <p class="mt-5 max-w-3xl text-base leading-7 text-zinc-200 sm:text-lg sm:leading-8">
                        Download Ghostable Desktop for macOS, create an account, and stop routing day-to-day env work through chat threads, deploy settings, and terminal rituals.
                    </p>

                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <a
                            href="{{ route('desktop.download') }}"
                            class="inline-flex items-center justify-center rounded-lg bg-white px-6 py-3 text-base font-semibold text-zinc-950 transition hover:-translate-y-0.5 hover:bg-zinc-100"
                        >
                            Download Desktop for macOS
                        </a>

                        <flux:button
                            variant="ghost"
                            class="!border !border-white/20 !bg-white/8 !px-6 !py-3 !text-base !font-semibold !text-white hover:!bg-white/14"
                            href="{{ route('register') }}"
                        >
                            Register
                        </flux:button>
                    </div>
                </div>
            </div>
        </section>
        --}}

        <section class="overflow-hidden bg-[linear-gradient(180deg,#090b11_0%,#07090d_100%)] py-20 text-white sm:py-24 lg:py-28">
            <div
                class="pointer-events-none absolute inset-x-0 top-0 h-full bg-[radial-gradient(60%_46%_at_50%_18%,color-mix(in_srgb,var(--color-brand)_30%,transparent),transparent_76%)]"
            ></div>
            <div class="relative mx-auto w-full max-w-7xl px-6 lg:px-8">
                <div class="mx-auto flex max-w-[56rem] flex-col items-center text-center">
                    <img
                        src="{{ asset('images/desktop/icon.png') }}"
                        alt="Ghostable Desktop icon"
                        class="h-16 w-16 rounded-[1rem] shadow-[0_0_0_1px_color-mix(in_srgb,var(--color-brand)_10%,transparent),0_28px_80px_color-mix(in_srgb,var(--color-brand)_42%,transparent)] sm:h-20 sm:w-20"
                        loading="lazy"
                    >

                    <h2 class="mt-6 text-4xl font-medium tracking-[-0.065em] text-white sm:text-5xl sm:leading-[0.96] lg:text-[3.65rem]">
                        Stop babysitting .env files
                    </h2>

                    <p class="mt-5 max-w-3xl text-base leading-7 text-white/88 sm:text-lg sm:leading-8">
                        Download Ghostable Desktop for macOS and manage environment configuration where it actually happens: in the hands-on work of reviewing, editing, validating, and tracking changes. Create an account, bring in your environments, and keep CI and the terminal for automation, not for day-to-day env management.
                    </p>

                    <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                        <a
                            href="{{ route('desktop.download') }}"
                            class="inline-flex items-center justify-center rounded-lg bg-white px-6 py-3 text-base font-semibold text-zinc-950 transition hover:-translate-y-0.5 hover:bg-zinc-100"
                        >
                            Download Desktop for macOS
                        </a>

                        <flux:button
                            variant="ghost"
                            class="!border !border-white/25 !bg-white/10 !px-6 !py-3 !text-base !font-semibold !text-white hover:!bg-white/16"
                            href="{{ route('register') }}"
                        >
                            Sign up
                        </flux:button>
                    </div>
                </div>
            </div>
        </section>
    </div>
</x-layouts.guest>
