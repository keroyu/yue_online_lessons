<script setup>
import { computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'

const props = defineProps({
  coupon: { type: Object, default: null },
  courses: { type: Array, default: () => [] },
})

const isEdit = computed(() => !!props.coupon)

const form = useForm({
  code:       props.coupon?.code ?? '',
  type:       props.coupon?.type ?? 'fixed',
  value:      props.coupon?.value ?? '',
  course_id:  props.coupon?.course_id ?? null,
  expires_at: props.coupon?.expires_at ?? '',
  max_uses:   props.coupon?.max_uses ?? '',
  is_active:  props.coupon?.is_active ?? true,
  note:       props.coupon?.note ?? '',
})

// 適用範圍：true = 全站通用（course_id = null）
const siteWide = computed({
  get: () => form.course_id === null,
  set: (v) => { form.course_id = v ? null : (props.courses[0]?.id ?? null) },
})

// ratio 折數即時說明：0.6 → 六折
const digits = ['零', '一', '二', '三', '四', '五', '六', '七', '八', '九']
const ratioHint = computed(() => {
  const v = parseFloat(form.value)
  if (form.type !== 'ratio' || isNaN(v) || v < 0.5 || v > 0.95) return ''
  const n = Math.round(v * 100)
  const tens = Math.floor(n / 10)
  const units = n % 10
  const label = digits[tens] + (units > 0 ? digits[units] : '') + '折'
  const pct = Math.round(v * 100)
  return `${label}，用戶實付原價 ${pct}%`
})

const submit = () => {
  if (isEdit.value) {
    form.put(`/admin/coupons/${props.coupon.id}`)
  } else {
    form.transform((data) => ({ ...data, code: (data.code || '').toUpperCase() }))
      .post('/admin/coupons')
  }
}
</script>

<template>
  <form @submit.prevent="submit" class="space-y-6 bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl p-6">
    <!-- 代碼 -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">折扣碼（1–6 位英數）</label>
      <input
        v-model="form.code"
        type="text"
        maxlength="6"
        :disabled="isEdit"
        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm uppercase focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none disabled:bg-gray-100 disabled:text-gray-500"
        placeholder="例：SUMMER"
      />
      <p v-if="isEdit" class="text-xs text-gray-400 mt-1">代碼建立後不可修改</p>
      <p v-if="form.errors.code" class="text-sm text-red-600 mt-1">{{ form.errors.code }}</p>
    </div>

    <!-- 折扣類型 -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">折扣類型</label>
      <div class="flex gap-4">
        <label class="flex items-center gap-2 text-sm">
          <input type="radio" value="fixed" v-model="form.type" class="text-indigo-600" /> 固定金額
        </label>
        <label class="flex items-center gap-2 text-sm">
          <input type="radio" value="ratio" v-model="form.type" class="text-indigo-600" /> 折數
        </label>
      </div>
    </div>

    <!-- 折扣值 -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">
        {{ form.type === 'fixed' ? '折抵金額（NT$，最低 10）' : '折數（0.50–0.95）' }}
      </label>
      <input
        v-model="form.value"
        type="number"
        :step="form.type === 'ratio' ? '0.05' : '1'"
        :min="form.type === 'ratio' ? '0.5' : '10'"
        :max="form.type === 'ratio' ? '0.95' : undefined"
        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none"
      />
      <p v-if="ratioHint" class="text-xs text-brand-teal mt-1">{{ ratioHint }}</p>
      <p v-if="form.errors.value" class="text-sm text-red-600 mt-1">{{ form.errors.value }}</p>
    </div>

    <!-- 適用範圍 -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">適用範圍</label>
      <div class="flex gap-4 mb-2">
        <label class="flex items-center gap-2 text-sm">
          <input type="radio" :value="true" v-model="siteWide" class="text-indigo-600" /> 全站通用
        </label>
        <label class="flex items-center gap-2 text-sm">
          <input type="radio" :value="false" v-model="siteWide" class="text-indigo-600" /> 指定課程
        </label>
      </div>
      <select
        v-if="!siteWide"
        v-model="form.course_id"
        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none"
      >
        <option v-for="c in courses" :key="c.id" :value="c.id">{{ c.name }}</option>
      </select>
      <p v-if="form.errors.course_id" class="text-sm text-red-600 mt-1">{{ form.errors.course_id }}</p>
    </div>

    <!-- 到期日 + 名額 -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">到期日（可留空 = 永不過期）</label>
        <input
          v-model="form.expires_at"
          type="datetime-local"
          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none"
        />
        <p v-if="form.errors.expires_at" class="text-sm text-red-600 mt-1">{{ form.errors.expires_at }}</p>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">使用名額（可留空 = 無限制）</label>
        <input
          v-model="form.max_uses"
          type="number"
          min="1"
          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none"
        />
        <p v-if="form.errors.max_uses" class="text-sm text-red-600 mt-1">{{ form.errors.max_uses }}</p>
      </div>
    </div>

    <!-- 備註 -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">備註說明（選填）</label>
      <input
        v-model="form.note"
        type="text"
        maxlength="255"
        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none"
      />
    </div>

    <!-- 啟用 -->
    <label class="flex items-center gap-2 text-sm">
      <input type="checkbox" v-model="form.is_active" class="rounded text-indigo-600" /> 啟用此折扣碼
    </label>

    <div class="flex items-center gap-3 pt-2">
      <button
        type="submit"
        :disabled="form.processing"
        class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 disabled:opacity-50"
      >
        {{ isEdit ? '儲存變更' : '建立折扣碼' }}
      </button>
      <Link href="/admin/coupons" class="text-sm text-gray-500 hover:text-gray-700">取消</Link>
    </div>
  </form>
</template>
