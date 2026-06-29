export function createOfflineSync(state, ui, autosave) {
    let offline = !navigator.onLine;
    const banner = () => document.getElementById('listening-offline-banner');
    const queue = [];

    const markOffline = () => {
        offline = true;
        banner()?.classList.remove('hidden');
        ui.setSaveStatus('Offline');
    };

    const markOnline = () => {
        offline = false;
        banner()?.classList.add('hidden');
        ui.setSaveStatus('Saved');
        flushQueue();
    };

    const flushQueue = async () => {
        while (queue.length > 0) {
            const job = queue.shift();
            await job();
        }
        await autosave.bulkFlush();
    };

    const enqueue = (job) => {
        queue.push(job);
    };

    window.addEventListener('offline', markOffline);
    window.addEventListener('online', markOnline);

    if (offline) markOffline();

    return { markOffline, markOnline, enqueue, isOffline: () => offline };
}
