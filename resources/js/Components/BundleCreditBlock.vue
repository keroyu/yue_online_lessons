<script setup>
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'

/**
 * Bundle perk block on a "my courses" card (011 US37 / FR-207).
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
const canBuy = computed(() => cost.value !== null && cost.value > 0)
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
  <div v-if="bundle" class="mt-3 rounded-lg bg-brand-cream/60 px-3 py-2">
    <p v-if="planName" class="text-xs text-gray-500">
      方案：<span class="font-medium text-brand-navy">{{ planName }}</span>
    </p>

    <div class="mt-1 flex items-center justify-between gap-2">
      <span class="text-sm text-brand-navy">{{ bundle.name }}</span>
      <span class="text-sm font-semibold text-brand-navy">剩 {{ bundle.balance }} 次</span>
    </div>

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
