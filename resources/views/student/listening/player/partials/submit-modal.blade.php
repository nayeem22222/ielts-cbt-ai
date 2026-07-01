<div id="listening-submit-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
        <h2 class="text-xl font-bold text-neutral-900">Submit Listening Test?</h2>
        <p class="mt-2 text-sm text-neutral-600">Review your progress before final submission. Your answers will be scored and your result will be shown immediately.</p>

        <dl class="mt-5 grid grid-cols-3 gap-3 text-center text-sm">
            <div class="rounded-lg bg-emerald-50 p-3">
                <dt class="font-medium text-emerald-800">Answered</dt>
                <dd id="listening-submit-answered" class="text-lg font-bold">{{ $payload['recovery']['counts']['answered'] ?? 0 }}</dd>
            </div>
            <div class="rounded-lg bg-amber-50 p-3">
                <dt class="font-medium text-amber-800">Unanswered</dt>
                <dd id="listening-submit-unanswered" class="text-lg font-bold">{{ $payload['recovery']['counts']['unanswered'] ?? 0 }}</dd>
            </div>
            <div class="rounded-lg bg-yellow-50 p-3">
                <dt class="font-medium text-yellow-800">Flagged</dt>
                <dd id="listening-submit-flagged" class="text-lg font-bold">{{ $payload['recovery']['counts']['flagged'] ?? 0 }}</dd>
            </div>
        </dl>

        <div id="listening-submit-unanswered-block" class="mt-5 hidden space-y-3 text-sm">
            <p class="font-semibold text-amber-800">Unanswered questions</p>
            <p id="listening-submit-unanswered-list" class="text-neutral-600"></p>
        </div>

        <div id="listening-submit-flagged-block" class="mt-3 hidden space-y-3 text-sm">
            <p class="font-semibold text-yellow-800">Flagged questions</p>
            <p id="listening-submit-flagged-list" class="text-neutral-600"></p>
        </div>

        <div class="mt-6 flex flex-wrap justify-end gap-2">
            <button
                type="button"
                id="listening-submit-cancel"
                class="rounded-lg border border-neutral-300 px-4 py-2 text-sm font-semibold text-neutral-700 hover:bg-neutral-50"
            >Cancel</button>
            <button
                type="button"
                id="listening-submit-review-unanswered"
                class="hidden rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-900 hover:bg-amber-100"
            >Review unanswered</button>
            <button
                type="button"
                id="listening-submit-confirm"
                class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-60"
            >Submit test</button>
        </div>
    </div>
</div>
