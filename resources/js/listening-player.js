import '../css/listening-player.css';
import { createAutosave } from './listening/autosave';
import { attachAudioGuard } from './listening-audio-guard';
import { createNavigation } from './listening/navigation';
import { createPalette } from './listening/palette';
import { createRecovery } from './listening/recovery';
import { createOfflineSync } from './listening/offline-sync';
import { createTimer } from './listening/timer';
import { createOfficialFlow } from './listening/official-flow';
import { createAudioFlow } from './listening/audio-flow';
import { createListeningReview } from './listening/review';
import { createListeningSubmitModal } from './listening/submit-modal';
import { bindMultipleAnswerLimits } from './listening/multiple-answer';
import { createListeningDragDrop } from './listening/drag-drop';

function readPayload() {
    const root = document.querySelector('[data-listening-player]');
    const raw = root?.getAttribute('data-player-payload');
    if (!raw) return null;
    try {
        return JSON.parse(raw);
    } catch {
        return null;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const payload = readPayload();
    if (!payload) return;

    const state = {
        ...payload,
        activeQuestionNumber: payload.current_question_number ?? 1,
        currentQuestion: payload.current_question_number ?? 1,
        currentSection: payload.current_section_number ?? 1,
    };

    const audioErrorEl = document.getElementById('listening-audio-error');
    const audioTimeEl = document.getElementById('listening-audio-time');
    const audioPanel = document.getElementById('listening-audio-panel');

    const ui = {
        setSaveStatus(text) {
            const el = document.getElementById('listening-save-status');
            if (el) el.textContent = text;
        },
        setAudioStatus(text) {
            const el = document.getElementById('listening-audio-status');
            if (el) el.textContent = text;
        },
        setAudioError(message) {
            if (audioErrorEl) {
                audioErrorEl.textContent = message;
                audioErrorEl.classList.remove('hidden');
            }
            if (audioTimeEl) {
                audioTimeEl.classList.add('hidden');
            }
            audioPanel?.classList.add('is-error');
        },
        clearAudioError() {
            if (audioErrorEl) {
                audioErrorEl.textContent = '';
                audioErrorEl.classList.add('hidden');
            }
            if (audioTimeEl) {
                audioTimeEl.classList.remove('hidden');
            }
            audioPanel?.classList.remove('is-error');
        },
        setUnsyncedCount(count) {
            const el = document.getElementById('listening-unsynced-count');
            if (el) el.textContent = String(count);
            const submitEl = document.getElementById('listening-submit-unsynced');
            if (submitEl) submitEl.textContent = String(count);
        },
        showOffline() {
            document.getElementById('listening-offline-banner')?.classList.remove('hidden');
        },
        hideOffline() {
            document.getElementById('listening-offline-banner')?.classList.add('hidden');
        },
    };

    let review;
    const palette = createPalette(state);
    const offlineSync = createOfflineSync(state, ui);
    const autosave = createAutosave(state, ui, palette, offlineSync, {
        updateFromPalette: (items) => review?.updateFromPalette(items),
    });
    const questionArea = document.getElementById('listening-question-area');
    const dragDrop = createListeningDragDrop({
        questionArea,
        saveNow: autosave.saveNow,
        palette,
    });
    const navigation = createNavigation(state, ui, autosave, palette, {
        afterQuestionChange: () => dragDrop.restore(questionArea),
    });
    review = createListeningReview(state, navigation, palette);
    const submitModal = createListeningSubmitModal(state, autosave, navigation, review);
    const officialFlow = createOfficialFlow(state, ui);
    const timer = createTimer(state, ui, autosave, officialFlow);
    const recovery = createRecovery(state, autosave, navigation);
    const audioFlow = createAudioFlow(state, ui);
    const audioEl = document.getElementById('listening-audio-element');
    const audio = attachAudioGuard(audioEl, state, ui);

    audioFlow.bindAudioElement(audioEl, () => state.currentSection);

    bindMultipleAnswerLimits(questionArea ?? document);
    dragDrop.bind(questionArea);

    palette.bind();
    navigation.bind();
    review.bind();
    submitModal.bind();
    navigation.showQuestion(state.activeQuestionNumber, 'resume');
    dragDrop.restore(questionArea);
    recovery.init();
    timer.bind();
    officialFlow.applyPhase(state.official_timer ?? {}, state.phase ?? {});

    const currentSectionData = state.sections?.find((s) => s.number === state.currentSection);
    const audioStartBtn = document.getElementById('listening-audio-start');
    const audioPlayBtn = document.getElementById('listening-audio-play');

    if (currentSectionData && !currentSectionData.has_playable_audio) {
        ui.setAudioError('Audio file is not available. Please contact admin.');
        if (audioStartBtn) audioStartBtn.disabled = true;
        if (audioPlayBtn) audioPlayBtn.disabled = true;
    } else if (audioStartBtn && (state.official_timer?.can_play_audio ?? state.phase?.can_play_audio ?? false)) {
        ui.clearAudioError();
        if (audioStartBtn) audioStartBtn.disabled = false;
        if (audioPlayBtn) audioPlayBtn.disabled = false;
    }

    document.getElementById('listening-audio-start')?.addEventListener('click', async () => {
        const startBtn = document.getElementById('listening-audio-start');
        if (!startBtn || startBtn.disabled) return;

        const ok = await audioFlow.markStarted(state.currentSection);
        if (!ok) return;

        ui.clearAudioError();
        audio?.loadSection(state.currentSection);
        audio?.start();
        startBtn.disabled = true;
    });

    const saveAnswerFromInput = (input) => {
        const questionId = Number(input.dataset.questionId);
        let answer = input.value;

        if (input.type === 'checkbox') {
            answer = [...document.querySelectorAll(`input[data-question-id="${questionId}"]:checked`)].map((el) => ({
                value: el.value,
                type: 'letter',
            }));
        } else if (input.dataset.itemKey) {
            answer = [{ item_key: input.dataset.itemKey, value: input.value, type: 'matching' }];
        } else if (input.type === 'radio' || input.dataset.answerType === 'letter') {
            answer = [{ value: input.value, type: 'letter' }];
        } else {
            answer = [{ value: input.value, type: 'text' }];
        }

        autosave.queueSave(questionId, answer);
    };

    questionArea?.addEventListener('input', (event) => {
        const input = event.target.closest('.listening-answer-input');
        if (!input) return;
        saveAnswerFromInput(input);
    });

    questionArea?.addEventListener('change', (event) => {
        const input = event.target.closest('.listening-answer-input');
        if (!input) return;
        saveAnswerFromInput(input);
    });

    questionArea?.addEventListener('blur', (event) => {
        const input = event.target.closest('.listening-answer-input');
        if (!input) return;
        saveAnswerFromInput(input);
    }, true);

    const toggleQuestionFlag = async (questionId, button) => {
        const question = state.questions?.find((q) => Number(q.id) === Number(questionId));
        if (!question) return;

        const flagged = !question.is_flagged;
        const url = state.routes.flag.replace('__QUESTION__', String(questionId));

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                },
                body: JSON.stringify({ flagged }),
            });
            const data = await res.json();
            question.is_flagged = flagged;
            button?.classList.toggle('is-flagged', flagged);
            button?.setAttribute('aria-pressed', flagged ? 'true' : 'false');
            if (data.palette) {
                palette.update(data.palette);
                review.updateFromPalette(data.palette);
            }
        } catch {
            ui.setSaveStatus('Offline');
        }
    };

    document.querySelectorAll('.listening-row-flag').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.stopPropagation();
            toggleQuestionFlag(Number(button.dataset.questionId), button);
        });
    });

    document.getElementById('listening-loading-overlay')?.classList.add('hidden');
});
