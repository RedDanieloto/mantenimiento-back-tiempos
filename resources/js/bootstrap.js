import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Realtime: Laravel Echo + Pusher (optional; safe to load if env vars exist)
// Install deps: npm i laravel-echo pusher-js
// Realtime deshabilitado
