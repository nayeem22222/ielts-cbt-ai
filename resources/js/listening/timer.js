const csrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

function formatTime(seconds) {
    const total = Math.max(0, Math.floor(seconds));
    const h = Math.floor(total / 3600);
    const m = Math.floor((total % 3600) / 60).toString().padStart(2, '0');
    const s = (total % 60).toString().padStart(2, '0');
    return h > 0 ? `${h}:${m}:${s}` : `${m}:${s}`;
}

export function createTimer(state, ui, autosave, officialFlow) {
    const config = state.config?.official_flow ?? {};
    const thresholds = config.warning_thresholds_seconds ?? [600, 300, 60];
    const syncIntervalMs = (config.server_timer_sync_interval_seconds ?? 20) * 1000;
    let timerState = state.official_timer ?? {};
    let warned = new Set();
    let syncTimer = null;

    const applyState = (next) => {
        timerState = { ...timerState, ...next };
        state.official_timer = timerState;

        const displaySeconds = timerState.current_phase === 'transfer'
            ? timerState.transfer_remaining_seconds ?? timerState.total_remaining_seconds
            : timerState.listening_remaining_seconds ?? timerState.total_remaining_seconds;

        const el = document.getElementById('listening-timer-display');
        const timerBadge = document.getElementById('listening-official-timer');
        if (el) {
            el.textContent = formatTime(displaySeconds ?? 0);
        }

        if (timerBadge) {
            timerBadge.classList.remove('is-warning', 'is-danger');
            const secs = displaySeconds ?? 0;
            if (secs <= 60) {
                timerBadge.classList.add('is-danger');
            } else if (secs <= 300) {
                timerBadge.classList.add('is-warning');
            }
        }

        const phaseLabel = document.getElementById('listening-timer-phase-label');
        if (phaseLabel) phaseLabel.textContent = timerState.current_phase_label ?? 'Listening Time';

        officialFlow?.applyPhase(timerState, state.phase);

        thresholds.forEach((seconds) => {
            if ((timerState.total_remaining_seconds ?? 0) <= seconds && !warned.has(seconds)) {
                warned.add(seconds);
                showWarning(seconds);
            }
        });

        if (timerState.should_enter_transfer) {
            officialFlow?.enterTransfer();
        }

        if (timerState.should_auto_submit || timerState.is_expired) {
            handleExpiry();
        }
    };

    const showWarning = (seconds) => {
        const modal = document.getElementById('listening-time-warning-modal');
        const message = document.getElementById('listening-time-warning-message');
        if (!modal || !message) return;
        const minutes = Math.floor(seconds / 60);
        message.textContent = minutes > 0 ? `${minutes} minute(s) remaining.` : 'Less than one minute remaining.';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    const handleExpiry = async () => {
        clearInterval(syncTimer);
        await autosave?.flushBeforeNavigation?.();
        await fetch(state.routes.auto_submit, {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
        }).then((res) => res.json()).then((data) => {
            if (data.redirect) window.location.href = data.redirect;
        });
    };

    const sync = async () => {
        try {
            const res = await fetch(state.routes.timer_sync, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                },
                body: JSON.stringify({
                    client_remaining_seconds: timerState.total_remaining_seconds,
                    client_phase: timerState.current_phase,
                }),
            });
            if (!res.ok) return;
            const data = await res.json();
            if (data.timer) applyState(data.timer);
            if (data.phase) state.phase = data.phase;
        } catch {
            // Server remains source of truth on next sync.
        }
    };

    const tick = () => {
        if (timerState.total_remaining_seconds > 0) {
            timerState = {
                ...timerState,
                total_remaining_seconds: Math.max(0, (timerState.total_remaining_seconds ?? 0) - 1),
                listening_remaining_seconds: Math.max(0, (timerState.listening_remaining_seconds ?? 0) - 1),
                transfer_remaining_seconds: Math.max(0, (timerState.transfer_remaining_seconds ?? 0) - 1),
            };
            applyState(timerState);
        }
    };

    const bind = () => {
        document.getElementById('listening-time-warning-dismiss')?.addEventListener('click', () => {
            document.getElementById('listening-time-warning-modal')?.classList.add('hidden');
        });
        applyState(timerState);
        setInterval(tick, 1000);
        syncTimer = setInterval(sync, syncIntervalMs);
    };

    return { bind, applyState, sync };
}
