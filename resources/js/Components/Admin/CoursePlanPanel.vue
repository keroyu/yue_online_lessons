<script setup>
import { ref, computed } from 'vue'
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
  chapters: { type: Array, default: () => [] },
  standaloneLessons: { type: Array, default: () => [] },
})

/**
 * Chapter shortcuts. Ticking chips one lesson at a time is fine for a tweak
 * and miserable for "plan B covers everything" — this assigns a whole chapter
 * in one click.
 *
 * Standalone lessons ride along as a pseudo-group so the shortcut can cover
 * the entire course, not just the chaptered part of it.
 */
const groups = computed(() => {
  const list = props.chapters.map(c => ({
    key: `c${c.id}`,
    title: c.title,
    lessonIds: c.lessons.map(l => l.id),
  }))

  if (props.standaloneLessons.length > 0) {
    list.push({
      key: 'standalone',
      title: '獨立小節',
      lessonIds: props.standaloneLessons.map(l => l.id),
    })
  }

  return list.filter(g => g.lessonIds.length > 0)
})

const allLessonIds = computed(() => groups.value.flatMap(g => g.lessonIds))

// Which lessons each plan currently holds, read back off the lesson payloads
// so this panel and the chips below can never disagree.
const planLessonIds = (planId) => {
  const ids = []
  for (const c of props.chapters) {
    for (const l of c.lessons) if ((l.plan_ids || []).includes(planId)) ids.push(l.id)
  }
  for (const l of props.standaloneLessons) {
    if ((l.plan_ids || []).includes(planId)) ids.push(l.id)
  }
  return ids
}

/** 'all' | 'some' | 'none' — drives the tri-state look of each shortcut. */
const groupState = (planId, group) => {
  const held = new Set(planLessonIds(planId))
  const hits = group.lessonIds.filter(id => held.has(id)).length
  if (hits === 0) return 'none'
  return hits === group.lessonIds.length ? 'all' : 'some'
}

const shortcutOpenId = ref(null)

const toggleShortcut = (planId) => {
  shortcutOpenId.value = shortcutOpenId.value === planId ? null : planId
}

const syncPlanLessons = (planId, lessonIds) => {
  router.put(`/admin/plans/${planId}/lessons`, { lesson_ids: lessonIds }, {
    preserveScroll: true,
  })
}

// Whole chapter in, or whole chapter out. Partial counts as "not yet in",
// so a second click after a partial selection completes it rather than
// clearing what was already there.
const toggleGroup = (planId, group) => {
  const held = new Set(planLessonIds(planId))
  const state = groupState(planId, group)

  if (state === 'all') {
    group.lessonIds.forEach(id => held.delete(id))
  } else {
    group.lessonIds.forEach(id => held.add(id))
  }

  syncPlanLessons(planId, [...held])
}

const selectAll = (planId) => syncPlanLessons(planId, allLessonIds.value)
const clearAll = (planId) => syncPlanLessons(planId, [])

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
  <!-- No overflow-hidden: the chapter shortcut popover has to escape the card.
       The header carries its own rounded corners instead. -->
  <div class="bg-white shadow rounded-lg mb-6">
    <div class="bg-gray-50 rounded-t-lg px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
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

            <!-- Chapter shortcuts, in a popover so the card keeps its height -->
            <div v-if="groups.length > 0" class="relative mt-2">
              <button
                type="button"
                class="w-full inline-flex items-center justify-between gap-1 px-2 py-1 rounded border border-gray-200 text-xs text-gray-600 hover:bg-gray-50 hover:text-gray-800 cursor-pointer"
                :aria-expanded="shortcutOpenId === plan.id"
                @click="toggleShortcut(plan.id)"
              >
                <span>選取章節（{{ planLessonIds(plan.id).length }}/{{ allLessonIds.length }} 節）</span>
                <svg class="w-3 h-3 shrink-0 transition-transform" :class="shortcutOpenId === plan.id ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                </svg>
              </button>

              <!-- Click-anywhere-else closes it; a backdrop beats a document
                   listener here because it cannot outlive the popover. -->
              <div v-if="shortcutOpenId === plan.id" class="fixed inset-0 z-10" @click="shortcutOpenId = null"></div>

              <div
                v-if="shortcutOpenId === plan.id"
                class="absolute z-20 mt-1 w-full min-w-[13rem] rounded-md border border-gray-200 bg-white shadow-lg p-1.5 space-y-0.5"
              >
                <button
                  v-for="group in groups"
                  :key="group.key"
                  type="button"
                  class="w-full flex items-center gap-2 px-2 py-1.5 rounded text-left text-xs hover:bg-gray-100 cursor-pointer"
                  @click="toggleGroup(plan.id, group)"
                >
                  <span
                    class="shrink-0 w-3.5 h-3.5 rounded border flex items-center justify-center"
                    :class="{
                      'bg-brand-teal border-brand-teal': groupState(plan.id, group) === 'all',
                      'bg-brand-teal/30 border-brand-teal': groupState(plan.id, group) === 'some',
                      'border-gray-300': groupState(plan.id, group) === 'none',
                    }"
                  >
                    <svg v-if="groupState(plan.id, group) === 'all'" class="w-2.5 h-2.5 text-white" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.5 7.5a1 1 0 0 1-1.4 0L3.3 9.7a1 1 0 1 1 1.4-1.4l3.8 3.8 6.8-6.8a1 1 0 0 1 1.4 0Z" clip-rule="evenodd" />
                    </svg>
                  </span>
                  <span class="flex-1 truncate text-gray-800" :title="group.title">{{ group.title }}</span>
                  <span class="shrink-0 text-gray-400">{{ group.lessonIds.length }}</span>
                </button>

                <div class="flex items-center gap-3 border-t border-gray-100 pt-1.5 mt-1 px-2">
                  <button type="button" class="text-xs text-brand-teal hover:text-brand-navy cursor-pointer" @click="selectAll(plan.id)">全選</button>
                  <button type="button" class="text-xs text-gray-500 hover:text-gray-700 cursor-pointer" @click="clearAll(plan.id)">全部清除</button>
                </div>
              </div>
            </div>
          </template>
        </div>
      </div>

    </div>
  </div>
</template>
