const STORAGE_KEYS = {
    dark: 'aa-admin-dark',
    collapsed: 'aa-admin-sidebar-collapsed',
    menuOpen: 'aa-admin-menu-open',
};

export function adminLayoutState() {
    const readMenuOpen = () => {
        try {
            return JSON.parse(localStorage.getItem(STORAGE_KEYS.menuOpen) ?? '{}');
        } catch {
            return {};
        }
    };

    return {
        dark: localStorage.getItem(STORAGE_KEYS.dark) === 'true',
        collapsed: localStorage.getItem(STORAGE_KEYS.collapsed) === 'true',
        mobileOpen: false,
        menuOpen: readMenuOpen(),

        init() {
            this.applyDark(this.dark);
        },

        applyDark(value) {
            document.documentElement.classList.toggle('dark', value);
        },

        toggleDark() {
            this.dark = !this.dark;
            localStorage.setItem(STORAGE_KEYS.dark, String(this.dark));
            this.applyDark(this.dark);
        },

        toggleCollapsed() {
            this.collapsed = !this.collapsed;
            localStorage.setItem(STORAGE_KEYS.collapsed, String(this.collapsed));
        },

        toggleMobile() {
            this.mobileOpen = !this.mobileOpen;
        },

        closeMobile() {
            this.mobileOpen = false;
        },

        isMenuOpen(key) {
            return this.menuOpen[key] !== false;
        },

        toggleMenu(key) {
            this.menuOpen[key] = !this.isMenuOpen(key);
            localStorage.setItem(STORAGE_KEYS.menuOpen, JSON.stringify(this.menuOpen));
        },
    };
}

window.adminLayoutState = adminLayoutState;
