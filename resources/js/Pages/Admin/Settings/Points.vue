<script setup>
import { ref, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import ReferrerDetailModal from '@/Components/Admin/ReferrerDetailModal.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  points: { type: Object, required: true },
  referral: { type: Object, default: () => ({ rows: [], range: '30' }) },
})

// Tabs: 積分設定 / 推薦成效 (preserved across the referral range reloads via preserveState).
const activeTab = ref('settings')

// --- Settings form ---
const form = useForm({
  referral_threshold_amount: props.points.referral_threshold_amount,
  referral_reward_rate: props.points.referral_reward_rate,
  homework_reward_points: props.points.homework_reward_points,
  referral_maturity_days: props.points.referral_maturity_days,
  referral_discount_amount: props.points.referral_discount_amount,
})

const submit = () => {
  form.post('/admin/settings/points', { preserveScroll: true })
}

const labelClasses = 'block text-sm font-medium text-gray-700 mb-1'
const inputClasses = 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-teal/30 focus:border-brand-teal text-sm'
const sectionClasses = 'bg-white shadow-sm rounded-lg p-6 space-y-4'

// --- Referral performance ---
const ranges = [
  { value: '7', label: '7 天' },
  { value: '30', label: '30 天' },
  { value: '60', label: '60 天' },
  { value: '90', label: '90 天' },
  { value: 'all', label: '全部' },
]

const setRange = (value) => {
  router.get('/admin/settings/points', { range: value }, { preserveState: true, preserveScroll: true })
}

const totals = computed(() => props.referral.rows.reduce((acc, r) => ({
  orders: acc.orders + r.order_count,
  revenue: acc.revenue + r.revenue,
  points: acc.points + r.reward_points,
}), { orders: 0, revenue: 0, points: 0 }))

const fmtMoney = (n) => 'NT$ ' + Number(n || 0).toLocaleString()

// Referrer detail drill-down modal (US8)
const detailReferrerId = ref(null)
const openDetail = (id) => { detailReferrerId.value = id }
const closeDetail = () => { detailReferrerId.value = null }
</script>

