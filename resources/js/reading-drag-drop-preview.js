import '../css/reading-cbt-ui.css';
import { createReadingDragDrop } from './reading-drag-drop';

document.addEventListener('DOMContentLoaded', () => {
    const previewRoot = document.querySelector('[data-dnd-preview-root]');
    if (!previewRoot) {
        return;
    }

    const dragDrop = createReadingDragDrop({
        isLocked: false,
        autosave: { bindInputs: () => {} },
    });

    dragDrop.init(previewRoot);
});
