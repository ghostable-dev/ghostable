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

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeDocumentationOutline, { once: true });
} else {
    initializeDocumentationOutline();
}