<template>
  <div class="max-w-5xl mx-auto py-8 px-4 space-y-6">
    <h1 class="text-xl font-bold text-gray-900">積分與推薦</h1>

    <!-- Tab nav -->
    <div class="border-b border-gray-200">
      <nav class="flex gap-6">
        <button
          type="button"
          class="pb-3 text-sm font-medium border-b-2 -mb-px transition-colors cursor-pointer"
          :class="activeTab === 'settings' ? 'border-brand-teal text-brand-teal' : 'border-transparent text-gray-500 hover:text-gray-700'"
          @click="activeTab = 'settings'"
        >積分設定</button>
        <button
          type="button"
          class="pb-3 text-sm font-medium border-b-2 -mb-px transition-colors cursor-pointer"
          :class="activeTab === 'referral' ? 'border-brand-teal text-brand-teal' : 'border-transparent text-gray-500 hover:text-gray-700'"
          @click="activeTab = 'referral'"
        >推薦成效</button>
      </nav>
    </div>

    <!-- Tab: 積分設定 -->
    <div v-show="activeTab === 'settings'" class="max-w-2xl space-y-6">
      <p class="text-sm text-gray-500">調整後僅影響「之後」產生的積分與回饋；既有帳本紀錄不受影響。</p>

      <div v-if="form.recentlySuccessful" class="rounded-lg bg-green-50 border border-green-200 p-3 text-sm text-green-700">
        積分設定已儲存
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <div :class="sectionClasses">
          <h2 class="text-base font-semibold text-gray-800 border-b pb-2">推薦回饋</h2>

          <div>
            <label :class="labelClasses">推薦啟用門檻（元）</label>
            <input type="number" min="0" v-model.number="form.referral_threshold_amount" :class="inputClasses" />
            <p class="mt-1 text-xs text-gray-400">會員累計消費達此金額後，推薦碼自動啟用。</p>
            <p v-if="form.errors.referral_threshold_amount" class="mt-1 text-sm text-red-600">{{ form.errors.referral_threshold_amount }}</p>
          </div>

          <div>
            <label :class="labelClasses">回饋比例（%）</label>
            <input type="number" min="0" max="100" v-model.number="form.referral_reward_rate" :class="inputClasses" />
            <p class="mt-1 text-xs text-gray-400">好友付款後，推薦人可得「實付金額 × 此比例」的回饋積分（四捨五入到十位）。</p>
            <p v-if="form.errors.referral_reward_rate" class="mt-1 text-sm text-red-600">{{ form.errors.referral_reward_rate }}</p>
          </div>

          <div>
            <label :class="labelClasses">買家折抵金額（元）</label>
            <input type="number" min="0" v-model.number="form.referral_discount_amount" :class="inputClasses" />
            <p class="mt-1 text-xs text-gray-400">好友結帳輸入推薦碼時，訂單直接折抵此金額（可與折扣碼疊加，實付最低 1 元）；設 0 停用折抵，推薦回饋照常發放。</p>
            <p v-if="form.errors.referral_discount_amount" class="mt-1 text-sm text-red-600">{{ form.errors.referral_discount_amount }}</p>
          </div>

          <div>
            <label :class="labelClasses">回饋成熟天數</label>
            <input type="number" min="0" v-model.number="form.referral_maturity_days" :class="inputClasses" />
            <p class="mt-1 text-xs text-gray-400">回饋積分需經過此天數才可使用；亦為含回饋訂單的退款期限。</p>
            <p v-if="form.errors.referral_maturity_days" class="mt-1 text-sm text-red-600">{{ form.errors.referral_maturity_days }}</p>
          </div>
        </div>

        <div :class="sectionClasses">
          <h2 class="text-base font-semibold text-gray-800 border-b pb-2">作業獎勵</h2>

          <div>
            <label :class="labelClasses">作業完成獎勵點數</label>
            <input type="number" min="0" v-model.number="form.homework_reward_points" :class="inputClasses" />
            <p class="mt-1 text-xs text-gray-400">學員每完成一份作業可獲得的積分。</p>
            <p v-if="form.errors.homework_reward_points" class="mt-1 text-sm text-red-600">{{ form.errors.homework_reward_points }}</p>
          </div>
        </div>

        <div class="flex justify-end">
          <button
            type="submit"
            :disabled="form.processing"
            class="px-5 py-2 rounded-lg font-semibold text-sm bg-brand-teal text-white hover:bg-brand-teal/90 transition-colors disabled:opacity-50"
          >
            {{ form.processing ? '儲存中…' : '儲存設定' }}
          </button>
        </div>
      </form>
    </div>

    <!-- Tab: 推薦成效 -->
    <div v-show="activeTab === 'referral'" class="space-y-6">
      <p class="text-sm text-gray-500">各推薦人帶來的已付款訂單、營收與回饋積分，以及其目前的積分餘額。</p>

      <!-- Range switcher -->
      <div class="inline-flex rounded-lg bg-gray-100 p-1">
        <button
          v-for="r in ranges"
          :key="r.value"
          @click="setRange(r.value)"
          class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors cursor-pointer"
          :class="String(referral.range) === r.value ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
        >
          {{ r.label }}
        </button>
      </div>

      <!-- Summary -->
      <div class="grid grid-cols-3 gap-4">
        <div class="bg-white shadow-sm rounded-lg p-4">
          <p class="text-xs text-gray-500">推薦訂單數</p>
          <p class="mt-1 text-2xl font-bold text-gray-900">{{ totals.orders.toLocaleString() }}</p>
        </div>
        <div class="bg-white shadow-sm rounded-lg p-4">
          <p class="text-xs text-gray-500">推薦營收</p>
          <p class="mt-1 text-2xl font-bold text-gray-900">{{ fmtMoney(totals.revenue) }}</p>
        </div>
        <div class="bg-white shadow-sm rounded-lg p-4">
          <p class="text-xs text-gray-500">發放回饋積分</p>
          <p class="mt-1 text-2xl font-bold text-brand-teal">{{ totals.points.toLocaleString() }}</p>
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100 text-sm">
          <thead class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            <tr>
              <th class="px-4 py-3 text-left font-medium text-gray-500">推薦人</th>
              <th class="px-4 py-3 text-left font-medium text-gray-500">推薦碼</th>
              <th class="px-4 py-3 text-right font-medium text-gray-500">訂單數</th>
              <th class="px-4 py-3 text-right font-medium text-gray-500">營收</th>
              <th class="px-4 py-3 text-right font-medium text-gray-500">回饋積分</th>
              <th class="px-4 py-3 text-right font-medium text-gray-500">明細</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-if="referral.rows.length === 0">
              <td colspan="6" class="px-4 py-10 text-center text-gray-400">此區間沒有推薦訂單。</td>
            </tr>
            <tr
              v-for="(r, i) in referral.rows"
              :key="i"
              class="hover:bg-gray-50 cursor-pointer"
              @click="openDetail(r.referrer_user_id)"
            >
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <p class="font-medium text-gray-800">{{ r.referrer_name }}</p>
                  <span
                    class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700"
                    title="推薦人目前擁有的積分"
                  >目前積分 {{ Number(r.current_points || 0).toLocaleString() }}</span>
                </div>
                <p class="text-xs text-gray-400">{{ r.referrer_email }}</p>
              </td>
              <td class="px-4 py-3 font-mono text-gray-600">{{ r.referral_code }}</td>
              <td class="px-4 py-3 text-right text-gray-700">{{ r.order_count.toLocaleString() }}</td>
              <td class="px-4 py-3 text-right text-gray-700">{{ fmtMoney(r.revenue) }}</td>
              <td class="px-4 py-3 text-right font-semibold text-brand-teal">{{ r.reward_points.toLocaleString() }}</td>
              <td class="px-4 py-3 text-right">
                <button
                  type="button"
                  class="text-sm font-medium text-brand-teal hover:text-brand-teal/80 whitespace-nowrap"
                  @click.stop="openDetail(r.referrer_user_id)"
                >查看 ›</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Referrer detail drill-down (US8) -->
    <ReferrerDetailModal
      :show="detailReferrerId !== null"
      :referrer-id="detailReferrerId"
      @close="closeDetail"
    />
  </div>
</template>
