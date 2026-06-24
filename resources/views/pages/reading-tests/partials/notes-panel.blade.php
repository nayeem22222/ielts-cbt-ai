<div
    x-show="notesPanelOpen"
    x-cloak
    class="reading-test-notes-panel"
    @keydown.escape.window="closeNotesPanel()"
>
    <div class="reading-test-notes-backdrop" @click="closeNotesPanel()"></div>
    <aside class="reading-test-notes-drawer">
        <div class="flex items-center justify-between border-b border-neutral-200 px-5 py-4">
            <h2 class="text-lg font-bold text-neutral-900">Notes</h2>
            <button type="button" class="reading-test-icon-btn" @click="closeNotesPanel()">✕</button>
        </div>

        <div class="flex gap-2 border-b border-neutral-200 px-4 py-3">
            <button type="button" class="reading-test-notes-tab" :class="notesTab === 'all' ? 'is-active' : ''" @click="notesTab = 'all'">My Notes</button>
            <button type="button" class="reading-test-notes-tab" :class="notesTab === 'passage' ? 'is-active' : ''" @click="notesTab = 'passage'">Passage Notes</button>
            <button type="button" class="reading-test-notes-tab" :class="notesTab === 'question' ? 'is-active' : ''" @click="notesTab = 'question'">Question Notes</button>
        </div>

        <div class="space-y-3 border-b border-neutral-200 p-4">
            <input
                type="text"
                class="reading-test-notes-input w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm"
                placeholder="Title (optional)"
                x-model="noteDraft.title"
                @input="saveNoteDraft()"
            />
            <textarea
                rows="5"
                class="reading-test-notes-input w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm"
                placeholder="Write your note..."
                x-model="noteDraft.content"
                @input="saveNoteDraft()"
            ></textarea>
            <p class="text-xs text-neutral-500">Notes save automatically.</p>
        </div>

        <div class="max-h-[40vh] overflow-y-auto p-4">
            <template x-for="note in filteredNotes()" :key="'note-'+note.id">
                <div class="mb-3 rounded-xl border border-neutral-200 bg-neutral-50 p-3">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0 flex-1 cursor-pointer" @click="editNote(note)">
                            <p class="text-sm font-semibold text-neutral-900" x-text="note.title || 'Untitled note'"></p>
                            <p class="mt-1 text-xs text-neutral-500" x-text="note.question_number ? 'Question ' + note.question_number : (note.passage_label || 'General')"></p>
                        </div>
                        <div class="flex gap-1">
                            <button type="button" class="text-xs font-semibold text-brand-700" @click="editNote(note)">Edit</button>
                            <button type="button" class="text-xs font-semibold text-red-600" @click="deleteNote(note.id)">Delete</button>
                        </div>
                    </div>
                    <p class="mt-2 text-sm text-neutral-700" x-text="note.content"></p>
                </div>
            </template>
            <p x-show="filteredNotes().length === 0" class="text-sm text-neutral-500">No notes in this tab yet.</p>
        </div>
    </aside>
</div>
