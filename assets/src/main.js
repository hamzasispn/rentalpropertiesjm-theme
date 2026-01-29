import './main.scss'
import Alpine from 'alpinejs'
import axios from 'axios'

// ✅ Swiper core ONLY
import Swiper from 'swiper'
import 'swiper/css'
import 'swiper/css/grid'
import 'swiper/css/thumbs'

import './property-filter-manager.js'

// ✅ Globals
window.Swiper = Swiper
window.axios = axios

window.wpData = {
    ajaxUrl: window.propertyTheme?.ajax_url || '',
    nonce: window.propertyTheme?.nonce || '',
    homeUrl: window.propertyTheme?.home_url || '',
    restUrl: window.propertyTheme?.rest_url || '',
    mapboxKey: window.propertyTheme?.mapbox_key || '',
}

// Axios
axios.defaults.headers.common['X-WP-Nonce'] = wpData.nonce
axios.defaults.withCredentials = true

// ✅ Alpine LAST
window.Alpine = Alpine
Alpine.start()
