const STORAGE_KEY = 'lms-theme';

export function resolveDark(theme) {
    return theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
}

export function applyTheme(theme) {
    document.documentElement.dataset.theme = theme;
    document.documentElement.classList.toggle('dark', resolveDark(theme));
    window.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme } }));
}

/**
 * Botón de la barra superior: alterna claro → oscuro → sistema y lo guarda en el perfil.
 */
export function themeToggle(saveUrl = null) {
    return {
        theme: document.documentElement.dataset.theme || 'system',

        get label() {
            return { light: 'Tema claro', dark: 'Tema oscuro', system: 'Tema del sistema' }[this.theme];
        },

        cycle() {
            const order = ['light', 'dark', 'system'];
            this.set(order[(order.indexOf(this.theme) + 1) % order.length]);
        },

        set(theme) {
            this.theme = theme;
            applyTheme(theme);

            try {
                localStorage.setItem(STORAGE_KEY, theme);
            } catch {
                // Almacenamiento no disponible (modo privado): el tema se aplica igualmente.
            }

            if (saveUrl) {
                fetch(saveUrl, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ theme }),
                }).catch(() => {});
            }
        },
    };
}
