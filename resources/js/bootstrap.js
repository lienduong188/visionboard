import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Ensure cookies (session, XSRF-TOKEN) are always sent with requests
window.axios.defaults.withCredentials = true;
window.axios.defaults.withXsrfToken = true;

// Use CSRF token from meta tag as fallback when XSRF-TOKEN cookie is unavailable
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
if (csrfToken) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
}

// Ensure CSRF token is always sent via both header and request body
// Some hosting providers (e.g., Sakura) may strip custom headers via mod_security
axios.interceptors.request.use((config) => {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    if (token) {
        // Set header (may be stripped by server)
        if (!config.headers['X-CSRF-TOKEN']) {
            config.headers['X-CSRF-TOKEN'] = token;
        }
        // Also send _token in request body as fallback (checked first by Laravel)
        const method = (config.method || 'get').toLowerCase();
        if (method !== 'get' && method !== 'head') {
            if (config.data instanceof FormData) {
                if (!config.data.has('_token')) {
                    config.data.append('_token', token);
                }
            } else if (typeof config.data === 'object' && config.data !== null) {
                if (!config.data._token) {
                    config.data = { ...config.data, _token: token };
                }
            } else if (!config.data) {
                config.data = { _token: token };
            }
        }
    }
    return config;
});
