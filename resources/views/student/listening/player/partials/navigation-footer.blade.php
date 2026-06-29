<footer class="sticky bottom-0 z-30 border-t border-neutral-200 bg-white/95 backdrop-blur">
    <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-3">
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" id="listening-prev" class="rounded-xl border px-3 py-2 text-sm font-medium">Previous</button>
            <button type="button" id="listening-next" class="rounded-xl border px-3 py-2 text-sm font-medium">Next</button>
            <button type="button" id="listening-flag-current" class="rounded-xl border px-3 py-2 text-sm font-medium">Flag for review</button>
            <button type="button" id="listening-clear-current" class="rounded-xl border px-3 py-2 text-sm font-medium">Clear answer</button>
        </div>
        @include('student.listening.player.partials.autosave-status')
    </div>
</footer>
