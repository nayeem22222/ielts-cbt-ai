<div x-show="submitModalOpen" x-cloak class="pointer-events-auto fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="closeSubmitModal()">
    <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
        <h2 class="text-xl font-bold text-neutral-900">Submit Reading Test?</h2>
        <p class="mt-2 text-sm text-neutral-600">Review your progress before final submission. Your answers will be scored immediately and you will see your band result.</p>

        <dl class="mt-5 grid grid-cols-3 gap-3 text-center text-sm">
            <div class="rounded-lg bg-emerald-50 p-3"><dt class="font-medium text-emerald-800">Answered</dt><dd class="text-lg font-bold" x-text="review.summary?.answered ?? 0"></dd></div>
            <div class="rounded-lg bg-amber-50 p-3"><dt class="font-medium text-amber-800">Unanswered</dt><dd class="text-lg font-bold" x-text="review.summary?.unanswered ?? 0"></dd></div>
            <div class="rounded-lg bg-yellow-50 p-3"><dt class="font-medium text-yellow-800">Flagged</dt><dd class="text-lg font-bold" x-text="review.summary?.flagged ?? 0"></dd></div>
        </dl>

        <div class="mt-5 space-y-3 text-sm" x-show="(review.unanswered_numbers ?? []).length">
            <p class="font-semibold text-amber-800">Unanswered questions</p>
            <p class="text-neutral-600" x-text="(review.unanswered_numbers ?? []).join(', ')"></p>
        </div>

        <div class="mt-3 space-y-3 text-sm" x-show="(review.flagged_numbers ?? []).length">
            <p class="font-semibold text-yellow-800">Flagged questions</p>
            <p class="text-neutral-600" x-text="(review.flagged_numbers ?? []).join(', ')"></p>
        </div>

        <div class="mt-6 flex flex-wrap justify-end gap-2">
            <button type="button" class="rounded-lg border px-4 py-2 text-sm font-semibold" @click="closeSubmitModal()">Cancel</button>
            <button type="button" class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-900" @click="reviewUnanswered()" x-show="(review.unanswered_numbers ?? []).length">Review unanswered</button>
            <button type="button" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-60" @click="submitAnyway()" :disabled="submitting" x-text="submitting ? 'Submitting…' : ((review.unanswered_numbers ?? []).length ? 'Submit anyway' : 'Submit test')"></button>
        </div>
    </div>
</div>
