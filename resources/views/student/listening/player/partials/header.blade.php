@php
    $testTypeLabel = strtoupper((string) ($payload['test_type_label'] ?? 'ACADEMIC'));
@endphp

<header class="listening-exam-header">
    <div class="listening-exam-header-inner">
        <div class="listening-exam-header-brand">
            <span class="listening-exam-header-icon" aria-hidden="true">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                    <path d="M3 18v-6a9 9 0 0 1 18 0v6"/>
                    <path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3v5zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3v5z"/>
                </svg>
            </span>
            <div class="listening-exam-header-titles">
                <h1 class="listening-exam-title">{{ $payload['test_title'] ?? $attempt->test?->title }}</h1>
                <p class="listening-exam-meta">{{ $testTypeLabel }} · LISTENING</p>
            </div>
        </div>

        <div class="listening-exam-header-center">
            @include('student.listening.player.partials.official-timer', ['payload' => $payload])
        </div>

        <div class="listening-exam-header-actions">
            <button type="button" class="listening-icon-btn" title="Help" aria-label="Help">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </button>
            <button type="button" id="listening-review-open" class="listening-header-btn listening-header-btn-outline">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                Review
            </button>
            <button type="button" id="listening-submit-open" class="listening-header-btn listening-header-btn-submit">
                Submit
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
            </button>
            <span id="listening-save-status" class="sr-only">Saved</span>
            <span id="listening-unsynced-count" class="sr-only">0</span>
        </div>
    </div>
</header>
