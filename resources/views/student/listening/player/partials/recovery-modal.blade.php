<div id="listening-recovery-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
        <h2 class="text-lg font-semibold">Recover unsaved answers?</h2>
        <p class="mt-2 text-sm aa-muted">Local draft answers were found that may not be saved on the server.</p>
        <ul id="listening-recovery-list" class="mt-4 list-disc space-y-1 pl-5 text-sm"></ul>
        <div class="mt-6 flex justify-end gap-2">
            <button type="button" id="listening-recovery-discard" class="rounded-xl border px-4 py-2 text-sm">Discard draft</button>
            <button type="button" id="listening-recovery-restore" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-semibold text-white">Restore draft</button>
        </div>
    </div>
</div>
