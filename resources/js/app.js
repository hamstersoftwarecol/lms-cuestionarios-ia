import Alpine from 'alpinejs';
import { applyTheme, themeToggle } from './theme';
import { ttsPlayer } from './tts';
import { quizRunner } from './quiz-runner';

window.Alpine = Alpine;

Alpine.data('themeToggle', themeToggle);
Alpine.data('ttsPlayer', ttsPlayer);
Alpine.data('quizRunner', quizRunner);

// Copiar al portapapeles: <button x-data @click="$copy('texto')">
Alpine.magic('copy', () => async (text) => {
    try {
        await navigator.clipboard.writeText(text);
        window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Copiado al portapapeles', type: 'success' } }));
    } catch {
        window.prompt('Copia el texto:', text);
    }
});

Alpine.start();

// Si el usuario usa el tema del sistema, sigue los cambios del sistema operativo.
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    if ((document.documentElement.dataset.theme || 'system') === 'system') {
        applyTheme('system');
    }
});

// Carga diferida: DataTables (con exportación) y Chart.js solo en las páginas que los usan.
if (document.querySelector('[data-datatable]')) {
    import('./datatables').then(({ initDataTables }) => initDataTables());
}

if (document.querySelector('[data-chart]')) {
    import('./charts').then(({ initCharts }) => initCharts());
}
