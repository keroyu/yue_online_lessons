<script setup>
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import BookingListTab from '@/Components/Admin/Leads/BookingListTab.vue'
import SubscriberListTab from '@/Components/Admin/Leads/SubscriberListTab.vue'

defineOptions({ layout: AdminLayout })

// Shell for both lists (US8). The active tab lives in the URL so the page is
// shareable and reloadable, and the server only assembles that tab's data —
// which is why switching costs a request instead of a v-show toggle (D24).
const props = defineProps({
  tab: { type: String, default: 'booking' },
  filters: { type: Object, required: true },

  // Booking tab
  leads: { type: Object, default: null },
  highTicketCourses: { type: Array, default: () => [] },
  consultantOptions: { type: Array, default: () => [] },
  dripCourses: { type: Array, default: () => [] },
  notifyTemplate: { type: Object, default: null },
  dripByEmail: { type: Object, default: () => ({}) },
  purchasesByEmail: { type: Object, default: () => ({}) },
  suppressionsByEmail: { type: Object, default: () => ({}) },
  grantableCourses: { type: Array, default: () => [] },
  statusCounts: { type: Object, default: () => ({}) },

  // Subscriber tab
  dripCourseOptions: { type: Array, default: () => [] },
  subscriberData: { type: Object, default: null },
})

const tabs = [
  { value: 'booking', label: '預約名單' },
  { value: 'subscribers', label: '訂閱者名單' },
]

const switchTab = (value) => {
  if (value === props.tab) return
  router.get('/admin/high-ticket-leads', { tab: value }, { preserveScroll: true })
}
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-semibold text-gray-900">Leads 名單</h1>
      <p class="mt-1 text-sm text-gray-600">客製服務預約訪客與連鎖 Email 訂閱者管理</p>
    </div>

    <!-- Tab nav -->
    <div class="mb-6 border-b border-gray-200">
      <nav class="flex gap-6">
        <button
          v-for="t in tabs"
          :key="t.value"
          type="button"
          class="pb-3 text-sm font-medium border-b-2 -mb-px transition-colors cursor-pointer"
          :class="tab === t.value
            ? 'border-brand-teal text-brand-teal'
            : 'border-transparent text-gray-500 hover:text-gray-700'"
          @click="switchTab(t.value)"
        >{{ t.label }}</button>
      </nav>
    </div>

    <BookingListTab
      v-if="tab === 'booking'"
      :leads="leads"
      :filters="filters"
      :high-ticket-courses="highTicketCourses"
      :consultant-options="consultantOptions"
      :drip-courses="dripCourses"
      :notify-template="notifyTemplate"
      :drip-by-email="dripByEmail"
      :purchases-by-email="purchasesByEmail"
      :suppressions-by-email="suppressionsByEmail"
      :grantable-courses="grantableCourses"
      :status-counts="statusCounts"
    />

    <SubscriberListTab
      v-else
      :data="subscriberData"
      :course-options="dripCourseOptions"
      :filters="filters"
    />
  </div>
</template>
