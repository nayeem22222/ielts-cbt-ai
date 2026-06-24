<div x-show="pauseOpen" x-cloak class="pointer-events-auto fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="togglePause()">
    <div class="w-full max-w-md rounded-2xl bg-white p-8 text-center shadow-2xl">
        <h2 class="text-xl font-bold text-neutral-900">Break Reminder</h2>
        <p class="mt-3 text-sm text-neutral-600">
            This is a visual break only. The exam timer continues on the server and will not pause.
        </p>
        <p class="mt-4 text-2xl font-bold text-brand-700" x-text="timerLabel"></p>
        <button type="button" class="mt-6 w-full rounded-xl bg-brand-600 px-6 py-3 text-sm font-semibold text-white hover:bg-brand-700" @click="togglePause()">Resume</button>
    </div>
</div>
