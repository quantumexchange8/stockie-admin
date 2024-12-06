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
            .mount(el);
    },
    progress: {
        color: "#4B5563",
    },
});
