<div>
    <x-ui.textarea
        name="content"
        label="Template Content"
        rows="6"
        class="font-mono text-sm"
        x-model="content"
        placeholder="Name: [blank:1]&#10;Date: [blank:2]"
    ></x-ui.textarea>

    <div class="mt-2 flex flex-wrap items-center gap-3">
        <button
            type="button"
            class="rounded-xl border border-neutral-200 bg-white px-3 py-1.5 text-xs font-medium text-neutral-800 hover:bg-neutral-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-200 dark:hover:bg-neutral-800"
            @click="generateBlanksForRange()"
        >
            Generate blanks for Q<span x-text="start"></span>–Q<span x-text="end"></span>
        </button>
        <p class="text-xs aa-muted">Use <code>[blank:N]</code> where N is the official question number.</p>
    </div>

    <p
        x-show="completionTypes.includes(type) && blankCount() === 0"
        x-cloak
        class="mt-2 text-xs text-amber-700 dark:text-amber-400"
    >
        No blanks found. Add <code>[blank:N]</code> placeholders or generate them for the selected range.
    </p>

    <p
        x-show="completionTypes.includes(type) && blankCount() > 0 && ! blanksMatchRange()"
        x-cloak
        class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-300"
    >
        Template has <span x-text="blankCount()"></span> unique blank(s) but this group covers
        <span x-text="expectedQuestions()"></span> question(s) (Q<span x-text="start"></span>–Q<span x-text="end"></span>).
        Blank numbers should match that range exactly.
    </p>
</div>
