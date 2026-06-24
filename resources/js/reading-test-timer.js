const SYNC_INTERVAL_MS = 30000;

export function createReadingTestTimer(component) {
    let tickTimer = null;
    let syncTimer = null;

    const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    const formatTime = (seconds) => {
        const safe = Math.max(0, Number(seconds) || 0);
        const minutes = Math.floor(safe / 60);
        const secs = safe % 60;
        return `${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    };

    const timerClass = () => {
        if (component.isLocked) {
            return 'reading-test-timer is-locked';
        }
        if (component.remainingSeconds <= 60) {
            return 'reading-test-timer is-danger';
        }
        if (component.remainingSeconds <= 600) {
            return 'reading-test-timer is-warning';
        }
        return 'reading-test-timer';
    };

    const lockInputs = () => {
        component.isLocked = true;
        document.querySelectorAll('.reading-test-input, .reading-test-flag-btn').forEach((el) => {
            el.disabled = true;
        });
    };

    const triggerAutoSubmit = async () => {
        if (component.autoSubmitting || component.isLocked) {
            return;
        }

        component.autoSubmitting = true;
        lockInputs();

        try {
            const response = await fetch(component.endpoints.autoSubmit, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
            });

            const body = await response.json();

            if (!response.ok) {
                component.saveWarning = body?.message ?? 'Time is up but submission failed. Please refresh or submit manually.';
                return;
            }

            const redirect = body?.data?.redirect_url;
            if (redirect) {
                window.location.href = redirect;
            }
        } catch {
            component.saveWarning = 'Time is up. Please refresh to complete submission.';
        }
    };

    const applyTimerPayload = (payload) => {
        if (!payload) {
            return;
        }

        component.remainingSeconds = payload.remaining_seconds ?? component.remainingSeconds;
        component.timerLabel = formatTime(component.remainingSeconds);
        component.timerClassName = timerClass();
        component.attemptStatus = payload.status ?? component.attemptStatus;

        if (payload.expired || (component.remainingSeconds <= 0 && component.attemptStatus === 'in_progress')) {
            triggerAutoSubmit();
        }

        if (['submitted', 'completed'].includes(component.attemptStatus)) {
            lockInputs();
        }
    };

    const syncWithServer = async () => {
        if (!component.endpoints?.timer || component.isLocked) {
            return;
        }

        try {
            const response = await fetch(component.endpoints.timer, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                return;
            }

            const body = await response.json();
            applyTimerPayload(body.data);
        } catch {
            // Keep local countdown running.
        }
    };

    const tick = () => {
        if (component.isLocked) {
            return;
        }

        component.remainingSeconds = Math.max(0, component.remainingSeconds - 1);
        component.timerLabel = formatTime(component.remainingSeconds);
        component.timerClassName = timerClass();

        if (component.remainingSeconds <= 0) {
            triggerAutoSubmit();
        }
    };

    const start = () => {
        const initial = component.timer?.remaining_seconds ?? component.durationMinutes * 60;
        component.remainingSeconds = initial;
        component.timerLabel = formatTime(initial);
        component.timerClassName = timerClass();

        if (component.isLocked || ['submitted', 'completed'].includes(component.attemptStatus)) {
            lockInputs();
            return;
        }

        if (component.remainingSeconds <= 0) {
            triggerAutoSubmit();
            return;
        }

        tickTimer = window.setInterval(tick, 1000);
        syncTimer = window.setInterval(syncWithServer, SYNC_INTERVAL_MS);
        syncWithServer();
    };

    const destroy = () => {
        if (tickTimer) {
            clearInterval(tickTimer);
        }
        if (syncTimer) {
            clearInterval(syncTimer);
        }
    };

    return {
        start,
        destroy,
        syncWithServer,
        formatTime,
        lockInputs,
    };
}
