import './bootstrap';
import Alpine from 'alpinejs';

import { applyTheme, initThemeControls } from './core/theme';
import { initSidebar } from './core/sidebar';
import { initDropdowns } from './core/dropdowns';
import { initDataTablesAndForms } from './core/datatables';

window.Alpine = Alpine;
Alpine.start();

applyTheme();

document.addEventListener('DOMContentLoaded', () => {
    initThemeControls();
    initSidebar();
    initDropdowns();
    initDataTablesAndForms();

    setTimeout(() => {
        document.getElementById('flash-success')?.remove();
        document.getElementById('flash-error')?.remove();
    }, 4000);
});
