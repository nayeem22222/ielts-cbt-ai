document.addEventListener('DOMContentLoaded', () => {
    const audio = document.querySelector('[data-review-audio]');
    if (!audio) return;

    const start = parseFloat(audio.dataset.start || '0');
    const end = parseFloat(audio.dataset.end || '0');

    audio.addEventListener('loadedmetadata', () => {
        if (!Number.isNaN(start) && start > 0) {
            audio.currentTime = start;
        }
    });

    if (!Number.isNaN(end) && end > 0) {
        audio.addEventListener('timeupdate', () => {
            if (audio.currentTime >= end) {
                audio.pause();
            }
        });
    }

    audio.addEventListener('contextmenu', (e) => e.preventDefault());
});
