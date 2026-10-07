<script setup>
// 010 US19 — distinct new drip subscribers across every drip course, by
// Taipei calendar day. Shared by the dashboard and the leads page (D37).
const props = defineProps({
  stats: {
    type: Object,
    default: () => ({ today: 0, last_7_days: 0, last_30_days: 0 }),
  },
})

const cards = [
  { key: 'today', label: '本日新增' },
  { key: 'last_7_days', label: '近 7 日新增' },
  { key: 'last_30_days', label: '近 30 日新增' },
]
</script>

<template>
  <div>
    <h2 class="text-sm font-medium text-gray-700">
      新訂閱者
      <span class="text-xs font-normal text-gray-500">（所有連鎖 Email 課程合計，不重複人數）</span>
    </h2>
    <div class="mt-2 grid grid-cols-3 gap-3 sm:gap-5">
      <div
        v-for="card in cards"
        :key="card.key"
        class="bg-white overflow-hidden shadow rounded-lg p-3 sm:p-5"
      >
        <dl>
          <dt class="text-xs sm:text-sm font-medium text-gray-500 truncate">{{ card.label }}</dt>
          <dd class="mt-1 text-xl sm:text-2xl font-semibold text-gray-900">{{ props.stats[card.key] ?? 0 }}</dd>
        </dl>
      </div>
    </div>
  </div>
</template>
