import Alpine from 'alpinejs';
import scannerList, { swipeRow } from './scanner-list';

window.Alpine = Alpine;

// Escáner reutilizable
Alpine.data('scannerList', scannerList);
Alpine.data('swipeRow', swipeRow);

Alpine.data('themeSwitcher', () => ({
    dark: false,

    init() {
        const savedTheme = localStorage.getItem('color-theme');

        this.dark = savedTheme
            ? savedTheme === 'dark'
            : window.matchMedia('(prefers-color-scheme: dark)').matches;

        this.applyTheme();
    },

    toggle() {
        this.dark = !this.dark;
        this.applyTheme();
    },

    applyTheme() {
        document.documentElement.classList.toggle('dark', this.dark);

        localStorage.setItem(
            'color-theme',
            this.dark ? 'dark' : 'light'
        );
    }
}));
Alpine.data('autoDismissAlert', (delay = 3000) => ({
    show: true,
    init() {
        setTimeout(() => {
            this.show = false;
        }, delay);
    }
}));
Alpine.start();
