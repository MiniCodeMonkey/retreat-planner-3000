import axios from 'axios';
import Alpine from 'alpinejs'
import maplibregl from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Make maplibregl available globally first
window.maplibregl = maplibregl;

// Then initialize Alpine
window.Alpine = Alpine;

// Start Alpine after maplibregl is available globally
Alpine.start();
