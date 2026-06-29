<div id="listening-timer" class="rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-2 text-center" data-expires-at="{{ $payload['timer']['expires_at'] ?? '' }}" data-remaining="{{ $payload['timer']['remaining_seconds'] ?? 0 }}">
    <p class="text-[10px] uppercase tracking-wide aa-muted">Time remaining</p>
    <p id="listening-timer-display" class="font-mono text-lg font-bold">--:--</p>
</div>
