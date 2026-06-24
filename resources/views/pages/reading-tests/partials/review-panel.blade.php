<div x-show="reviewOpen" x-cloak class="reading-test-review-panel pointer-events-auto fixed inset-y-0 right-0 z-40 w-full max-w-md border-l border-neutral-200 bg-white shadow-2xl">
    <div class="flex h-full flex-col">
        <div class="flex items-center justify-between border-b border-neutral-200 px-5 py-4">
            <h2 class="text-lg font-bold text-neutral-900">Review Answers</h2>
            <button type="button" class="rounded-lg border px-3 py-1 text-sm" @click="closeReview()">Close</button>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto p-5">
            <div class="mb-6 grid grid-cols-2 gap-3 text-sm">
                <div class="rounded-lg bg-neutral-50 p-3"><span class="font-semibold">Total</span><p x-text="review.summary?.total ?? 0"></p></div>
                <div class="rounded-lg bg-emerald-50 p-3"><span class="font-semibold text-emerald-800">Answered</span><p x-text="review.summary?.answered ?? 0"></p></div>
                <div class="rounded-lg bg-amber-50 p-3"><span class="font-semibold text-amber-800">Unanswered</span><p x-text="review.summary?.unanswered ?? 0"></p></div>
                <div class="rounded-lg bg-yellow-50 p-3"><span class="font-semibold text-yellow-800">Flagged</span><p x-text="review.summary?.flagged ?? 0"></p></div>
                <div class="col-span-2 rounded-lg bg-neutral-50 p-3"><span class="font-semibold">Not visited</span><p x-text="review.summary?.not_visited ?? 0"></p></div>
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
                                x-text="question.question_number"
                            ></button>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
