@php
    $isTransfer = ($payload['phase']['current_phase'] ?? 'listening') === 'transfer';
@endphp

<div
    id="listening-phase-banner"
    class="listening-info-strip @if($isTransfer) hidden @endif"
>
    <span id="listening-phase-banner-text">You are now in the Listening section. Audio can be played once.</span>
</div>

<div id="listening-transfer-banner" class="listening-info-strip is-transfer @if(! $isTransfer) hidden @endif">
    Transfer time — review and finalize your answers before submission.
</div>
