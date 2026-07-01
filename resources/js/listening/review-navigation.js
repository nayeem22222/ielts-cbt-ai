document.addEventListener('DOMContentLoaded', () => {
    const rows = Array.from(document.querySelectorAll('[data-question-row]'));
    if (rows.length === 0) return;

    let sectionFilter = 'all';
    let matchFilter = 'all';

    const applyFilters = () => {
        rows.forEach((row) => {
            const section = String(row.dataset.section || '');
            const match = String(row.dataset.match || '');
            const sectionOk = sectionFilter === 'all' || section === sectionFilter;
            const matchOk = matchFilter === 'all'
                || (matchFilter === 'incorrect' && match === 'incorrect')
                || (matchFilter === 'unanswered' && match === 'unanswered');
            row.classList.toggle('hidden', !(sectionOk && matchOk));
        });
    };

    document.querySelectorAll('[data-section-filter]').forEach((btn) => {
        btn.addEventListener('click', () => {
            sectionFilter = btn.getAttribute('data-section-filter') || 'all';
            applyFilters();
        });
    });

    document.querySelectorAll('[data-match-filter]').forEach((btn) => {
        btn.addEventListener('click', () => {
            matchFilter = btn.getAttribute('data-match-filter') || 'all';
            applyFilters();
        });
    });
});
