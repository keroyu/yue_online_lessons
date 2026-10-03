<script setup>
/**
 * Consultation credit setup (011 US37 / FR-201–FR-202, US38 / FR-222).
 *
 * High-ticket courses grant per tier and an admin spends credits from the
 * roster; ordinary paid courses grant one number per sale and the customer
 * spends them by booking a slot on the sales page.
 *
 * Everything here is part of the course form and saves with 儲存課程. It used to
 * have its own per-tier save buttons, which made the two fields at the top look
 * saved when they were not — the page already promises one save button, and a
 * panel that contradicts it loses data silently (D145 revised).
 *
 * Tiers themselves are read-only here: they are created on the chapters page,
 * where lesson assignment lives. This panel only labels them.
 */
import { computed } from 'vue'

const props = defineProps({
  form: { type: Object, required: true },   // the parent CourseForm's useForm object
  plans: { type: Array, default: () => [] },
  labelClasses: { type: String, default: '' },
  inputClasses: { type: String, default: '' },
  helpTextClasses: { type: String, default: '' },
  errorTextClasses: { type: String, default: '' },
})

// Ordinary courses spend credits by self-booking (FR-223).
const selfBooking = computed(() => props.form.type !== 'high_ticket')

// Without a points price, an ordinary course's credits cannot be topped up, so
// a customer who used them all can never book again (D154).
const noTopUp = computed(() => selfBooking.value && !props.form.bundle_redeem_points)

// The form object is created before this panel mounts, but a plan added in
// another tab would otherwise have no row to write into.
const rowFor = (planId) => {
  if (!props.form.bundle_plans[planId]) {
    props.form.bundle_plans[planId] = { quantity: 0, unlimited: false }
  }

  return props.form.bundle_plans[planId]
}
</script>

<template>
  <div data-field="bundle_name" class="border border-amber-300 bg-amber-50 rounded-lg p-4">
    <p class="text-sm font-semibold text-amber-800 mb-1">
      諮詢次數
    </p>
    <p v-if="selfBooking" class="text-sm text-amber-800/80 mb-4">
      學員購買後取得的 1 對 1 諮詢次數（每次 1 小時），可在銷售頁自行選時段預約，預約成功即扣 1 次。諮詢名稱留空即代表此課程不附諮詢。
    </p>
    <p v-else class="text-sm text-amber-800/80 mb-4">
      成交時隨方案一起儲值的諮詢次數，由管理員在學員名單扣除。諮詢名稱留空即代表此課程不附諮詢。
    </p>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label for="bundle_name" :class="labelClasses">諮詢名稱</label>
        <input
          id="bundle_name"
          v-model="form.bundle_name"
          type="text"
          maxlength="50"
          placeholder="例如：1 對 1 諮詢"
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
        <p v-if="noTopUp" class="mt-1 text-sm text-amber-700">
          未設加購點數：次數用完後學員將無法再預約。
        </p>
        <p v-if="form.errors.bundle_redeem_points" :class="errorTextClasses">
          {{ form.errors.bundle_redeem_points }}
        </p>
      </div>
    </div>

    <!-- Per-tier quantities, saved with the course form -->
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
              v-model="rowFor(plan.id).quantity"
              type="number"
              min="0"
              :disabled="rowFor(plan.id).unlimited"
              class="w-24 rounded-lg border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-brand-teal focus:ring-brand-teal disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-400"
            />
            <span class="text-sm text-gray-500">{{ rowFor(plan.id).unlimited ? '不計次' : '次' }}</span>
            <label class="flex cursor-pointer items-center gap-1.5 text-sm text-gray-700">
              <input
                v-model="rowFor(plan.id).unlimited"
                type="checkbox"
                class="h-4 w-4 cursor-pointer rounded border-gray-300 text-amber-600 focus:ring-amber-500"
              />
              無限次
            </label>
          </div>
        </div>
      </div>
      <p :class="helpTextClasses">
        方案本身在「章節編輯」頁新增或刪除。勾「無限次」後該方案的學員不再計次，且既有學員立刻生效。
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
        :disabled="form.bundle_unlimited"
        class="mt-2 block w-32 rounded-lg border-gray-300 px-4 py-3 text-base shadow-sm focus:border-brand-teal focus:ring-brand-teal disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-400"
      />
      <label class="mt-2 flex w-fit cursor-pointer items-center gap-2 text-sm text-gray-700">
        <input
          v-model="form.bundle_unlimited"
          type="checkbox"
          class="h-4 w-4 cursor-pointer rounded border-gray-300 text-amber-600 focus:ring-amber-500"
        />
        無限次（不計次）
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
