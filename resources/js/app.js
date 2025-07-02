import "./bootstrap";
import "../css/app.css";

import { createApp, h } from "vue";
import { createInertiaApp } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { ZiggyVue } from "../../vendor/tightenco/ziggy";
import PrimeVue from "primevue/config";
import preset from "../presets/primevue";
import ToastService from 'primevue/toastservice';
import Tooltip from 'primevue/tooltip';
import { vClickOutside } from './Composables/index.js';

import { registerSW } from 'virtual:pwa-register';
import PWAManager from '@/Components/PWAInstallManager.vue';

const updateSW = registerSW({
    onNeedRefresh() {
        // Show PrimeVue toast instead of default alert
        const toast = useToast();
        toast.add({
            severity: 'info',
            summary: 'Update Available',
            detail: 'A new version is available. Click to reload.',
            life: 10000,
            onClick: () => {
                    updateSW(true);
                    toast.remove();
                }
            });
        },
        onOfflineReady() {
            console.log('App ready for offline use');
            // Optional: Show offline ready notification
            const toast = useToast();
            toast.add({
                severity: 'success',
                summary: 'Offline Ready',
                detail: 'App is ready for offline use',
                life: 3000
            });
        },
    });

const appName = import.meta.env.VITE_APP_NAME || "Laravel";

createInertiaApp({
    // title: (title) => `${title} - ${appName}`,
    title: (title) => `${title} - Stockie Admin`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob("./Pages/**/*.vue")
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(PrimeVue, {
                unstyled: true,
                pt: preset,
            })
            .use(ToastService)
            .directive('tooltip', Tooltip)
            .directive('click-outside', vClickOutside)
            .component('PWAManager', PWAManager)
            .mount(el);
    },
    progress: {
        color: "#4B5563",
    },
});
