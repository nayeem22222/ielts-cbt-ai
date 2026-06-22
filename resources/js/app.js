import './bootstrap';
import { adminLayoutState } from './admin-layout';
import { readingPlayer } from './reading-player';

window.adminLayoutState = adminLayoutState;
window.readingPlayer = readingPlayer;

window.aaToast = (message = 'Saved successfully') => {
    window.dispatchEvent(new CustomEvent('aa-toast', { detail: { message } }));
};
