<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import HintBox from './HintBox.vue'

/**
 * Tier setup for a high-ticket course (011 US21).
 *
 * There is no "enable multi-plan" switch: the plan count is the switch. A
 * course with no plans behaves exactly as it did before this feature existed.
 */
const props = defineProps({
  courseId: { type: Number, required: true },
  plans: { type: Array, default: () => [] },
})

const newName = ref('')
const newPrice = ref('')

const editingId = ref(null)
const editName = ref('')
const editPrice = ref('')

const addPlan = () => {
  if (!newName.value.trim()) return

  router.post(`/admin/courses/${props.courseId}/plans`, {
    name: newName.value.trim(),
    price: newPrice.value === '' ? null : Number(newPrice.value),
  }, {
    preserveScroll: true,
    onSuccess: () => {
      newName.value = ''
      newPrice.value = ''
    },
  })
}

const startEdit = (plan) => {
  editingId.value = plan.id
  editName.value = plan.name
  editPrice.value = plan.price ?? ''
}

const cancelEdit = () => {
  editingId.value = null
}

const savePlan = () => {
  if (!editName.value.trim()) return

  router.put(`/admin/plans/${editingId.value}`, {
    name: editName.value.trim(),
    price: editPrice.value === '' ? null : Number(editPrice.value),
  }, {
    preserveScroll: true,
    onSuccess: cancelEdit,
  })
}

const deletePlan = (plan) => {
  if (!confirm(`確定要刪除方案「${plan.name}」嗎？小節的歸屬設定會一併移除。`)) return

  router.delete(`/admin/plans/${plan.id}`, { preserveScroll: true })
}
</script>

<template>
  <div class="bg-white shadow rounded-lg overflow-hidden mb-6">
    <div class="bg-gray-50 px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h3 class="text-sm font-medium text-gray-900">課程方案</h3>
        <p class="text-xs text-gray-500 mt-0.5">
          {{ plans.length === 0 ? '尚未設定方案，目前所有學員都看得到全部小節' : `已設定 ${plans.length} 個方案` }}
        </p>
      </div>

      <!-- Always-open add form: creating a plan is the whole point of this
           panel, so it should not be hidden behind a button first. -->
      <div class="flex items-center gap-2">
        <input
          v-model="newName"
          type="text"
          placeholder="方案名稱（例：方案A）"
          class="w-36 sm:w-44 rounded-md border-gray-300 shadow-sm focus:border-brand-teal focus:ring-brand-teal text-sm"
          @keyup.enter="addPlan"
        />
        <input
          v-model="newPrice"
          type="number"
          min="0"
          placeholder="建議價"
          class="w-24 sm:w-28 rounded-md border-gray-300 shadow-sm focus:border-brand-teal focus:ring-brand-teal text-sm"
          @keyup.enter="addPlan"
        />
        <button
          type="button"
          :disabled="!newName.trim()"
          class="shrink-0 inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-white bg-brand-teal hover:bg-brand-teal/90 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
          @click="addPlan"
        >
          新增方案
        </button>
      </div>
    </div>

    <div class="p-4 space-y-3">
      <HintBox title="多方案怎麼運作？">
        <ul class="list-disc pl-5 space-y-1">
          <li>建立方案後，到下方每個小節勾選它屬於哪些方案。<strong>同一個小節可以同時屬於多個方案</strong>（例如 A 給 EP1–4、B 給全部）。</li>
          <li><strong>沒有勾選任何方案的小節，持有方案的學員看不到</strong>，而且完全不會出現在他的章節列表裡（不是鎖頭）。</li>
          <li>沒有建立任何方案時 = 單一方案，所有學員看得到全部小節，與以往行為相同。</li>
          <li>「建議價格」只用來在 Leads 開通時自動帶入成交價，不會顯示在銷售頁。</li>
          <li>學員的學習進度百分比會依他的方案計算（方案 A 看完 4 集就是 100%）。</li>
        </ul>
      </HintBox>

      <!-- Plan cards: four per row rather than one full-width strip each —
           a plan is a name and a price, not a paragraph. -->
      <div v-if="plans.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div
          v-for="plan in plans"
          :key="plan.id"
          class="border border-gray-200 rounded-md p-3"
        >
          <template v-if="editingId === plan.id">
            <div class="space-y-2">
              <input
                v-model="editName"
                type="text"
                placeholder="方案名稱"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-teal focus:ring-brand-teal text-sm"
                @keyup.enter="savePlan"
                @keyup.escape="cancelEdit"
              />
              <input
                v-model="editPrice"
                type="number"
                min="0"
                placeholder="建議價（選填）"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-teal focus:ring-brand-teal text-sm"
                @keyup.enter="savePlan"
                @keyup.escape="cancelEdit"
              />
              <div class="flex items-center gap-3">
                <button type="button" class="text-green-600 hover:text-green-700 text-sm cursor-pointer" @click="savePlan">儲存</button>
                <button type="button" class="text-gray-400 hover:text-gray-600 text-sm cursor-pointer" @click="cancelEdit">取消</button>
              </div>
            </div>
          </template>

          <!-- Actions sit beside the text, not under it: two lines of content
               do not justify a three-line card. -->
          <template v-else>
            <div class="flex items-center justify-between gap-2">
              <div class="min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate" :title="plan.name">{{ plan.name }}</p>
                <p class="text-xs text-gray-500 mt-0.5">
                  <template v-if="plan.price !== null">建議價 NT$ {{ Number(plan.price).toLocaleString() }}</template>
                  <template v-else>未設建議價</template>
                </p>
              </div>
              <div class="flex items-center gap-2 shrink-0">
                <button type="button" class="text-brand-teal hover:text-brand-navy text-xs cursor-pointer" @click="startEdit(plan)">編輯</button>
                <button type="button" class="text-red-400 hover:text-red-600 text-xs cursor-pointer" @click="deletePlan(plan)">刪除</button>
              </div>
            </div>
          </template>
        </div>
      </div>

    </div>
  </div>
</template>
