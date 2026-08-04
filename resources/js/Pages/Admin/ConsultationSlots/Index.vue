<script setup>
import { ref, computed } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  groups: { type: Array, default: () => [] },
})

const page = usePage()
const flash = computed(() => page.props.flash?.success)

const form = useForm({
  date: '',
  start_time: '10:00',
  end_time: '12:00',
})

const showForm = ref(false)

function submit() {
  form.post('/admin/consultation-slots', {
    preserveScroll: true,
    onSuccess: () => {
      form.reset('date')
      showForm.value = false
    },
  })
}

function remove(slot) {
  if (!confirm(`確定刪除 ${slot.time} 這個時段？`)) return
  router.delete(`/admin/consultation-slots/${slot.id}`, { preserveScroll: true })
}

const stateStyles = {
  available: 'bg-green-50 border-green-200 text-green-800',
  held: 'bg-amber-50 border-amber-200 text-amber-800',
  booked: 'bg-gray-100 border-gray-300 text-gray-600',
}

const stateLabels = {
  available: '可預約',
  held: '暫留中',
  booked: '已預約',
}

function leadSearchUrl(email) {
  return `/admin/high-ticket-leads?search=${encodeURIComponent(email)}`
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between gap-4 flex-wrap">
      <div>
        <h1 class="text-xl font-bold text-gray-900">諮詢時段</h1>
        <p class="mt-1 text-sm text-gray-500">
          以 15 分鐘為 1 單位。一場諮詢預設佔 2 個單位（30 分鐘），使用預約優惠碼者佔 3 個（45 分鐘）。
        </p>
      </div>
      <button
        type="button"
        class="px-4 py-2 rounded-lg bg-brand-teal text-white text-sm font-medium cursor-pointer hover:bg-teal-700 transition"
        @click="showForm = !showForm"
      >
        {{ showForm ? '收起' : '新增時段' }}
      </button>
    </div>

    <div v-if="flash" class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
      {{ flash }}
    </div>

    <form
      v-if="showForm"
      class="bg-white rounded-xl border border-gray-200 p-5 space-y-4"
      @submit.prevent="submit"
    >
      <div class="grid gap-4 sm:grid-cols-3">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">日期</label>
          <input
            v-model="form.date"
            type="date"
            class="w-full rounded-lg border-gray-300 text-sm focus:border-brand-teal focus:ring-brand-teal"
            :class="{ 'border-red-300': form.errors.date }"
          >
          <p v-if="form.errors.date" class="mt-1 text-sm text-red-600">{{ form.errors.date }}</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">開始時間</label>
          <input
            v-model="form.start_time"
            type="time"
            step="900"
            class="w-full rounded-lg border-gray-300 text-sm focus:border-brand-teal focus:ring-brand-teal"
            :class="{ 'border-red-300': form.errors.start_time }"
          >
          <p v-if="form.errors.start_time" class="mt-1 text-sm text-red-600">{{ form.errors.start_time }}</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">結束時間</label>
          <input
            v-model="form.end_time"
            type="time"
            step="900"
            class="w-full rounded-lg border-gray-300 text-sm focus:border-brand-teal focus:ring-brand-teal"
            :class="{ 'border-red-300': form.errors.end_time }"
          >
          <p v-if="form.errors.end_time" class="mt-1 text-sm text-red-600">{{ form.errors.end_time }}</p>
        </div>
      </div>
      <p class="text-xs text-gray-500">
        時間須為 15 分鐘的整數倍。已存在的單位會自動略過，可以重複對同一天加時段。
      </p>
      <button
        type="submit"
        :disabled="form.processing"
        class="px-4 py-2 rounded-lg bg-brand-teal text-white text-sm font-medium cursor-pointer hover:bg-teal-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
      >
        {{ form.processing ? '建立中…' : '建立時段' }}
      </button>
    </form>

    <div v-if="!props.groups.length" class="bg-white rounded-xl border border-gray-200 p-10 text-center">
      <p class="text-sm text-gray-500">尚未建立任何時段。訪客在預約流程中會看到「目前沒有開放的時段」。</p>
    </div>

    <div v-for="group in props.groups" :key="group.date" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <h2 class="px-4 py-3 text-sm font-semibold text-gray-700 border-b border-gray-100">
        {{ group.date }}
        <span class="ml-2 font-normal text-xs text-gray-400">{{ group.slots.length }} 個單位</span>
      </h2>
      <div class="p-4 flex flex-wrap gap-2">
        <div
          v-for="slot in group.slots"
          :key="slot.id"
          class="rounded-lg border px-3 py-2 text-sm"
          :class="stateStyles[slot.state]"
        >
          <div class="flex items-center gap-2">
            <span class="font-medium tabular-nums">{{ slot.time }}</span>
            <span class="text-xs">{{ stateLabels[slot.state] }}</span>
            <button
              v-if="slot.state === 'available'"
              type="button"
              class="text-xs text-gray-400 cursor-pointer hover:text-red-600 transition"
              title="刪除此時段"
              @click="remove(slot)"
            >
              ✕
            </button>
          </div>
          <a
            v-if="slot.lead"
            :href="leadSearchUrl(slot.lead.email)"
            class="mt-1 block text-xs underline cursor-pointer hover:opacity-70"
          >
            {{ slot.lead.name }}
          </a>
        </div>
      </div>
    </div>
  </div>
</template>
