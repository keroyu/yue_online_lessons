<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  state: { type: String, required: true },
  courseName: { type: String, default: null },
  courseSlug: { type: String, default: null },
  slotLabel: { type: String, default: null },
})

// One page, four outcomes — a stale link is a normal thing to happen, not an error.
const views = {
  confirmed: {
    icon: '✓',
    tone: 'bg-green-50 border-green-200 text-green-800',
    title: '確認已完成預約',
    body: '相關資料已寄出，建議在諮詢時間以前看完。',
  },
  already: {
    icon: '✓',
    tone: 'bg-green-50 border-green-200 text-green-800',
    title: '這筆預約已經確認過了',
    body: '不需要再做任何事，我們到時候見。相關資料先前已寄到你的信箱。',
  },
  expired: {
    icon: '!',
    tone: 'bg-amber-50 border-amber-200 text-amber-800',
    title: '確認連結已逾時',
    body: '保留的時段已經釋出。如果仍想預約，請回到課程頁重新申請並在 1 小時內完成確認。',
  },
  invalid: {
    icon: '?',
    tone: 'bg-gray-50 border-gray-200 text-gray-700',
    title: '連結無效',
    body: '這個確認連結無法辨識，可能是網址不完整。請直接使用信件中的連結。',
  },
}

const view = computed(() => views[props.state] ?? views.invalid)
const showSlot = computed(() => props.slotLabel && ['confirmed', 'already'].includes(props.state))
</script>

<template>
  <Head title="預約確認" />

  <div class="max-w-lg mx-auto px-4 py-16">
    <div class="rounded-2xl border p-8 text-center" :class="view.tone">
      <div class="w-12 h-12 mx-auto rounded-full bg-white/70 flex items-center justify-center text-2xl font-bold">
        {{ view.icon }}
      </div>
      <h1 class="mt-4 text-xl font-bold">{{ view.title }}</h1>
      <p class="mt-2 text-sm leading-relaxed">{{ view.body }}</p>

      <div v-if="showSlot" class="mt-5 rounded-xl bg-white/70 px-4 py-3 text-sm">
        <p v-if="courseName" class="font-semibold">{{ courseName }}</p>
        <p class="mt-1 tabular-nums">{{ slotLabel }}</p>
      </div>
    </div>

    <div class="mt-6 text-center">
      <Link
        v-if="courseSlug"
        :href="`/course/${courseSlug}`"
        class="text-sm text-brand-teal underline cursor-pointer hover:opacity-70 transition"
      >
        {{ state === 'expired' ? '回課程頁重新預約' : '回到課程頁' }}
      </Link>
      <Link
        v-else
        href="/"
        class="text-sm text-brand-teal underline cursor-pointer hover:opacity-70 transition"
      >
        回首頁
      </Link>
    </div>
  </div>
</template>
