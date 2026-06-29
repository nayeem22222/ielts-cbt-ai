function formatTime(seconds) {
    if (!Number.isFinite(seconds) || seconds < 0) {
        return '0:00';
    }

    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);

    return `${mins}:${String(secs).padStart(2, '0')}`;
}

export function attachAudioGuard(audioEl, state, ui) {
    if (!audioEl) {
        return null;
    }

    const progressEl = document.getElementById('listening-audio-progress');
    const timeEl = document.getElementById('listening-audio-time');
    const playBtn = document.getElementById('listening-audio-play');
    const playIcon = document.getElementById('listening-audio-play-icon');
    const pauseIcon = document.getElementById('listening-audio-pause-icon');
    const volumeBtn = document.getElementById('listening-audio-volume');
    const volumeOn = document.getElementById('listening-audio-volume-on');
    const volumeOff = document.getElementById('listening-audio-volume-off');
    const panel = document.getElementById('listening-audio-panel');

    let lastValidTime = 0;
    let ended = false;
    let muted = false;

    const setPlayingUi = (playing) => {
        playIcon?.classList.toggle('hidden', playing);
        pauseIcon?.classList.toggle('hidden', !playing);
        playBtn?.setAttribute('aria-label', playing ? 'Pause audio' : 'Play audio');
    };

    const updateProgress = () => {
        const duration = audioEl.duration;
        const current = audioEl.currentTime;

        if (Number.isFinite(duration) && duration > 0) {
            const pct = (current / duration) * 100;
            if (progressEl) {
                progressEl.style.width = `${pct}%`;
            }
            const track = progressEl?.parentElement;
            if (track) {
                track.setAttribute('aria-valuenow', String(Math.round(pct)));
            }
        }

        if (timeEl) {
            const total = Number.isFinite(duration) && duration > 0 ? duration : 0;
            timeEl.textContent = `${formatTime(current)} / ${formatTime(total)}`;
        }
    };

    const showError = (message) => {
        ui.setAudioError?.(message);
        setPlayingUi(false);
        if (playBtn) {
            playBtn.disabled = true;
        }
        panel?.classList.add('is-error');
    };

    audioEl.addEventListener('contextmenu', (e) => e.preventDefault());

    audioEl.addEventListener('ratechange', () => {
        audioEl.playbackRate = 1;
    });

    audioEl.addEventListener('seeking', () => {
        if (!state.config?.allow_audio_seek && audioEl.currentTime > lastValidTime + 0.25) {
            audioEl.currentTime = lastValidTime;
        }
    });

    audioEl.addEventListener('timeupdate', () => {
        if (!audioEl.paused) {
            lastValidTime = audioEl.currentTime;
        }
        updateProgress();
    });

    audioEl.addEventListener('loadedmetadata', updateProgress);

    audioEl.addEventListener('play', () => {
        if (ended && !state.config?.allow_audio_replay) {
            audioEl.pause();
            return;
        }
        setPlayingUi(true);
    });

    audioEl.addEventListener('pause', () => {
        setPlayingUi(false);
    });

    audioEl.addEventListener('ended', () => {
        ended = true;
        setPlayingUi(false);
        ui.setAudioStatus('Audio finished. Replay is disabled in official mode.');
        if (playBtn) {
            playBtn.disabled = true;
        }
    });

    audioEl.addEventListener('error', () => {
        showError('Audio file is not available. Please contact admin.');
    });

    volumeBtn?.addEventListener('click', () => {
        muted = !muted;
        audioEl.muted = muted;
        volumeBtn.classList.toggle('is-muted', muted);
        volumeOn?.classList.toggle('hidden', muted);
        volumeOff?.classList.toggle('hidden', !muted);
    });

    const volumeRange = document.getElementById('listening-audio-volume-range');
    volumeRange?.addEventListener('input', () => {
        const level = Number(volumeRange.value);
        audioEl.volume = level;
        audioEl.muted = level === 0;
        muted = level === 0;
        volumeBtn?.classList.toggle('is-muted', muted);
        volumeOn?.classList.toggle('hidden', muted);
        volumeOff?.classList.toggle('hidden', !muted);
    });

    playBtn?.addEventListener('click', () => {
        if (playBtn.disabled) {
            return;
        }

        if (audioEl.paused) {
            document.getElementById('listening-audio-start')?.click();
        } else {
            audioEl.pause();
        }
    });

    return {
        loadSection(sectionNumber) {
            const section = state.sections?.find((s) => s.number === sectionNumber);
            if (!section?.audio_stream_url) {
                showError('Audio file is not available. Please contact admin.');
                return;
            }

            ended = false;
            lastValidTime = 0;
            panel?.classList.remove('is-error');
            audioEl.src = section.audio_stream_url;
            audioEl.load();
            updateProgress();
        },
        start() {
            if (ended && !state.config?.allow_audio_replay) {
                return;
            }

            audioEl.play().catch(() => {
                showError('Audio file is not available. Please contact admin.');
            });
        },
        updateProgress,
    };
}
