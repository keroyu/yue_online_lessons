import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// CSRF: no X-CSRF-TOKEN default header. The meta tag is rendered once per full
// page load and Inertia never refreshes it, so after a logout (or anything that
// regenerates the token) a frozen header made every axios POST 419 — and Laravel
// checks X-CSRF-TOKEN before X-XSRF-TOKEN, so the stale value always won. Axios
// already sends X-XSRF-TOKEN from the XSRF-TOKEN cookie, which Laravel rewrites
// on every response, so it stays current on its own.
