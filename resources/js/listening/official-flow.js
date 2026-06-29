const csrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

export function createOfficialFlow(state, ui) {
    let enteringTransfer = false;

    const applyPhase = (timerState, phaseState) => {
        const phase = timerState.current_phase ?? phaseState?.current_phase ?? 'listening';
        const banner = document.getElementById('listening-phase-banner-text');
        const phaseStrip = document.getElementById('listening-phase-banner');
        const transferBanner = document.getElementById('listening-transfer-banner');

        if (phase === 'transfer') {
            if (banner) banner.textContent = 'You are now in transfer time. Please finalize your answers.';
            phaseStrip?.classList.add('is-transfer', 'hidden');
            transferBanner?.classList.remove('hidden');
            document.getElementById('listening-audio-start')?.setAttribute('disabled', 'disabled');
            document.getElementById('listening-audio-play')?.setAttribute('disabled', 'disabled');
        } else if (phase === 'listening') {
            if (banner) banner.textContent = 'You are now in the Listening section. Audio can be played once.';
            phaseStrip?.classList.remove('is-transfer', 'hidden');
            transferBanner?.classList.add('hidden');
        } else if (phase === 'submitted' || phase === 'expired') {
            document.querySelectorAll('.listening-answer-input').forEach((input) => {
                input.setAttribute('disabled', 'disabled');
            });
        }

        const canPlay = timerState.can_play_audio ?? phaseState?.can_play_audio ?? false;
        const startBtn = document.getElementById('listening-audio-start');
        if (startBtn && !canPlay) startBtn.disabled = true;
    };

    const enterTransfer = async () => {
        if (enteringTransfer || state.phase?.current_phase === 'transfer') return;
        enteringTransfer = true;
        try {
            const res = await fetch(state.routes.phase_transfer, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
            });
            const data = await res.json();
            if (data.phase) state.phase = data.phase;
            if (data.timer) state.official_timer = data.timer;
            applyPhase(data.timer ?? {}, data.phase ?? {});
        } finally {
            enteringTransfer = false;
        }
    };

    return { applyPhase, enterTransfer };
}
