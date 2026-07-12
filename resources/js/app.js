import './bootstrap'
import '../css/app.css'

import { createApp, h } from 'vue'
import { createInertiaApp, router } from '@inertiajs/vue3'
import AppLayout from './Components/Layout/AppLayout.vue'

// Skip the first navigate event: the initial full-page load already sends
// PageView from the blade-injected pixel snippet; only SPA visits need it here.
let initialPageLoad = true
router.on('navigate', () => {
  if (initialPageLoad) {
    initialPageLoad = false
    return
  }
  if (window.fbq) window.fbq('track', 'PageView')
})

createInertiaApp({
  title: (title) => title ? `${title} - Your Time Bank` : 'Your Time Bank',
  resolve: (name) => {
    const pages = import.meta.glob('./Pages/**/*.vue', { eager: true })
    const page = pages[`./Pages/${name}.vue`]
    // Only apply default layout if not explicitly set (including false)
    if (page.default.layout === undefined) {
      page.default.layout = AppLayout
    }
    return page
  },
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .mount(el)
  },
})
