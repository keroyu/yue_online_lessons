<script setup>
import { useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  payuni: { type: Object, required: true },
  newebpay: { type: Object, required: true },
  meta_pixel_id: { type: String, default: '' },
})

const form = useForm({
  payuni_merchant_id: props.payuni.merchant_id,
  payuni_hash_key: '',
  payuni_hash_iv: '',
  newebpay_merchant_id: props.newebpay.merchant_id,
  newebpay_hash_key: '',
  newebpay_hash_iv: '',
  newebpay_env: props.newebpay.env,
  meta_pixel_id: props.meta_pixel_id,
})

const submit = () => {
  form.post('/admin/settings/payment')
}

const labelClasses = 'block text-sm font-medium text-gray-700 mb-1'
const inputClasses = 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-teal/30 focus:border-brand-teal text-sm'
const sectionClasses = 'bg-white shadow-sm rounded-lg p-6 space-y-4'
</script>

<template>
  <div class="max-w-2xl mx-auto py-8 px-4 space-y-6">
    <h1 class="text-xl font-bold text-gray-900">金流設定</h1>

    <form @submit.prevent="submit" class="space-y-6">
      <!-- PayUni -->
      <div :class="sectionClasses">
        <h2 class="text-base font-semibold text-gray-800 border-b pb-2">PayUni 統一金流</h2>

        <div>
          <label :class="labelClasses">商店代號（MerchantID）</label>
          <input type="text" v-model="form.payuni_merchant_id" :class="inputClasses" placeholder="M00001" />
          <p v-if="form.errors.payuni_merchant_id" class="mt-1 text-sm text-red-600">{{ form.errors.payuni_merchant_id }}</p>
        </div>

        <div>
          <label :class="labelClasses">HashKey</label>
          <input type="password" v-model="form.payuni_hash_key" :class="inputClasses" placeholder="已儲存，輸入新值以更新" autocomplete="new-password" />
          <p v-if="form.errors.payuni_hash_key" class="mt-1 text-sm text-red-600">{{ form.errors.payuni_hash_key }}</p>
        </div>

        <div>
          <label :class="labelClasses">HashIV</label>
          <input type="password" v-model="form.payuni_hash_iv" :class="inputClasses" placeholder="已儲存，輸入新值以更新" autocomplete="new-password" />
          <p v-if="form.errors.payuni_hash_iv" class="mt-1 text-sm text-red-600">{{ form.errors.payuni_hash_iv }}</p>
        </div>
      </div>

      <!-- NewebPay -->
      <div :class="sectionClasses">
        <h2 class="text-base font-semibold text-gray-800 border-b pb-2">藍新金流（NewebPay）</h2>

        <div>
          <label :class="labelClasses">商店代號（MerchantID）</label>
          <input type="text" v-model="form.newebpay_merchant_id" :class="inputClasses" placeholder="MS1234567890" />
          <p v-if="form.errors.newebpay_merchant_id" class="mt-1 text-sm text-red-600">{{ form.errors.newebpay_merchant_id }}</p>
        </div>

        <div>
          <label :class="labelClasses">HashKey</label>
          <input type="password" v-model="form.newebpay_hash_key" :class="inputClasses" placeholder="已儲存，輸入新值以更新" autocomplete="new-password" />
          <p v-if="form.errors.newebpay_hash_key" class="mt-1 text-sm text-red-600">{{ form.errors.newebpay_hash_key }}</p>
        </div>

        <div>
          <label :class="labelClasses">HashIV</label>
          <input type="password" v-model="form.newebpay_hash_iv" :class="inputClasses" placeholder="已儲存，輸入新值以更新" autocomplete="new-password" />
          <p v-if="form.errors.newebpay_hash_iv" class="mt-1 text-sm text-red-600">{{ form.errors.newebpay_hash_iv }}</p>
        </div>

        <div>
          <label :class="labelClasses">環境</label>
          <select v-model="form.newebpay_env" :class="inputClasses">
            <option value="sandbox">Sandbox（測試）</option>
            <option value="production">Production（正式）</option>
          </select>
          <p v-if="form.errors.newebpay_env" class="mt-1 text-sm text-red-600">{{ form.errors.newebpay_env }}</p>
        </div>
      </div>

      <!-- Meta Pixel -->
      <div :class="sectionClasses">
        <h2 class="text-base font-semibold text-gray-800 border-b pb-2">Meta Pixel</h2>

        <div>
          <label :class="labelClasses">Pixel ID</label>
          <input type="text" v-model="form.meta_pixel_id" :class="inputClasses" placeholder="1287511383482442" />
          <p class="mt-1 text-xs text-gray-500">留空表示停用 Meta Pixel（頁面不輸出任何 fbq 代碼）</p>
          <p v-if="form.errors.meta_pixel_id" class="mt-1 text-sm text-red-600">{{ form.errors.meta_pixel_id }}</p>
        </div>
      </div>

      <div class="flex justify-end">
        <button
          type="submit"
          :disabled="form.processing"
          class="px-6 py-2 bg-brand-teal text-white font-medium rounded-lg hover:bg-brand-teal/80 disabled:opacity-50 transition-colors"
        >
          {{ form.processing ? '儲存中...' : '儲存設定' }}
        </button>
      </div>
    </form>
  </div>
</template>
