document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('[data-transcript-review]');
    if (!root) return;

    const disableCopy = root.hasAttribute('data-disable-copy');
    if (disableCopy) {
        root.addEventListener('copy', (e) => e.preventDefault());
        root.style.userSelect = 'none';
    }

    const toggle = root.querySelector('[data-toggle-snippet]');
    const snippet = root.querySelector('[data-transcript-snippet]');
    if (toggle && snippet) {
        toggle.addEventListener('click', () => {
            snippet.classList.toggle('hidden');
        });
    }

    const firstHighlighted = root.querySelector('[data-line][data-line]:not([data-line=""])');
    if (firstHighlighted) {
        firstHighlighted.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
});
