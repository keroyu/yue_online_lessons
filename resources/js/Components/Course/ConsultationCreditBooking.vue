<script setup>
/**
 * Self-booking a 1-on-1 consultation from the sales page (011 US38).
 *
 * The holder has paid and is logged in, so there is no application here:
 * picking a time and confirming books it outright and spends one credit. The
 * page props carry the state; after a booking the endpoint returns the fresh
 * state, so this never needs a page reload.
 */
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
  courseId: { type: Number, required: true },
  offerName: { type: String, default: '1 對 1 諮詢' },
  // { balance, unlimited, redeem_points, active: { slot_label, zoom_join_url } | null }
  initial: { type: Object, required: true },
})

const state = ref({ ...props.initial })
const slotGroups = ref([])
const loading = ref(false)
const loadError = ref('')
const picked = ref(null)
const submitting = ref(false)
const submitError = ref('')
const justBooked = ref(false)

const canBook = computed(() =>
  !state.value.active && (state.value.unlimited || state.value.balance > 0)
)

const pickedLabel = computed(() => {
  for (const group of slotGroups.value) {
    const hit = group.times.find((t) => t.value === picked.value)
    if (hit) return `${group.date} ${hit.label}`
  }
  return ''
})

// "8/6（週三）" → day + weekday, same split as the application wizard.
const dateHead = (label) => {
  const parts = String(label).match(/^(.+?)（(.+?)）$/)
  return parts ? { day: parts[1], weekday: parts[2] } : { day: label, weekday: '' }
}

// Paged columns rather than a sideways scroller, like the application wizard.
const perPage = ref(4)
const pageIndex = ref(0)
const pages = computed(() => {
  const out = []
  for (let i = 0; i < slotGroups.value.length; i += perPage.value) {
    out.push(slotGroups.value.slice(i, i + perPage.value))
  }
  return out
})
const currentPage = computed(() => pages.value[pageIndex.value] ?? pages.value[0] ?? null)

const wideScreen = window.matchMedia('(min-width: 640px)')
const applyPerPage = () => {
  perPage.value = wideScreen.matches ? 6 : 4
  pageIndex.value = 0
}
applyPerPage()
wideScreen.addEventListener('change', applyPerPage)
onUnmounted(() => wideScreen.removeEventListener('change', applyPerPage))

watch(slotGroups, () => { pageIndex.value = 0 })

async function loadSlots() {
  loading.value = true
  loadError.value = ''
  try {
    const { data } = await axios.get(`/course/${props.courseId}/consultation-slots`)
    slotGroups.value = data.slots || []
  } catch (e) {
    loadError.value = '時段載入失敗，請重新整理頁面'
  } finally {
    loading.value = false
  }
}

async function confirmBooking() {
  if (!picked.value || submitting.value) return
  submitting.value = true
  submitError.value = ''
  try {
    const { data } = await axios.post(`/course/${props.courseId}/consultation-bookings`, {
      starts_at: picked.value,
    })
    state.value = data.booking
    picked.value = null
    justBooked.value = true
  } catch (e) {
    submitError.value = e.response?.data?.message || '預約失敗，請稍後再試'
    // Someone else took it: the list is stale, so refetch (FR-227 409).
    if (e.response?.status === 409) {
      picked.value = null
      loadSlots()
    }
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  if (canBook.value) loadSlots()

  // The "預約諮詢" link on 我的課程 lands here; this block renders after
  // mount, so the browser's own hash scroll has nothing to find.
  if (window.location.hash === '#consultation-booking') {
    document.getElementById('consultation-booking')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }
})
</script>

