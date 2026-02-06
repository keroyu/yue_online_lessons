import './bootstrap'
import '../css/app.css'

import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import AppLayout from './Components/Layout/AppLayout.vue'

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
