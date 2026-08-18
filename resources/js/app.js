import '../css/app.css';
import './bootstrap';

// 🚀 Silence Mapbox telemetry network requests to prevent net::ERR_BLOCKED_BY_CLIENT console noise in Brave/Adblockers
if (typeof window !== 'undefined') {
    const originalFetch = window.fetch;
    window.fetch = function (resource, init) {
        const url = typeof resource === 'string' ? resource : (resource && resource.url) ? resource.url : '';
        if (url && url.includes('events.mapbox.com')) {
            return Promise.resolve(new Response(JSON.stringify({ status: 'ok' }), {
                status: 200,
                headers: { 'Content-Type': 'application/json' },
            }));
        }
        return originalFetch.apply(this, arguments);
    };

    const originalXHR = window.XMLHttpRequest.prototype.open;
    window.XMLHttpRequest.prototype.open = function (method, url) {
        if (typeof url === 'string' && url.includes('events.mapbox.com')) {
            this.__isMapboxTelemetry = true;
        }
        return originalXHR.apply(this, arguments);
    };

    const originalXHRSend = window.XMLHttpRequest.prototype.send;
    window.XMLHttpRequest.prototype.send = function (body) {
        if (this.__isMapboxTelemetry) {
            Object.defineProperty(this, 'readyState', { value: 4, writable: false });
            Object.defineProperty(this, 'status', { value: 200, writable: false });
            Object.defineProperty(this, 'responseText', { value: '{}', writable: false });
            if (typeof this.onreadystatechange === 'function') {
                this.onreadystatechange();
            }
            if (typeof this.onload === 'function') {
                this.onload();
            }
            return;
        }
        return originalXHRSend.apply(this, arguments);
    };
}

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';

// 🚀 PWA SERVICE WORKER REGISTRATION (Strictly Restricted to Landlord Owner Portal)
import { registerSW } from 'virtual:pwa-register';

if (typeof window !== 'undefined' && 'serviceWorker' in navigator) {
    if (window.location.pathname.startsWith('/owner')) {
        registerSW({ immediate: true });
    }
}
// ---------------------------------

createInertiaApp({
    resolve: name => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        return pages[`./Pages/${name}.vue`];
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
});