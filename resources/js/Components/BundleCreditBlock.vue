<script setup>
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'

/**
 * Consultation credit block on a "my courses" card (011 US37 / FR-207, US38).
 *
 * Renders nothing at all when the course has no perk — a card that gained an
 * empty box or a "0 次" line would be a regression for every other course.
 *
 * Top-up is two-step like the course redemption on the sales page (007 US1):
 * spending points should never be one stray tap away.
 */
const props = defineProps({
  planName: { type: String, default: null },
  bundle: { type: Object, default: null }, // { purchase_id, name, balance, redeem_points }
  availablePoints: { type: Number, default: null },
})

const cost = computed(() => props.bundle?.redeem_points ?? null)
// Unlimited hides the top-up entirely: buying more of an uncapped perk means
// nothing, and the endpoint refuses it anyway (011 FR-217).
const isUnlimited = computed(() => !!props.bundle?.unlimited)
// Never granted anything, and not unlimited either: this member predates the
// perk being added to the course, so the block stays hidden rather than
// claiming "剩 0 次" — which reads as "I used mine up" (FR-218 / D149).
const neverGranted = computed(
  () => !isUnlimited.value
    && (props.bundle?.granted ?? 0) === 0
    && (props.bundle?.balance ?? 0) === 0,
)
const canBuy = computed(() => !isUnlimited.value && cost.value !== null && cost.value > 0)
const affordable = computed(
  () => canBuy.value && props.availablePoints !== null && props.availablePoints >= cost.value,
)
const shortfall = computed(
  () => (canBuy.value && props.availablePoints !== null
    ? Math.max(0, cost.value - props.availablePoints)
    : 0),
)

const confirming = ref(false)
const submitting = ref(false)

const redeem = () => {
  submitting.value = true
  router.post(`/member/purchases/${props.bundle.purchase_id}/bundle-redeem`, {}, {
    preserveScroll: true,
    onFinish: () => {
      submitting.value = false
      confirming.value = false
    },
  })
}
</script>

<template>
  <div v-if="bundle && !neverGranted" class="mt-3 rounded-lg bg-brand-cream/60 px-3 py-2">
    <p v-if="planName" class="text-xs text-gray-500">
      方案：<span class="font-medium text-brand-navy">{{ planName }}</span>
    </p>

    <div class="mt-1 flex items-center justify-between gap-2">
      <span class="text-sm text-brand-navy">{{ bundle.name }}</span>
      <span v-if="isUnlimited" class="text-sm font-semibold text-emerald-700">無限次</span>
      <span v-else class="text-sm font-semibold text-brand-navy">剩 {{ bundle.balance }} 次</span>
    </div>

    <!-- Ordinary courses book their consultations on the sales page (011 US38) -->
    <template v-if="bundle.self_booking">
      <p v-if="bundle.active_slot_label" class="mt-1 text-xs text-teal-800">
        已預約：<span class="font-semibold tabular-nums">{{ bundle.active_slot_label }}</span>
      </p>
      <a
        v-else-if="isUnlimited || bundle.balance > 0"
        :href="bundle.booking_url"
        class="mt-2 block w-full cursor-pointer rounded-lg border border-brand-teal px-3 py-2 text-center text-xs font-semibold text-brand-teal transition hover:bg-brand-teal/10"
      >
        預約諮詢
      </a>
    </template>

    <!-- Top-up, only when the course sets a per-credit price -->
    <template v-if="canBuy">
      <div v-if="!confirming">
        <button
          v-if="affordable"
          type="button"
          class="mt-2 w-full cursor-pointer rounded-lg border border-emerald-600 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-50"
          @click="confirming = true"
        >
          用 {{ cost }} 積分加購 1 次
        </button>
        <button
          v-else
          type="button"
          disabled
          class="mt-2 w-full cursor-not-allowed rounded-lg bg-gray-200 px-3 py-2 text-xs font-semibold text-gray-500"
        >
          加購 1 次需 {{ cost }} 積分（還差 {{ shortfall }} 點）
        </button>
      </div>

      <div v-else class="mt-2 rounded-lg border border-emerald-600 bg-white p-3">
        <p class="text-xs text-gray-700">
          將扣除 {{ cost }} 積分，加購 1 次{{ bundle.name }}。
        </p>
        <div class="mt-2 flex gap-2">
          <button
            type="button"
            :disabled="submitting"
            class="flex-1 cursor-pointer rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-emerald-700 disabled:opacity-60"
            @click="redeem"
          >
            {{ submitting ? '處理中…' : '確認加購' }}
          </button>
          <button
            type="button"
            :disabled="submitting"
            class="cursor-pointer rounded-lg border border-gray-300 px-3 py-2 text-xs font-medium text-gray-600 transition hover:bg-gray-50"
            @click="confirming = false"
          >
            取消
          </button>
        </div>
      </div>
    </template>
  </div>
</template>
