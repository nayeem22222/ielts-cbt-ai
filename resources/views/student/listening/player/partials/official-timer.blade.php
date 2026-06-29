<div id="listening-official-timer" class="listening-exam-timer" data-timer-state='@json($payload['official_timer'] ?? [])'>
    <svg class="listening-timer-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
    <span id="listening-timer-display" class="listening-timer-text">--:--</span>
    <span id="listening-timer-phase-label" class="sr-only">{{ $payload['phase']['current_phase_label'] ?? 'Listening Time' }}</span>
</div>
