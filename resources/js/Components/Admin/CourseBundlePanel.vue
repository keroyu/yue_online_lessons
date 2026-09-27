<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

/**
 * Bundle perk setup for a high-ticket course (011 US37 / FR-201–FR-202).
 *
 * Two different save paths on purpose, because the data lives in two places:
 * the name and per-credit price are `courses` columns and ride along with the
 * course form (v-model straight onto it), while each tier's quantity is a
 * `course_plans` column and goes through the existing plan endpoint.
 *
 * Tiers themselves are read-only here — they are created on the chapters page,
 * where lesson assignment lives (D145). This panel only labels them.
 */
const props = defineProps({
  form: { type: Object, required: true },   // the parent CourseForm's useForm object
  plans: { type: Array, default: () => [] },
  labelClasses: { type: String, default: '' },
  inputClasses: { type: String, default: '' },
  helpTextClasses: { type: String, default: '' },
  errorTextClasses: { type: String, default: '' },
})

// Local copy so a click on 儲存次數 sends the plan's own row, not the whole form.
const quantities = ref(
  Object.fromEntries(props.plans.map(plan => [plan.id, plan.bundle_quantity ?? 0])),
)
// Unlimited is a tier flag, not a snapshot: ticking it covers everyone already
// holding that tier (011 D148).
const unlimited = ref(
  Object.fromEntries(props.plans.map(plan => [plan.id, !!plan.bundle_unlimited])),
)

const savingPlanId = ref(null)
const savedPlanId = ref(null)

const savePlanQuantity = (plan) => {
  savingPlanId.value = plan.id
  // name/price come back unchanged: the endpoint validates the whole plan row,
  // and this panel is not where those two are edited.
  router.put(`/admin/plans/${plan.id}`, {
    name: plan.name,
    price: plan.price,
    bundle_quantity: Number(quantities.value[plan.id]) || 0,
    bundle_unlimited: unlimited.value[plan.id],
  }, {
    preserveScroll: true,
    onSuccess: () => {
      savedPlanId.value = plan.id
      setTimeout(() => { if (savedPlanId.value === plan.id) savedPlanId.value = null }, 2000)
    },
    onFinish: () => { savingPlanId.value = null },
  })
}
</script>

<template>
  <div data-field="bundle_name" class="border border-amber-300 bg-amber-50 rounded-lg p-4">
    <p class="text-sm font-semibold text-amber-800 mb-1">
      附帶福利
    </p>
    <p class="text-sm text-amber-800/80 mb-4">
      成交時隨方案一起儲值的次數（例如「團體諮詢」）。福利名稱留空即代表此課程沒有附帶福利。
    </p>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label for="bundle_name" :class="labelClasses">福利名稱</label>
        <input
          id="bundle_name"
          v-model="form.bundle_name"
          type="text"
          maxlength="50"
          placeholder="例如：團體諮詢"
          :class="[inputClasses, form.errors.bundle_name ? 'border-red-300' : '']"
        />
        <p v-if="form.errors.bundle_name" :class="errorTextClasses">
          {{ form.errors.bundle_name }}
        </p>
      </div>

      <div>
        <label for="bundle_redeem_points" :class="labelClasses">每次加購所需積分</label>
        <input
          id="bundle_redeem_points"
          v-model="form.bundle_redeem_points"
          type="number"
          min="1"
          placeholder="留空 = 不可用積分加購"
          :class="[inputClasses, form.errors.bundle_redeem_points ? 'border-red-300' : '']"
        />
        <p :class="helpTextClasses">
          學員在「我的課程」可用積分加購，一次 +1 次。
        </p>
        <p v-if="form.errors.bundle_redeem_points" :class="errorTextClasses">
          {{ form.errors.bundle_redeem_points }}
        </p>
      </div>
    </div>

    <!-- Per-tier quantities. Saved one row at a time through the plan endpoint. -->
    <div v-if="plans.length > 0" class="mt-5">
      <p :class="labelClasses">各方案成交時儲值次數</p>
      <div class="mt-2 space-y-2">
        <div
          v-for="plan in plans"
          :key="plan.id"
          class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3 rounded-lg bg-white px-3 py-2 ring-1 ring-amber-200"
        >
          <span class="flex-1 text-sm font-medium text-gray-900">{{ plan.name }}</span>
          <div class="flex flex-wrap items-center gap-2">
            <input
              v-model="quantities[plan.id]"
              type="number"
              min="0"
              :disabled="unlimited[plan.id]"
              class="w-24 rounded-lg border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-brand-teal focus:ring-brand-teal disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-400"
            />
            <span class="text-sm text-gray-500">{{ unlimited[plan.id] ? '不計次' : '次' }}</span>
            <label class="flex cursor-pointer items-center gap-1.5 text-sm text-gray-700">
              <input
                v-model="unlimited[plan.id]"
                type="checkbox"
                class="h-4 w-4 cursor-pointer rounded border-gray-300 text-amber-600 focus:ring-amber-500"
              />
              無限次
            </label>
            <button
              type="button"
              :disabled="savingPlanId === plan.id"
              class="rounded-lg bg-amber-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-amber-700 disabled:opacity-60"
              @click="savePlanQuantity(plan)"
            >
              {{ savedPlanId === plan.id ? '已儲存' : (savingPlanId === plan.id ? '儲存中…' : '儲存次數') }}
            </button>
          </div>
        </div>
      </div>
      <p :class="helpTextClasses">
        方案本身在「章節編輯」頁新增或刪除；這裡只設定各方案帶幾次福利，次數與「無限次」需個別按「儲存次數」。勾「無限次」後該方案的學員不再計次，且既有學員立刻生效。
      </p>
    </div>

    <!-- No tiers: one number covers every sale of this course (FR-202). -->
    <div v-else class="mt-5">
      <label for="bundle_default_quantity" :class="labelClasses">預設儲值次數</label>
      <input
        id="bundle_default_quantity"
        v-model="form.bundle_default_quantity"
        type="number"
        min="0"
        class="mt-2 block w-32 rounded-lg border-gray-300 px-4 py-3 text-base shadow-sm focus:border-brand-teal focus:ring-brand-teal"
      />
      <label class="mt-2 flex w-fit cursor-pointer items-center gap-2 text-sm text-gray-700">
        <input
          v-model="form.bundle_unlimited"
          type="checkbox"
          class="h-4 w-4 cursor-pointer rounded border-gray-300 text-amber-600 focus:ring-amber-500"
        />
        無限次（不計次，勾選後上面的次數不生效）
      </label>
      <p :class="helpTextClasses">
        此課程尚未設定方案，成交時一律給這個次數。設定方案後改為各方案分別設定（無限次也改由方案決定）。
      </p>
      <p v-if="form.errors.bundle_default_quantity" :class="errorTextClasses">
        {{ form.errors.bundle_default_quantity }}
      </p>
    </div>
  </div>
</template>
