<script setup>
import { computed, ref, watch } from 'vue'
import axios from 'axios'
import AdminModal from './AdminModal.vue'

/**
 * Student roster for one course (011 US37 / FR-208–FR-211).
 *
 * The list always comes from the server: the conditions span purchases, tiers
 * and credit balances, and the consume button below spends other people's
 * credits, so the browser's copy is never the source of truth (D144). This
 * component only renders what the endpoint returns and posts back ids.
 */
const props = defineProps({
  open: { type: Boolean, default: false },
  course: { type: Object, default: null }, // { id, name, has_bundle, bundle_name }
})

const emit = defineEmits(['close'])

const planId = ref('all')
const withCredit = ref(false)
const students = ref([])
const plans = ref([])
const loading = ref(false)
const selected = ref([])
const copied = ref(false)
const actionResult = ref('')
const consuming = ref(false)
const confirmingConsume = ref(false)
const granting = ref(false)
const confirmingGrant = ref(false)
const crediting = ref(false)
const confirmingCredit = ref(false)

const hasBundle = computed(() => !!props.course?.has_bundle)
const bundleName = computed(() => props.course?.bundle_name || '福利')

const selectedStudents = computed(() =>
  students.value.filter(s => selected.value.includes(s.user_id)),
)
// Deduped: one member cannot appear twice, but a stale list should not be able
// to produce a doubled recipient either.
const selectedEmails = computed(() => [...new Set(selectedStudents.value.map(s => s.email))])

const allSelected = computed(
  () => students.value.length > 0 && selected.value.length === students.value.length,
)

const toggleAll = () => {
  selected.value = allSelected.value ? [] : students.value.map(s => s.user_id)
}

const load = async () => {
  if (!props.course) return
  loading.value = true
  actionResult.value = ''
  try {
    const { data } = await axios.get(`/admin/courses/${props.course.id}/roster`, {
      params: { plan_id: planId.value, with_credit: withCredit.value ? 1 : 0 },
    })
    students.value = data.students
    plans.value = data.plans ?? []
    // Drop ticks for rows that are no longer on the list.
    const ids = new Set(data.students.map(s => s.user_id))
    selected.value = selected.value.filter(id => ids.has(id))
  } catch (e) {
    actionResult.value = '名單載入失敗，請重新整理後再試'
  } finally {
    loading.value = false
  }
}

watch(() => props.open, (isOpen) => {
  if (!isOpen) return
  planId.value = 'all'
  withCredit.value = false
  selected.value = []
  confirmingConsume.value = false
  confirmingGrant.value = false
  confirmingCredit.value = false
  load()
})

watch([planId, withCredit], () => {
  if (props.open) load()
})

const copyEmails = async () => {
  if (selectedEmails.value.length === 0) return
  try {
    await navigator.clipboard.writeText(selectedEmails.value.join(', '))
    copied.value = true
    setTimeout(() => { copied.value = false }, 2000)
  } catch (e) {
    actionResult.value = '複製失敗，請手動選取 Email'
  }
}

// Backfill for students who predate the perk (FR-219). Idempotent server side,
// so the confirm text can promise that a second press is harmless.
const grant = async () => {
  granting.value = true
  try {
    const { data } = await axios.post(`/admin/courses/${props.course.id}/bundle/grant`, {
      user_ids: selected.value,
    })
    actionResult.value = `已補發 ${data.granted} 位，${data.unchanged} 位未變動`
    await load()
  } catch (e) {
    actionResult.value = e.response?.data?.message || '補發失敗，請稍後再試'
  } finally {
    granting.value = false
    confirmingGrant.value = false
  }
}

// The undo for a mis-pressed deduction (FR-221): there is no ledger to roll
// back, and 補發 tops up to the plan's number, which helps nobody who already
// received it.
const credit = async () => {
  crediting.value = true
  try {
    const { data } = await axios.post(`/admin/courses/${props.course.id}/bundle/credit`, {
      user_ids: selected.value,
    })
    const unlimited = data.unlimited?.length
      ? `，其中 ${data.unlimited.length} 位為無限次未變動`
      : ''
    actionResult.value = `已補 ${data.credited} 位各 1 次${unlimited}`
    await load()
  } catch (e) {
    actionResult.value = e.response?.data?.message || '補次數失敗，請稍後再試'
  } finally {
    crediting.value = false
    confirmingCredit.value = false
  }
}

