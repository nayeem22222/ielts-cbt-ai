<div x-show="reviewOpen" x-cloak class="reading-test-review-shell fixed inset-0 z-40" role="dialog" aria-modal="true" aria-labelledby="review-panel-title">
    <div class="reading-test-review-backdrop" @click="closeReview()" aria-hidden="true"></div>

    <aside class="reading-test-review-panel pointer-events-auto absolute inset-y-0 right-0 w-full max-w-md bg-white">
        <div class="flex h-full flex-col">
            <div class="flex items-center justify-between border-b border-neutral-200 px-5 py-4">
                <h2 id="review-panel-title" class="text-lg font-bold text-neutral-900">Review Answers</h2>
                <button type="button" class="reading-test-toolbar-btn" @click="closeReview()" aria-label="Close review panel">Close</button>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto p-5">
                <div class="mb-6 grid grid-cols-2 gap-3 text-sm">
                    <div class="reading-test-review-summary-card">
                        <span class="text-xs font-semibold uppercase tracking-wide text-neutral-500">Total</span>
                        <p class="mt-1 text-xl font-bold" x-text="review.summary?.total ?? 0"></p>
                    </div>
                    <div class="reading-test-review-summary-card is-answered">
                        <span class="text-xs font-semibold uppercase tracking-wide text-emerald-800">Answered</span>
                        <p class="mt-1 text-xl font-bold text-emerald-900" x-text="review.summary?.answered ?? 0"></p>
                    </div>
                    <div class="reading-test-review-summary-card is-unanswered">
                        <span class="text-xs font-semibold uppercase tracking-wide text-amber-800">Unanswered</span>
                        <p class="mt-1 text-xl font-bold text-amber-900" x-text="review.summary?.unanswered ?? 0"></p>
                    </div>
                    <div class="reading-test-review-summary-card is-flagged">
                        <span class="text-xs font-semibold uppercase tracking-wide text-yellow-800">Flagged</span>
                        <p class="mt-1 text-xl font-bold text-yellow-900" x-text="review.summary?.flagged ?? 0"></p>
                    </div>
                    <div class="reading-test-review-summary-card col-span-2">
                        <span class="text-xs font-semibold uppercase tracking-wide text-neutral-500">Not visited</span>
                        <p class="mt-1 text-xl font-bold" x-text="review.summary?.not_visited ?? 0"></p>
                    </div>
                </div>

                <template x-for="part in review.parts ?? []" :key="'review-part-'+part.passage_id">
                    <div class="mb-6">
                        <h3 class="mb-2 text-sm font-bold text-brand-700" x-text="part.part_label + ': ' + (part.title || '')"></h3>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="question in part.questions ?? []" :key="'review-q-'+question.question_id">
                                <button
                                    type="button"
                                    class="reading-test-review-q"
                                    :class="'is-' + question.status + (question.current ? ' is-current' : '')"
                                    @click="selectFromReview(question.question_number)"
                                    :aria-label="'Go to question ' + question.question_number"
                                    x-text="question.question_number"
                                ></button>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            <div class="reading-test-review-actions">
                <button type="button" class="reading-test-review-action-btn" @click="reviewUnanswered()">Review unanswered</button>
                <button type="button" class="reading-test-review-action-btn" @click="reviewFlagged()">Review flagged</button>
                <button type="button" class="reading-test-review-action-btn reading-test-review-action-btn--primary" @click="continueTest()">Continue test</button>
            </div>
        </div>
    </aside>
</div>
