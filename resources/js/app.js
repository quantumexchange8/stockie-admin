import "./bootstrap";
import "../css/app.css";
import axios from 'axios';

import { createApp, h } from "vue";
import { createInertiaApp } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { ZiggyVue } from "../../vendor/tightenco/ziggy";
import PrimeVue from "primevue/config";
import preset from "../presets/primevue";
import ToastService from 'primevue/toastservice';
import Tooltip from 'primevue/tooltip';
import { vClickOutside } from './Composables/index.js';

// import { registerSW } from 'virtual:pwa-register';
// import PWAManager from '@/Components/PWAInstallManager.vue';

// Session Continuity Check
const checkSession = async () => {
    // Skip check for these paths to prevent loops
    const excludedPaths = ['/login', '/logout', '/auth/*'];
    const currentPath = window.location.pathname;
    console.log(currentPath);
    
    const isExcluded = excludedPaths.some(path => {
        if (path.endsWith('*')) {
            return currentPath.startsWith(path.slice(0, -1));
        }
        return currentPath === path;
    });

    if (isExcluded) return;
    
    try {
        const response = await axios.get('/api/auth/check', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            // Important for session cookies to be sent
            withCredentials: true 
        });

        const isAuthenticated = response.data;
    
        // Only redirect if we're actually on login page
        if (isAuthenticated && currentPath === '/login') {
            // Use Inertia.js redirect if available
            if (typeof window.Inertia !== 'undefined') {
                window.Inertia.visit('/dashboard');
            } else {
                window.location.href = '/dashboard';
            }
            return;
        }
        
        // Only redirect to login if not authenticated
        if (!isAuthenticated && currentPath !== '/login') {
            if (typeof window.Inertia !== 'undefined') {
                window.Inertia.visit('/login');
            } else {
                window.location.href = '/login';
            }
        }
    } catch (error) {
        console.error('Session check failed:', error);
        if (currentPath !== '/login') {
            if (typeof window.Inertia !== 'undefined') {
                window.Inertia.visit('/login');
            } else {
                window.location.href = '/login';
            }
        }
    }
};

// Run session check on initial load - but only after Inertia is ready
if (typeof window.Inertia !== 'undefined') {
    window.Inertia.on('navigate', () => {
        setTimeout(checkSession, 100); // Small delay after navigation
    });
} else {
    document.addEventListener('DOMContentLoaded', checkSession);
}

// For handling unauthorized requests when user session has been removed or ended
axios.interceptors.response.use(
    response => response,
    error => {
        const status = error.response?.status;
        
        if (status === 401) {
            // Unauthorized - redirect to login
            window.location.href = '/login?session_expired=1';
        } 
        else if (status === 419) {
            // CSRF token mismatch - refresh page
            window.location.reload();
        }
        else if (status >= 500) {
            // Server error - show notification
            const toast = useToast();
            toast.add({
                severity: 'error',
                summary: 'Server Error',
                detail: 'Please try again later',
                life: 5000
            });
        }
        
        return Promise.reject(error);
    }
);

// const updateSW = registerSW({
//     onNeedRefresh() {
//         // Show PrimeVue toast instead of default alert
//         const toast = useToast();
//         toast.add({
//             severity: 'info',
//             summary: 'Update Available',
//             detail: 'A new version is available. Click to reload.',
//             life: 10000,
//             onClick: () => {
//                     updateSW(true);
//                     toast.remove();
//                 }
//             });
//         },
//         onOfflineReady() {
//             console.log('App ready for offline use');
//             // Optional: Show offline ready notification
//             const toast = useToast();
//             toast.add({
//                 severity: 'success',
//                 summary: 'Offline Ready',
//                 detail: 'App is ready for offline use',
//                 life: 3000
//             });
//         },
//     });

const appName = import.meta.env.VITE_APP_NAME || "Stockie Admin";

createInertiaApp({
    // title: (title) => `${title} - Stockie Admin`,
    title: (title) => `${title} - ${appName}`,
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
            // .component('PWAManager', PWAManager)
            .mount(el);
    },
    progress: {
        color: "#4B5563",
    },
});
