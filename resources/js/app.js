import './bootstrap';
import { adminLayoutState } from './admin-layout';

window.adminLayoutState = adminLayoutState;

window.aaToast = (message = 'Saved successfully') => {
    window.dispatchEvent(new CustomEvent('aa-toast', { detail: { message } }));
};
