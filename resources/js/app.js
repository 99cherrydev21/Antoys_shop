
import Alpine from 'alpinejs';

window.Alpine = Alpine;

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

Alpine.start();
