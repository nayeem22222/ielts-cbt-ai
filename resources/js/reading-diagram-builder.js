window.readingDiagramBuilder = (config) => ({
    groupId: config.groupId,
    startQuestion: config.startQuestion,
    endQuestion: config.endQuestion,
    answerRule: config.answerRule,
    customAnswerRule: config.customAnswerRule ?? '',
    diagramImageUrl: config.diagramImageUrl,
    previewImageUrl: null,
    labels: config.labels ?? [],
    saveLabelsUrl: config.saveLabelsUrl,
    uploadUrl: config.uploadUrl,
    confirmRemove: config.confirmRemove ?? false,
    destroyQuestionBase: config.destroyQuestionBase,
    selectedIndex: null,
    draggingIndex: null,
    canvasRect: null,

    get displayImageUrl() {
        return this.diagramImageUrl || this.previewImageUrl;
    },

    get canPlaceLabels() {
        return Boolean(this.diagramImageUrl);
    },

    init() {
        this.refreshCanvasMetrics();
        window.addEventListener('mousemove', (event) => this.onDrag(event));
        window.addEventListener('mouseup', () => {
            this.draggingIndex = null;
        });
    },

    previewSelectedFile(event) {
        const file = event.target.files?.[0];

        if (this.previewImageUrl) {
            URL.revokeObjectURL(this.previewImageUrl);
            this.previewImageUrl = null;
        }

        if (!file) {
            return;
        }

        const allowed = ['image/jpeg', 'image/png', 'image/webp'];

        if (!allowed.includes(file.type) && !/\.(jpe?g|png|webp)$/i.test(file.name)) {
            return;
        }

        this.previewImageUrl = URL.createObjectURL(file);
        this.$nextTick(() => this.refreshCanvasMetrics());
    },

    refreshCanvasMetrics() {
        const canvas = document.querySelector('[data-diagram-canvas]');

        if (!canvas) {
            return;
        }

        this.canvasRect = canvas.getBoundingClientRect();
    },

    nextAvailableNumber() {
        const used = new Set(this.labels.map((label) => Number(label.question_number)));

        for (let number = this.startQuestion; number <= this.endQuestion; number += 1) {
            if (!used.has(number)) {
                return number;
            }
        }

        return this.endQuestion;
    },

    addLabelAtClick(event) {
        if (!this.canPlaceLabels || this.draggingIndex !== null) {
            return;
        }

        const canvas = event.currentTarget;
        const rect = canvas.getBoundingClientRect();
        const x = ((event.clientX - rect.left) / rect.width) * 100;
        const y = ((event.clientY - rect.top) / rect.height) * 100;

        this.labels.push({
            question_number: this.nextAvailableNumber(),
            x: Math.round(x * 100) / 100,
            y: Math.round(y * 100) / 100,
            label: '',
            question_id: null,
            correct_answer: '',
            alternative_answers: [],
            case_sensitive: false,
            explanation: '',
            difficulty: 'medium',
        });

        this.selectedIndex = this.labels.length - 1;
    },

    selectLabel(index) {
        this.selectedIndex = index;
    },

    startDrag(index, event) {
        if (!this.canPlaceLabels) {
            return;
        }

        this.draggingIndex = index;
        this.selectedIndex = index;
        this.refreshCanvasMetrics();
        this.onDrag(event);
    },

    onDrag(event) {
        if (this.draggingIndex === null) {
            return;
        }

        const canvas = document.querySelector('[data-diagram-canvas]');

        if (!canvas) {
            return;
        }

        const rect = canvas.getBoundingClientRect();
        const x = Math.min(100, Math.max(0, ((event.clientX - rect.left) / rect.width) * 100));
        const y = Math.min(100, Math.max(0, ((event.clientY - rect.top) / rect.height) * 100));

        this.labels[this.draggingIndex].x = Math.round(x * 100) / 100;
        this.labels[this.draggingIndex].y = Math.round(y * 100) / 100;
    },

    removeLabel(index) {
        this.labels.splice(index, 1);

        if (this.selectedIndex === index) {
            this.selectedIndex = null;
        }
    },
});