const consume = async () => {
  consuming.value = true
  try {
    const { data } = await axios.post(`/admin/courses/${props.course.id}/bundle/consume`, {
      user_ids: selected.value,
    })
    const skipped = data.skipped?.length
      ? `，${data.skipped.length} 位次數不足已跳過`
      : ''
    // Said out loud on purpose: unlimited members were not deducted, and a
    // silent pass would read as "done" (011 FR-215).
    const unlimited = data.unlimited?.length
      ? `，其中 ${data.unlimited.length} 位為無限次未扣除`
      : ''
    actionResult.value = `已扣 ${data.consumed} 位${skipped}${unlimited}`
    selected.value = []
    await load()
  } catch (e) {
    actionResult.value = e.response?.data?.message || '扣除失敗，請稍後再試'
  } finally {
    consuming.value = false
    confirmingConsume.value = false
  }
}
</script>

<template>
  <AdminModal
    :open="open"
    :title="`學員名單${course ? ` — ${course.name}` : ''}`"
    @close="emit('close')"
  >
    <!-- Conditions -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div class="flex flex-wrap items-center gap-3">
        <select
          v-model="planId"
          class="cursor-pointer rounded-lg border-gray-300 py-2 pl-3 pr-8 text-sm shadow-sm focus:border-brand-teal focus:ring-brand-teal"
        >
          <option value="all">
            全部方案
          </option>
          <option v-for="plan in plans" :key="plan.id" :value="plan.id">
            {{ plan.name }}
          </option>
          <option value="none">
            未指定方案
          </option>
        </select>

        <label v-if="hasBundle" class="flex cursor-pointer items-center gap-2 text-sm text-gray-700">
          <input
            v-model="withCredit"
            type="checkbox"
            class="h-4 w-4 cursor-pointer rounded border-gray-300 text-brand-teal focus:ring-brand-teal"
          />
          只列還有{{ bundleName }}次數的人
        </label>
      </div>

      <p class="text-sm text-gray-500">
        共 {{ students.length }} 位，已勾選 {{ selected.length }} 位
      </p>
    </div>

    <!-- Roster -->
    <div class="mt-4 max-h-96 overflow-auto rounded-lg ring-1 ring-gray-200">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="w-10 px-3 py-2">
              <input
                type="checkbox"
                :checked="allSelected"
                class="h-4 w-4 cursor-pointer rounded border-gray-300 text-brand-teal focus:ring-brand-teal"
                @change="toggleAll"
              />
            </th>
            <th class="px-3 py-2 text-left font-semibold text-gray-700">
              姓名
            </th>
            <th class="px-3 py-2 text-left font-semibold text-gray-700">
              Email
            </th>
            <th class="px-3 py-2 text-left font-semibold text-gray-700">
              加入時間
            </th>
            <th class="px-3 py-2 text-left font-semibold text-gray-700">
              方案
            </th>
            <th v-if="hasBundle" class="px-3 py-2 text-left font-semibold text-gray-700">
              剩餘次數
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
          <tr
            v-for="student in students"
            :key="student.user_id"
            class="cursor-pointer hover:bg-gray-50"
            @click="selected.includes(student.user_id)
              ? selected = selected.filter(id => id !== student.user_id)
              : selected.push(student.user_id)"
          >
            <td class="px-3 py-2">
              <input
                type="checkbox"
                :checked="selected.includes(student.user_id)"
                class="pointer-events-none h-4 w-4 rounded border-gray-300 text-brand-teal"
              />
            </td>
            <td class="px-3 py-2 text-gray-900">
              {{ student.name }}
            </td>
            <td class="px-3 py-2 text-gray-600">
              {{ student.email }}
            </td>
            <td class="whitespace-nowrap px-3 py-2 text-gray-500">
              {{ student.joined_at }}
            </td>
            <td class="px-3 py-2 text-gray-600">
              {{ student.plan_name || '—' }}
            </td>
            <td v-if="hasBundle" class="px-3 py-2 font-medium text-gray-900">
              <span v-if="student.unlimited" class="text-emerald-700">無限</span>
              <span v-else>{{ student.bundle_balance }}</span>
            </td>
          </tr>
          <tr v-if="!loading && students.length === 0">
            <td :colspan="hasBundle ? 6 : 5" class="px-3 py-8 text-center text-gray-500">
              沒有符合條件的學員
            </td>
          </tr>
          <tr v-if="loading">
            <td :colspan="hasBundle ? 6 : 5" class="px-3 py-8 text-center text-gray-500">
              載入中…
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <p v-if="actionResult" class="mt-3 text-sm text-brand-teal">
      {{ actionResult }}
    </p>

    <!-- Actions -->
    <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-center">
      <button
        type="button"
        :disabled="selected.length === 0"
        class="cursor-pointer rounded-lg bg-brand-teal px-4 py-2 text-sm font-medium text-white transition hover:bg-brand-navy disabled:cursor-not-allowed disabled:opacity-50"
        title="以逗號分隔複製所選 Email，可直接貼到郵件收件人欄"
        @click="copyEmails"
      >
        {{ copied ? `已複製 ${selectedEmails.length} 個 Email` : '複製 Email' }}
      </button>

      <template v-if="hasBundle">
        <button
          v-if="!confirmingGrant"
          type="button"
          :disabled="selected.length === 0"
          class="cursor-pointer rounded-lg border border-emerald-600 px-4 py-2 text-sm font-medium text-emerald-700 transition hover:bg-emerald-50 disabled:cursor-not-allowed disabled:opacity-50"
          title="把所選學員的次數補到其方案的預設值；已領過的人不會多拿"
          @click="confirmingGrant = true"
        >
          補發至方案預設次數
        </button>
        <div v-else class="flex items-center gap-2 rounded-lg border border-emerald-600 bg-emerald-50 px-3 py-2">
          <span class="text-sm text-emerald-900">
            把 {{ selected.length }} 位學員的{{ bundleName }}補到方案預設次數？已領過的人不會多拿。
          </span>
          <button
            type="button"
            :disabled="granting"
            class="cursor-pointer rounded-lg bg-emerald-600 px-3 py-1.5 text-sm font-medium text-white transition hover:bg-emerald-700 disabled:opacity-60"
            @click="grant"
          >
            {{ granting ? '處理中…' : '確認補發' }}
          </button>
          <button
            type="button"
            :disabled="granting"
            class="cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-600 transition hover:bg-gray-50"
            @click="confirmingGrant = false"
          >
            取消
          </button>
        </div>

        <button
          v-if="!confirmingConsume"
          type="button"
          :disabled="selected.length === 0"
          class="cursor-pointer rounded-lg border border-amber-600 px-4 py-2 text-sm font-medium text-amber-700 transition hover:bg-amber-50 disabled:cursor-not-allowed disabled:opacity-50"
          @click="confirmingConsume = true"
        >
          消費 1 次{{ bundleName }}
        </button>
        <div v-else class="flex items-center gap-2 rounded-lg border border-amber-600 bg-amber-50 px-3 py-2">
          <span class="text-sm text-amber-900">
            將把 {{ selected.length }} 位學員的{{ bundleName }}各扣 1 次？
          </span>
          <button
            type="button"
            :disabled="consuming"
            class="cursor-pointer rounded-lg bg-amber-600 px-3 py-1.5 text-sm font-medium text-white transition hover:bg-amber-700 disabled:opacity-60"
            @click="consume"
          >
            {{ consuming ? '處理中…' : '確認扣除' }}
          </button>
          <button
            type="button"
            :disabled="consuming"
            class="cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-600 transition hover:bg-gray-50"
            @click="confirmingConsume = false"
          >
            取消
          </button>
        </div>

        <button
          v-if="!confirmingCredit"
          type="button"
          :disabled="selected.length === 0"
          class="cursor-pointer rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
          title="扣錯了用這個補回來，一次 +1"
          @click="confirmingCredit = true"
        >
          補 1 次
        </button>
        <div v-else class="flex items-center gap-2 rounded-lg border border-gray-400 bg-gray-50 px-3 py-2">
          <span class="text-sm text-gray-700">
            將把 {{ selected.length }} 位學員的{{ bundleName }}各加 1 次？
          </span>
          <button
            type="button"
            :disabled="crediting"
            class="cursor-pointer rounded-lg bg-gray-700 px-3 py-1.5 text-sm font-medium text-white transition hover:bg-gray-800 disabled:opacity-60"
            @click="credit"
          >
            {{ crediting ? '處理中…' : '確認補 1 次' }}
          </button>
          <button
            type="button"
            :disabled="crediting"
            class="cursor-pointer rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-600 transition hover:bg-gray-50"
            @click="confirmingCredit = false"
          >
            取消
          </button>
        </div>
      </template>
    </div>
  </AdminModal>
</template>
