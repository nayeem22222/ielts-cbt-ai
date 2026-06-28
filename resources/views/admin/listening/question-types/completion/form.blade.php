<div class="md:col-span-2 space-y-3">
    @include('admin.listening.question-types.completion.template-editor')
    <label class="block">
        <span class="mb-2 block text-sm font-medium text-neutral-800 dark:text-neutral-200">Word Limit</span>
        <input type="number" min="1" max="10" class="w-full rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-sm dark:border-neutral-800 dark:bg-neutral-900" x-model.number="settings.word_limit">
    </label>
    <input type="hidden" name="settings" :value="JSON.stringify(settings)">
</div>
