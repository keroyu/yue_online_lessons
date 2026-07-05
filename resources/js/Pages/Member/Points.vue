<script setup>
import AppLayout from '@/Components/Layout/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import { ref } from 'vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  available:       { type: Number, default: 0 },
  pending:         { type: Number, default: 0 },
  referralCode:    { type: String, default: '' },
  referralActive:  { type: Boolean, default: false },
  thresholdAmount: { type: Number, default: 0 },
  rewardRate:      { type: Number, default: 0 },
  transactions:    { type: Object, default: () => ({ data: [], links: [] }) },
})

const TYPE_LABELS = {
  earn_homework:   '作業獎勵',
  redeem_course:   '兌換課程',
  earn_referral:   '推薦回饋',
  refund_reversal: '退款回收',
  admin_grant:     '後台派發',
}

const typeLabel = (t) => TYPE_LABELS[t] ?? t

const formatDate = (iso) =>
  new Date(iso).toLocaleDateString('zh-TW', { year: 'numeric', month: 'long', day: 'numeric' })

const copied = ref(false)
const copyCode = async () => {
  try {
    await navigator.clipboard.writeText(props.referralCode)
    copied.value = true
    setTimeout(() => (copied.value = false), 1500)
  } catch {
    // clipboard unavailable — ignore
  }
}
</script>

<template>
  <Head title="我的積分" />

  <div class="max-w-3xl mx-auto px-4 py-10">
    <h1 class="text-2xl font-bold text-brand-navy mb-6">我的積分</h1>

    <!-- Balance cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
      <div class="rounded-xl bg-white border border-gray-100 shadow-sm p-5">
        <p class="text-sm text-gray-500">可用積分</p>
        <p class="mt-1 text-3xl font-bold text-brand-teal">{{ available.toLocaleString() }}</p>
        <p class="text-xs text-gray-400 mt-1">已成熟、可立即用於兌換</p>
      </div>
      <div class="rounded-xl bg-white border border-gray-100 shadow-sm p-5">
        <p class="text-sm text-gray-500">未成熟積分</p>
        <p class="mt-1 text-3xl font-bold text-gray-400">{{ pending.toLocaleString() }}</p>
        <p class="text-xs text-gray-400 mt-1">推薦回饋，成熟後自動計入可用</p>
      </div>
    </div>

    <!-- Referral code -->
    <div class="rounded-xl bg-white border border-gray-100 shadow-sm p-5 mb-6">
      <h2 class="font-semibold text-brand-navy mb-3">我的推薦碼</h2>
      <div class="flex items-center gap-3">
        <code class="px-3 py-2 rounded-lg bg-gray-50 border border-gray-200 text-lg font-mono tracking-widest">{{ referralCode }}</code>
        <button
          @click="copyCode"
          class="px-3 py-2 rounded-lg text-sm font-medium bg-brand-navy text-white hover:bg-brand-navy/90 transition-colors"
        >
          {{ copied ? '已複製' : '複製' }}
        </button>
      </div>
      <p v-if="referralActive" class="text-sm text-gray-500 mt-3">
        分享推薦碼給好友，好友結帳輸入後、完成付款，你就能獲得對方實付金額 <strong class="text-brand-teal">{{ rewardRate }}%</strong> 的回饋積分。
      </p>
      <p v-else class="text-sm text-amber-600 mt-3">
        推薦碼尚未啟用。當你累計消費滿 NT$ {{ thresholdAmount.toLocaleString() }} 後即自動啟用，屆時好友使用你的推薦碼消費，你即可獲得對方實付金額 <strong>{{ rewardRate }}%</strong> 的回饋積分。
      </p>
    </div>

    <!-- Ledger -->
    <div class="rounded-xl bg-white border border-gray-100 shadow-sm p-5">
      <h2 class="font-semibold text-brand-navy mb-3">積分明細</h2>

      <div v-if="transactions.data.length === 0" class="text-center text-gray-400 py-8">
        目前沒有任何積分紀錄。
      </div>

      <ul v-else class="divide-y divide-gray-100">
        <li v-for="(tx, i) in transactions.data" :key="i" class="flex items-center justify-between py-3">
          <div class="min-w-0">
            <p class="text-sm font-medium text-gray-800">
              {{ typeLabel(tx.type) }}
              <span v-if="!tx.is_matured" class="ml-2 text-xs text-amber-600">（成熟於 {{ formatDate(tx.available_at) }}）</span>
            </p>
            <p class="text-xs text-gray-400 mt-0.5">
              {{ formatDate(tx.created_at) }}<span v-if="tx.note"> · {{ tx.note }}</span>
            </p>
          </div>
          <span
            class="text-sm font-bold shrink-0 ml-4"
            :class="tx.amount >= 0 ? 'text-brand-teal' : 'text-red-500'"
          >
            {{ tx.amount >= 0 ? '+' : '' }}{{ tx.amount.toLocaleString() }}
          </span>
        </li>
      </ul>

      <!-- Pagination -->
      <div v-if="transactions.links && transactions.links.length > 3" class="flex flex-wrap gap-1 mt-4 justify-center">
        <template v-for="(link, i) in transactions.links" :key="i">
          <Link
            v-if="link.url"
            :href="link.url"
            class="px-3 py-1.5 rounded-md text-sm transition-colors"
            :class="link.active ? 'bg-brand-navy text-white' : 'text-gray-600 hover:bg-gray-100'"
            preserve-scroll
            v-html="link.label"
          />
          <span
            v-else
            class="px-3 py-1.5 rounded-md text-sm text-gray-300"
            v-html="link.label"
          />
        </template>
      </div>
    </div>
  </div>
</template>
