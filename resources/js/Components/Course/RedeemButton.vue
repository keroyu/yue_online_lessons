<script setup>
import { computed } from 'vue'

const props = defineProps({
  redeemPoints: { type: Number, default: null },        // null = 不可兌換
  userAvailablePoints: { type: Number, default: null }, // null = 未登入
  isOwned: { type: Boolean, default: false },
  confirming: { type: Boolean, default: false },         // 父層是否正在顯示確認面板
})

const emit = defineEmits(['request'])

const isRedeemable = computed(() => props.redeemPoints !== null && props.redeemPoints > 0)
const isGuest = computed(() => props.userAvailablePoints === null)
const canRedeem = computed(
  () => isRedeemable.value && !isGuest.value && props.userAvailablePoints >= props.redeemPoints
)
const shortfall = computed(() =>
  isRedeemable.value && !isGuest.value ? Math.max(0, props.redeemPoints - props.userAvailablePoints) : 0
)
</script>

<template>
  <div v-if="isRedeemable && !isOwned" class="mt-3">
    <!-- 未登入：導向登入 -->
    <a
      v-if="isGuest"
      href="/login"
      class="block w-full rounded-lg border border-emerald-600 px-4 py-3 text-center text-sm font-semibold text-emerald-700 transition hover:bg-emerald-50"
    >
      登入以 {{ redeemPoints }} 積分兌換
    </a>

    <!-- 已登入、積分足夠：點擊後由父層顯示確認面板（不直接兌換） -->
    <button
      v-else-if="canRedeem"
      type="button"
      :disabled="confirming"
      class="block w-full rounded-lg bg-emerald-600 px-4 py-3 text-center text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:opacity-60"
      @click="emit('request')"
    >
      {{ confirming ? '請確認兌換…' : `用 ${redeemPoints} 積分兌換` }}
    </button>

    <!-- 已登入、積分不足 -->
    <button
      v-else
      type="button"
      disabled
      class="block w-full cursor-not-allowed rounded-lg bg-gray-200 px-4 py-3 text-center text-sm font-semibold text-gray-500"
    >
      積分兌換需 {{ redeemPoints }} 點（還差 {{ shortfall }} 點）
    </button>
  </div>
</template>
