const csrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

export function createAudioFlow(state, ui) {
    const sectionPlayed = new Set();

    const markStarted = async (sectionNumber) => {
        if (sectionPlayed.has(sectionNumber)) return false;
        const res = await fetch(state.routes.audio_start, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf(),
            },
            body: JSON.stringify({ section_number: sectionNumber }),
        });
        if (!res.ok) {
            ui.setAudioStatus('Audio cannot be started in this phase.');
            return false;
        }
        sectionPlayed.add(sectionNumber);
        return true;
    };

    const markEnded = async (sectionNumber, position = 0) => {
        await fetch(state.routes.audio_end, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf(),
            },
            body: JSON.stringify({ section_number: sectionNumber, position }),
        });
    };

    const restoreFromPayload = () => {
        const flow = state.audio_flow ?? {};
        Object.entries(flow).forEach(([key, value]) => {
            if ((value?.play_count ?? 0) > 0) {
                const section = Number(String(key).replace('section_', ''));
                if (section > 0) sectionPlayed.add(section);
            }
        });
    };

    const bindAudioElement = (audioEl, getSection) => {
        if (!audioEl) return;

        audioEl.addEventListener('play', () => {
            audioEl.playbackRate = 1;
        });

        audioEl.addEventListener('ended', () => {
            markEnded(getSection(), audioEl.currentTime);
            ui.setAudioStatus('Audio completed for this section.');
            audioEl.setAttribute('disabled', 'disabled');
        });

        let lastValid = 0;
        audioEl.addEventListener('timeupdate', () => {
            if (audioEl.currentTime < lastValid - 0.5) {
                audioEl.currentTime = lastValid;
            } else {
                lastValid = audioEl.currentTime;
            }
        });
    };

    restoreFromPayload();

    return { markStarted, markEnded, bindAudioElement, sectionPlayed };
}
