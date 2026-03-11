<script setup>
import { ref, computed, watch } from 'vue'
import { Bar } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  LineElement,
  PointElement,
  Tooltip,
  Legend,
} from 'chart.js'

ChartJS.register(CategoryScale, LinearScale, BarElement, LineElement, PointElement, Tooltip, Legend)

const props = defineProps({
  chartData: { type: Object, required: true },   // { days, total_amount, total_count }
  chartFilters: { type: Object, required: true }, // { range, start, end }
})

const emit = defineEmits(['change-range', 'change-custom'])

// Local state for the selected range dropdown
const selectedRange = ref(props.chartFilters.range ?? '30d')
const customStart   = ref(props.chartFilters.range === 'custom' ? props.chartFilters.start : '')
const customEnd     = ref(props.chartFilters.range === 'custom' ? props.chartFilters.end   : '')

// Keep selectedRange in sync when parent reloads (partial reload)
watch(() => props.chartFilters.range, (v) => { selectedRange.value = v })

const onRangeChange = () => {
  if (selectedRange.value !== 'custom') {
    emit('change-range', selectedRange.value)
  }
}

const onCustomApply = () => {
  if (!customStart.value || !customEnd.value) return
  emit('change-custom', customStart.value, customEnd.value)
}

// Formatted summary stats
const formattedAmount = computed(() => {
  const n = props.chartData.total_amount ?? 0
  return '$' + n.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 })
})

// Chart.js dataset
const chartDataset = computed(() => ({
  labels: props.chartData.days.map(d => d.date),
  datasets: [
    {
      type: 'bar',
      label: '當日銷售額',
      data: props.chartData.days.map(d => d.amount),
      backgroundColor: '#2dd4bf',
      yAxisID: 'yAmount',
      order: 2,
    },
    {
      type: 'line',
      label: '當日銷售量',
      data: props.chartData.days.map(d => d.count),
      borderColor: '#93c5fd',
      backgroundColor: 'transparent',
      pointBackgroundColor: '#93c5fd',
      yAxisID: 'yCount',
      tension: 0.4,
      order: 1,
    },
  ],
}))

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: { mode: 'index', intersect: false },
  plugins: {
    legend: { position: 'bottom' },
    tooltip: {
      callbacks: {
        label: (ctx) => {
          if (ctx.dataset.yAxisID === 'yAmount') {
            return ` 銷售額：$${Number(ctx.raw).toLocaleString()}`
          }
          return ` 銷售量：${ctx.raw} 筆`
        },
      },
    },
  },
  scales: {
    yAmount: {
      type: 'linear',
      position: 'left',
      beginAtZero: true,
      ticks: { callback: v => `$${v.toLocaleString()}` },
    },
    yCount: {
      type: 'linear',
      position: 'right',
      beginAtZero: true,
      grid: { drawOnChartArea: false },
      ticks: {
        precision: 0,
        stepSize: 1,
      },
    },
  },
}
</script>

<template>
  <div class="mb-6">
    <!-- Summary cards + range picker row -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
      <!-- Stats cards -->
      <div class="flex gap-4">
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm px-5 py-3 min-w-[140px]">
          <p class="text-xs text-gray-500 mb-0.5">區間銷售額</p>
          <p class="text-xl font-semibold text-gray-900">{{ formattedAmount }}</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm px-5 py-3 min-w-[120px]">
          <p class="text-xs text-gray-500 mb-0.5">區間銷售量</p>
          <p class="text-xl font-semibold text-gray-900">{{ chartData.total_count }} 筆</p>
        </div>
      </div>

      <!-- Range controls -->
      <div class="flex flex-wrap items-center gap-2">
        <select
          v-model="selectedRange"
          @change="onRangeChange"
          class="rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
        >
          <option value="7d">過去 7 天</option>
          <option value="30d">過去 30 天</option>
          <option value="90d">過去 90 天</option>
          <option value="custom">自訂</option>
        </select>

        <!-- Custom date inputs -->
        <template v-if="selectedRange === 'custom'">
          <input
            v-model="customStart"
            type="text"
            placeholder="MM/DD/YYYY"
            class="rounded-md border-gray-300 shadow-sm text-sm w-32 focus:border-indigo-500 focus:ring-indigo-500"
          />
          <span class="text-gray-400 text-sm">—</span>
          <input
            v-model="customEnd"
            type="text"
            placeholder="MM/DD/YYYY"
            class="rounded-md border-gray-300 shadow-sm text-sm w-32 focus:border-indigo-500 focus:ring-indigo-500"
          />
          <button
            type="button"
            @click="onCustomApply"
            class="px-3 py-1.5 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 transition-colors"
          >
            套用
          </button>
        </template>
      </div>
    </div>

    <!-- Chart -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4" style="height: 360px;">
      <Bar :data="chartDataset" :options="chartOptions" style="height: 100%;" />
    </div>
  </div>
</template>
