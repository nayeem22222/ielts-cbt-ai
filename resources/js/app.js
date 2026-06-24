import './bootstrap';
import { adminLayoutState } from './admin-layout';
import { readingPlayer } from './reading-player';
import { readingTestRenderer } from './reading-test-renderer';
import { readingTestResultReview } from './reading-test-result-review';

window.adminLayoutState = adminLayoutState;
window.readingPlayer = readingPlayer;
window.readingTestRenderer = readingTestRenderer;
window.readingTestResultReview = readingTestResultReview;

window.aaToast = (message = 'Saved successfully') => {
    window.dispatchEvent(new CustomEvent('aa-toast', { detail: { message } }));
};
