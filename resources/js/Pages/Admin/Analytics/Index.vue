<script setup>
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import TrafficTab from '@/Components/Admin/Analytics/TrafficTab.vue'
import ShortLinkTab from '@/Components/Admin/Analytics/ShortLinkTab.vue'

defineOptions({ layout: AdminLayout })

/**
 * Shell for the marketing screens. Short links used to be their own sidebar
 * entry, but they are a marketing instrument measured by clicks — the same
 * question the funnel answers — so they live here as a tab.
 *
 * Active tab lives in the URL (比照 011 US8) so the page is shareable and the
 * server only assembles that tab's data; switching costs a request rather than
 * loading both reports on every visit.
 */
const props = defineProps({
  tab: { type: String, default: 'traffic' },

  // Traffic tab
  funnel: { type: Array, default: () => [] },
  channels: { type: Array, default: () => [] },
  cta: { type: Array, default: () => [] },
  range: { type: String, default: '30' },
  channel: { type: String, default: null },

  // Short link tab
  links: { type: Array, default: () => [] },
  baseUrl: { type: String, default: '' },
})

const tabs = [
  { value: 'traffic', label: '流量與轉換' },
  { value: 'short-links', label: '短網址' },
]

const switchTab = (value) => {
  if (value === props.tab) return
  router.get('/admin/analytics', { tab: value }, { preserveScroll: true })
}
</script>

<template>
  <div class="p-4 sm:p-6">
    <h1 class="text-xl font-bold text-gray-900">行銷分析</h1>

    <div class="mt-4 mb-6 border-b border-gray-200">
      <nav class="flex gap-6">
        <button
          v-for="t in tabs"
          :key="t.value"
          type="button"
          class="pb-3 text-sm font-medium border-b-2 -mb-px transition-colors cursor-pointer"
          :class="tab === t.value
            ? 'border-brand-teal text-brand-teal'
            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
          @click="switchTab(t.value)"
        >
          {{ t.label }}
        </button>
      </nav>
    </div>

    <TrafficTab
      v-if="tab === 'traffic'"
      :funnel="funnel"
      :channels="channels"
      :cta="cta"
      :range="range"
      :channel="channel"
    />
    <ShortLinkTab v-else :links="links" :base-url="baseUrl" />
  </div>
</template>
