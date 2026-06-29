<div class="listening-audio-bar" id="listening-audio-panel">
    <div class="listening-audio-bar-inner">
        <button type="button" id="listening-audio-play" class="listening-audio-play-btn" aria-label="Play audio" disabled>
            <svg id="listening-audio-play-icon" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M8 5v14l11-7z"/>
            </svg>
            <svg id="listening-audio-pause-icon" class="hidden" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M6 5h4v14H6V5zm8 0h4v14h-4V5z"/>
            </svg>
        </button>
        <div class="listening-audio-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
            <div class="listening-audio-progress" id="listening-audio-progress"></div>
        </div>
        <p id="listening-audio-time" class="listening-audio-time">0:00 / 0:00</p>
        <button type="button" id="listening-audio-volume" class="listening-audio-volume" aria-label="Toggle mute">
            <svg id="listening-audio-volume-on" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/>
                <path d="M15.54 8.46a5 5 0 0 1 0 7.07"/>
            </svg>
            <svg id="listening-audio-volume-off" class="hidden" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/>
                <line x1="23" y1="9" x2="17" y2="15"/>
                <line x1="17" y1="9" x2="23" y2="15"/>
            </svg>
        </button>
        <input type="range" id="listening-audio-volume-range" class="listening-audio-volume-range" min="0" max="1" step="0.05" value="1" aria-label="Volume">
        <p id="listening-audio-error" class="listening-audio-error hidden" role="alert"></p>
        <button type="button" id="listening-audio-start" class="sr-only" disabled>Start Audio</button>
        <audio id="listening-audio-element" class="hidden" preload="metadata" controlslist="nodownload noplaybackrate" oncontextmenu="return false;"></audio>
    </div>
</div>
