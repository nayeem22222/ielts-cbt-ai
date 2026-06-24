<div x-show="ticketModalOpen" x-cloak class="reading-test-modal">
    <div class="reading-test-modal-backdrop" @click="closeTicketModal()"></div>
    <div class="reading-test-modal-card max-w-lg">
        <h3 class="text-lg font-bold text-neutral-900">Report Question <span x-text="ticketQuestionNumber"></span></h3>
        <p class="mt-1 text-sm text-neutral-600">Tell us what seems wrong. Our team will review your ticket.</p>

        <div class="mt-4 space-y-4">
            <div>
                <label class="text-sm font-semibold text-neutral-700">Issue Type</label>
                <select x-model="ticketIssueType" class="reading-test-input mt-1 w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm">
                    <template x-for="type in ticketIssueTypes" :key="type.value">
                        <option :value="type.value" x-text="type.label"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="text-sm font-semibold text-neutral-700">Message</label>
                <textarea
                    rows="4"
                    x-model="ticketMessage"
                    class="reading-test-input mt-1 w-full rounded-lg border border-neutral-300 px-3 py-2 text-sm"
                    placeholder="Describe the issue..."
                ></textarea>
            </div>
        </div>

        <p x-show="ticketSuccess" class="mt-3 text-sm font-semibold text-emerald-700">Ticket submitted. Thank you!</p>

        <div class="mt-5 flex justify-end gap-2">
            <button type="button" class="reading-test-toolbar-btn" @click="closeTicketModal()">Cancel</button>
            <button type="button" class="reading-test-submit-btn" @click="submitTicket()" :disabled="ticketSubmitting || !ticketMessage.trim()">
                <span x-text="ticketSubmitting ? 'Submitting...' : 'Submit Ticket'"></span>
            </button>
        </div>
    </div>
</div>
