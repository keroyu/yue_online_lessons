<script setup>
// 011 US39 — new bookings (confirmed, distinct email) beside new drip
// subscribers (distinct people), by Taipei calendar day. Each module counts
// its own; this component only puts them side by side (D155).
const props = defineProps({
  bookings: { type: Object, default: () => ({ today: 0, last_7_days: 0, last_30_days: 0 }) },
  subscribers: { type: Object, default: () => ({ today: 0, last_7_days: 0, last_30_days: 0 }) },
})

const columns = [
  { key: 'today', label: '本日' },
  { key: 'last_7_days', label: '近 7 日' },
  { key: 'last_30_days', label: '近 30 日' },
]

const rows = [
  { source: 'bookings', label: '預約' },
  { source: 'subscribers', label: '訂閱' },
]

const valueOf = (source, key) => props[source]?.[key] ?? 0
// Zero is the signal worth noticing: an entry point that brought nobody in.
const colorOf = (value) => (value > 0 ? 'text-green-600' : 'text-red-600')
</script>

<template>
  <div>
    <h2 class="text-sm font-medium text-gray-700">新預約者／訂閱者</h2>
    <div class="mt-2 grid grid-cols-3 gap-3 sm:gap-5">
      <div
        v-for="col in columns"
        :key="col.key"
        class="bg-white overflow-hidden shadow rounded-lg p-3 sm:p-5"
      >
        <p class="text-xs sm:text-sm font-medium text-gray-500 truncate">{{ col.label }}</p>
        <dl class="mt-2 grid grid-cols-2 gap-2">
          <div v-for="row in rows" :key="row.source">
            <dt class="text-xs text-gray-500">{{ row.label }}</dt>
            <dd
              class="text-xl sm:text-2xl font-semibold"
              :class="colorOf(valueOf(row.source, col.key))"
            >{{ valueOf(row.source, col.key) }}</dd>
          </div>
        </dl>
      </div>
    </div>
  </div>
</template>