<template>
  <div id="consultation-booking" class="mx-auto mt-6 max-w-2xl scroll-mt-20 rounded-xl border border-gray-200 bg-white p-5 text-left shadow-sm sm:p-6">
    <div class="flex flex-wrap items-baseline justify-between gap-2">
      <h3 class="text-base font-bold text-brand-navy sm:text-lg">預約{{ offerName }}</h3>
      <span class="text-sm text-gray-600">
        {{ state.unlimited ? '不限次數' : `剩 ${state.balance} 次` }}
      </span>
    </div>
    <p class="mt-1 text-xs text-gray-500">每次 60 分鐘，線上進行。預約成功即扣 1 次，並寄出會議通知與行事曆邀請。</p>

    <!-- Booked and not over yet: one at a time (FR-229) -->
    <div v-if="state.active" class="mt-4 rounded-lg border border-teal-200 bg-teal-50 p-4">
      <p v-if="justBooked" class="text-sm font-semibold text-teal-800">預約成功！會議通知已寄到你的 Email。</p>
      <p class="text-sm text-teal-900" :class="{ 'mt-1': justBooked }">
        已預約：<span class="font-semibold tabular-nums">{{ state.active.slot_label }}</span>
      </p>
      <a
        v-if="state.active.zoom_join_url"
        :href="state.active.zoom_join_url"
        target="_blank"
        rel="noopener"
        class="mt-2 inline-block text-sm font-medium text-brand-teal underline cursor-pointer hover:text-teal-700"
      >
        開啟會議連結
      </a>
      <p class="mt-2 text-xs text-gray-600">如需改期或取消，請直接回覆會議通知信與我們聯絡。這場結束後即可預約下一場。</p>
    </div>

    <!-- Out of credits -->
    <div v-else-if="!canBook" class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4">
      <p class="text-sm font-semibold text-gray-800">諮詢次數已用完</p>
      <p v-if="state.redeem_points" class="mt-1 text-sm text-gray-600">
        可用 {{ state.redeem_points }} 積分加購 1 次：
        <a href="/member/learning" class="font-medium text-brand-teal underline cursor-pointer hover:text-teal-700">前往我的課程加購</a>
      </p>
    </div>

    <!-- Picker -->
    <div v-else class="mt-4 space-y-3">
      <p v-if="loading" class="text-sm text-gray-500">載入時段中…</p>
      <p v-else-if="loadError" class="text-sm text-red-600">{{ loadError }}</p>
      <p v-else-if="!slotGroups.length" class="rounded-lg bg-gray-50 p-4 text-sm text-gray-600">
        目前沒有開放的時段，請稍後再來看看。
      </p>

      <template v-else-if="currentPage">
        <div class="flex items-center justify-between gap-2">
          <p class="text-xs text-gray-500">請選擇開始時間（台北時間）</p>
          <div v-if="pages.length > 1" class="flex items-center gap-1">
            <button
              type="button"
              class="rounded-md border border-gray-200 p-1 text-gray-600 transition-colors cursor-pointer hover:bg-gray-50 hover:text-brand-teal disabled:opacity-30 disabled:cursor-not-allowed"
              aria-label="較早的日期"
              :disabled="pageIndex === 0"
              @click="pageIndex--"
            >
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
              </svg>
            </button>
            <button
              type="button"
              class="rounded-md border border-gray-200 p-1 text-gray-600 transition-colors cursor-pointer hover:bg-gray-50 hover:text-brand-teal disabled:opacity-30 disabled:cursor-not-allowed"
              aria-label="更晚的日期"
              :disabled="pageIndex >= pages.length - 1"
              @click="pageIndex++"
            >
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </button>
          </div>
        </div>

        <div class="flex max-h-80 gap-1 overflow-y-auto sm:gap-2">
          <div v-for="group in currentPage" :key="group.date" class="min-w-0 flex-1 basis-0">
            <div class="sticky top-0 z-10 bg-white pb-1.5 text-center">
              <p class="text-xs font-bold text-brand-navy tabular-nums sm:text-sm">{{ dateHead(group.date).day }}</p>
              <p class="text-[10px] leading-none text-gray-400">{{ dateHead(group.date).weekday }}</p>
            </div>
            <div class="flex flex-col gap-1 sm:gap-1.5">
              <button
                v-for="t in group.times"
                :key="t.value"
                type="button"
                class="w-full rounded-lg border px-0.5 py-2 text-xs tabular-nums cursor-pointer transition sm:text-sm"
                :class="picked === t.value
                  ? 'bg-brand-teal text-white border-brand-teal ring-2 ring-brand-teal/30'
                  : 'border-gray-200 text-gray-700 hover:bg-gray-50'"
                @click="picked = t.value; submitError = ''"
              >
                {{ t.label }}
              </button>
            </div>
          </div>
        </div>
      </template>

      <!-- Two-step confirm: spending a credit should never be one stray tap -->
      <div v-if="picked" class="rounded-lg border border-amber-200 bg-amber-50 p-4">
        <p class="text-sm text-amber-900">
          確定預約 <span class="font-semibold tabular-nums">{{ pickedLabel }}</span>？
          <template v-if="!state.unlimited">將使用 1 次諮詢，剩 {{ state.balance - 1 }} 次。</template>
        </p>
        <div class="mt-3 flex gap-2">
          <button
            type="button"
            :disabled="submitting"
            class="rounded-lg bg-brand-gold px-4 py-2 text-sm font-semibold text-brand-navy border border-brand-gold-dark/50 cursor-pointer transition hover:bg-brand-gold-dark disabled:opacity-60 disabled:cursor-not-allowed"
            @click="confirmBooking"
          >
            {{ submitting ? '預約中…' : '確定預約' }}
          </button>
          <button
            type="button"
            :disabled="submitting"
            class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-600 cursor-pointer transition hover:bg-gray-100 disabled:opacity-60"
            @click="picked = null"
          >
            取消
          </button>
        </div>
      </div>

      <p v-if="submitError" class="text-sm text-red-600">{{ submitError }}</p>
    </div>
  </div>
</template>
