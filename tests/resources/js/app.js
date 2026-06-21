import './bootstrap';

window.aaToast = (message = 'Saved successfully') => {
    window.dispatchEvent(new CustomEvent('aa-toast', { detail: { message } }));
};
