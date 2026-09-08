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
    body: '預約確認信已經寄出，我們到時候見。',
    // Wording stays deliberately generic: the confirmation mail body lives in
    // email_templates and is editable from the admin, so naming a format here
    // would eventually point people at something that is no longer in the
    // mail — and nothing would fail (FR-181 / D129). The .ics is the one thing
    // the code attaches unconditionally, so that one can be named.
    reminders: [
      { label: '查收確認信', text: '信裡有時段、會議連結與行事曆邀請。沒收到請看一下垃圾郵件。' },
      { label: '把時段排進行程', text: '信中附有行事曆邀請，打開就能加進日曆。' },
      { label: '看完前置資料', text: '諮詢時間有限，先看完我們才有時間談真正重要的事。' },
    ],
  },
  already: {
    icon: '✓',
    tone: 'bg-green-50 border-green-200 text-green-800',
    title: '這筆預約已經確認過了',
    body: '不需要再確認一次，我們到時候見。',
    reminders: [
      { label: '查收確認信', text: '先前已寄到你的信箱，裡面有時段、會議連結與行事曆邀請。' },
      { label: '把時段排進行程', text: '信中附有行事曆邀請，打開就能加進日曆。' },
      { label: '看完前置資料', text: '諮詢時間有限，先看完我們才有時間談真正重要的事。' },
    ],
  },
  expired: {
    icon: '!',
    tone: 'bg-amber-50 border-amber-200 text-amber-800',
    title: '確認連結已逾時',
    body: '保留的時段已經釋出。如果仍想預約，請回到課程頁重新申請並在 1 小時內完成確認。',
  },
  // Covers two causes we can no longer tell apart: a malformed URL, and a link
  // whose application was swept after its hour ran out (FR-068). Blaming the
  // URL alone would tell a late clicker to do the thing they just did.
  invalid: {
    icon: '?',
    tone: 'bg-gray-50 border-gray-200 text-gray-700',
    title: '連結已失效',
    body: '這個確認連結可能已經逾時（保留的時段會在 1 小時後釋出），或是網址不完整。如果仍想預約，請回到課程頁重新申請。',
  },
}

const view = computed(() => views[props.state] ?? views.invalid)
const showSlot = computed(() => props.slotLabel && ['confirmed', 'already'].includes(props.state))
const reminders = computed(() => view.value.reminders ?? [])
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

      <!-- Three things to do before the consultation (FR-181) -->
      <div v-if="reminders.length" class="mt-5 rounded-xl bg-white/70 px-4 py-4 text-left">
        <p class="text-xs font-semibold tracking-wide uppercase opacity-70">諮詢前請完成</p>
        <ol class="mt-3 space-y-3">
          <li v-for="(item, i) in reminders" :key="item.label" class="flex gap-3 text-sm leading-relaxed">
            <span
              class="flex-shrink-0 w-5 h-5 mt-0.5 rounded-full bg-white flex items-center justify-center text-xs font-bold tabular-nums"
            >
              {{ i + 1 }}
            </span>
            <span>
              <strong class="font-semibold">{{ item.label }}</strong>
              <span class="opacity-90">：{{ item.text }}</span>
            </span>
          </li>
        </ol>
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
