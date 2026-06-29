<div id="listening-submit-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
        <h2 class="text-lg font-semibold">Submit listening test?</h2>
        <p class="mt-2 text-sm aa-muted">You will not be able to change your answers after submission.</p>
        <ul class="mt-4 space-y-1 text-sm aa-muted">
            <li>Unanswered: <strong id="listening-submit-unanswered">{{ $payload['recovery']['counts']['unanswered'] ?? 0 }}</strong></li>
            <li>Flagged: <strong id="listening-submit-flagged">{{ $payload['recovery']['counts']['flagged'] ?? 0 }}</strong></li>
            <li>Unsynced: <strong id="listening-submit-unsynced">0</strong></li>
        </ul>
        <form id="listening-submit-form" method="POST" action="{{ $payload['routes']['submit'] ?? '#' }}" class="mt-6 flex justify-end gap-2">
            @csrf
            <button type="button" id="listening-submit-cancel" class="rounded-xl border px-4 py-2 text-sm">Cancel</button>
            <button type="submit" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white">Submit now</button>
        </form>
    </div>
</div>
