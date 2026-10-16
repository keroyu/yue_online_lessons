<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

// 011 US40 / FR-244 — review one testimonial: show it on the sales page,
// hide it, or delete it. Points already paid are never touched.
const props = defineProps({
  lead: { type: Object, required: true },
  testimonial: { type: Object, required: true },
})

const emit = defineEmits(['close'])

const STATUS_LABELS = {
  pending: { text: '待審核', class: 'bg-amber-100 text-amber-800' },
  published: { text: '已顯示', class: 'bg-green-100 text-green-800' },
  hidden: { text: '已隱藏', class: 'bg-gray-100 text-gray-600' },
}

// Same wording as Testimonial::IDENTITY_LABELS (011 FR-246).
const IDENTITIES = { consultation: '諮詢客戶', student: '課程學員' }

const busy = ref(false)
const confirmingDelete = ref(false)

const taipei = (iso, withTime = true) => {
  if (!iso) return '—'
  const parts = new Intl.DateTimeFormat('zh-TW', {
    timeZone: 'Asia/Taipei',
    year: 'numeric', month: '2-digit', day: '2-digit',
    ...(withTime ? { hour: '2-digit', minute: '2-digit', hour12: false } : {}),
  }).formatToParts(new Date(iso))
  const get = (type) => parts.find((p) => p.type === type)?.value
  const date = `${get('year')}/${get('month')}/${get('day')}`
  return withTime ? `${date} ${get('hour')}:${get('minute')}` : date
}

const visitOptions = {
  preserveScroll: true,
  onStart: () => { busy.value = true },
  onFinish: () => { busy.value = false },
  onSuccess: () => emit('close'),
}

const setStatus = (status) => {
  router.patch(`/admin/testimonials/${props.testimonial.id}`, { status }, visitOptions)
}

const setIdentity = (identity) => {
  if (identity === props.testimonial.identity) return
  router.patch(`/admin/testimonials/${props.testimonial.id}`, { identity }, visitOptions)
}

const destroy = () => {
  router.delete(`/admin/testimonials/${props.testimonial.id}`, visitOptions)
}
</script>

<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center">
    <div class="fixed inset-0 bg-black/40" @click="emit('close')" />
    <div class="relative bg-white rounded-lg shadow-xl p-6 w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
      <div class="flex items-start justify-between gap-3 mb-4">
        <h3 class="text-lg font-semibold text-gray-900">客戶評語</h3>
        <span
          class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
          :class="STATUS_LABELS[testimonial.status]?.class"
        >
          {{ STATUS_LABELS[testimonial.status]?.text }}
        </span>
      </div>

      <div class="rounded-md border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 space-y-1">
        <p><span class="text-gray-500">預約者：</span>{{ lead.name }}（{{ lead.email }}）</p>
        <p><span class="text-gray-500">暱稱：</span>{{ testimonial.nickname }}</p>
        <p><span class="text-gray-500">頭銜：</span>{{ testimonial.title || '—' }}</p>
        <div class="flex items-center gap-2 pt-1">
          <span class="text-gray-500">身份：</span>
          <div class="inline-flex rounded-md border border-gray-300 overflow-hidden">
            <button
              v-for="(label, key) in IDENTITIES"
              :key="key"
              type="button"
              class="px-2.5 py-1 text-xs font-medium transition-colors disabled:opacity-50"
              :class="testimonial.identity === key ? 'bg-brand-teal text-white' : 'bg-white text-gray-600 hover:bg-gray-100'"
              :disabled="busy"
              @click="setIdentity(key)"
            >
              {{ label }}
            </button>
          </div>
        </div>
        <p class="tabular-nums"><span class="text-gray-500">諮詢時間：</span>{{ taipei(testimonial.consulted_at) }}</p>
        <p class="tabular-nums"><span class="text-gray-500">提交時間：</span>{{ taipei(testimonial.created_at) }}</p>
        <p><span class="text-gray-500">已發積分：</span>{{ testimonial.reward_points }} 點</p>
      </div>

      <p class="mt-4 text-sm text-gray-800 leading-relaxed whitespace-pre-line break-words">{{ testimonial.content }}</p>

      <div v-if="confirmingDelete" class="mt-5 rounded-md border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800">
        確定刪除這則評語？已發出的 {{ testimonial.reward_points }} 點積分不會收回，此動作無法復原。
        <div class="mt-3 flex justify-end gap-2">
          <button
            type="button"
            class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
            :disabled="busy"
            @click="confirmingDelete = false"
          >
            取消
          </button>
          <button
            type="button"
            class="px-3 py-1.5 text-sm font-medium text-white bg-rose-600 rounded-md hover:bg-rose-700 disabled:opacity-50"
            :disabled="busy"
            @click="destroy"
          >
            確認刪除
          </button>
        </div>
      </div>

      <div v-else class="mt-6 flex flex-wrap items-center justify-between gap-3">
        <button
          type="button"
          class="text-sm text-rose-600 hover:text-rose-800 hover:underline"
          :disabled="busy"
          @click="confirmingDelete = true"
        >
          刪除
        </button>
        <div class="flex gap-2">
          <button
            type="button"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
            @click="emit('close')"
          >
            關閉
          </button>
          <button
            v-if="testimonial.status !== 'published'"
            type="button"
            class="px-4 py-2 text-sm font-medium text-white bg-brand-teal rounded-md hover:bg-brand-teal/90 disabled:opacity-50"
            :disabled="busy"
            @click="setStatus('published')"
          >
            顯示於銷售頁
          </button>
          <button
            v-else
            type="button"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 disabled:opacity-50"
            :disabled="busy"
            @click="setStatus('hidden')"
          >
            從銷售頁隱藏
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
