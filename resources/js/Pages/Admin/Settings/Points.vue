<script setup>
import { useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  points: { type: Object, required: true },
})

const form = useForm({
  referral_threshold_amount: props.points.referral_threshold_amount,
  referral_reward_rate: props.points.referral_reward_rate,
  homework_reward_points: props.points.homework_reward_points,
  referral_maturity_days: props.points.referral_maturity_days,
})

const submit = () => {
  form.post('/admin/settings/points', { preserveScroll: true })
}

const labelClasses = 'block text-sm font-medium text-gray-700 mb-1'
const inputClasses = 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-teal/30 focus:border-brand-teal text-sm'
const sectionClasses = 'bg-white shadow-sm rounded-lg p-6 space-y-4'
</script>

<template>
  <div class="max-w-2xl mx-auto py-8 px-4 space-y-6">
    <h1 class="text-xl font-bold text-gray-900">積分設定</h1>
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
</template>
