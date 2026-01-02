import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { createPinia } from 'pinia';

// Create Pinia store
const pinia = createPinia();

createInertiaApp({
    title: (title) => `${title} - ThaiVote`,
    resolve: (name) => {
        const pages = import.meta.glob('./pages/**/*.vue', { eager: true });
        return pages[`./pages/${name}.vue`];
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });

        app.use(plugin);
        app.use(pinia);

        // Global properties
        app.config.globalProperties.$formatNumber = (num) =>
            new Intl.NumberFormat('th-TH').format(num);

        app.config.globalProperties.$formatPercent = (num) =>
            new Intl.NumberFormat('th-TH', {
                style: 'percent',
                minimumFractionDigits: 2,
            }).format(num / 100);

        app.mount(el);
    },
    progress: {
        color: '#FF6B35',
        showSpinner: true,
    },
});
