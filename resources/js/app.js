import AOS from 'aos';

AOS.init();

const initializeDocumentationOutline = () => {
    const outline = document.querySelector('[data-docs-on-this-page]');

    if (!outline || outline.hasAttribute('data-docs-scrollspy-ready')) {
        return;
    }

    const links = Array.from(outline.querySelectorAll('[data-docs-outline-link]'));
    const sections = links
        .map((link) => ({
            link,
            section: document.getElementById(link.hash.slice(1)),
        }))
        .filter(({ section }) => section !== null);

    if (sections.length === 0) {
        return;
    }

    outline.setAttribute('data-docs-scrollspy-ready', '');

    const setActiveSection = (activeLink) => {
        links.forEach((link) => {
            const isActive = link === activeLink;

            link.toggleAttribute('data-active', isActive);

            if (isActive) {
                link.setAttribute('aria-current', 'location');
            } else {
                link.removeAttribute('aria-current');
            }
        });
    };

    let animationFrame = null;

    const updateActiveSection = () => {
        animationFrame = null;

        const header = document.querySelector('[data-docs-header]');
        const activationBoundary = (header?.getBoundingClientRect().bottom ?? 0) + 32;
        const isAtPageBottom = window.scrollY + window.innerHeight >= document.documentElement.scrollHeight - 2;
        let activeSection = sections[0];

        for (const section of sections) {
            if (section.section.getBoundingClientRect().top > activationBoundary) {
                break;
            }

            activeSection = section;
        }

        if (isAtPageBottom) {
            activeSection = sections.at(-1);
        }

        setActiveSection(activeSection.link);
    };

    const scheduleUpdate = () => {
        if (animationFrame !== null) {
            return;
        }

        animationFrame = window.requestAnimationFrame(updateActiveSection);
    };

    links.forEach((link) => {
        link.addEventListener('click', () => setActiveSection(link));
    });

    window.addEventListener('scroll', scheduleUpdate, { passive: true });
    window.addEventListener('resize', scheduleUpdate);
    window.addEventListener('hashchange', scheduleUpdate);

    updateActiveSection();
};

const copyDocumentationText = async (content) => {
    if (navigator.clipboard?.writeText) {
        try {
            await navigator.clipboard.writeText(content);

            return true;
        } catch {
            // Fall through for browsers and embedded webviews without clipboard permission.
        }
    }

    const textarea = document.createElement('textarea');
    textarea.value = content;
    textarea.setAttribute('readonly', '');
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.append(textarea);
    textarea.select();

    const copied = document.execCommand('copy');
    textarea.remove();

    return copied;
};

const initializeDocumentationCopyButtons = () => {
    document.querySelectorAll('[data-docs-terminal-copy]').forEach((button) => {
        if (button.hasAttribute('data-docs-terminal-copy-ready')) {
            return;
        }

        button.setAttribute('data-docs-terminal-copy-ready', '');
        button.addEventListener('click', async () => {
            const terminal = button.closest('[data-docs-terminal]');
            const label = button.querySelector('[data-docs-terminal-copy-label]');
            const content = terminal?.dataset.copyContent;

            if (! content || ! label) {
                return;
            }

            if (! await copyDocumentationText(content)) {
                label.textContent = 'Unable to copy';
                button.setAttribute('aria-label', 'Unable to copy commands');

                return;
            }

            label.textContent = 'Copied';
            button.setAttribute('aria-label', 'Copied commands');

            window.setTimeout(() => {
                label.textContent = 'Copy';
                button.setAttribute('aria-label', 'Copy commands');
            }, 1500);
        });
    });
};

const initializeDocumentation = () => {
    initializeDocumentationOutline();
    initializeDocumentationCopyButtons();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeDocumentation, { once: true });
} else {
    initializeDocumentation();
}
