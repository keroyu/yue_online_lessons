<script setup>
import { computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  courses: { type: Array, default: () => [] },
})

const form = useForm({
  alias:         '',
  course_id:     null,
  type:          'fixed',
  value:         '',
  code_max_uses: 1,
  is_active:     true,
  note:          '',
})

const siteWide = computed({
  get: () => form.course_id === null,
  set: (v) => { form.course_id = v ? null : (props.courses[0]?.id ?? null) },
})

const digits = ['零', '一', '二', '三', '四', '五', '六', '七', '八', '九']
const ratioHint = computed(() => {
  const v = parseFloat(form.value)
  if (form.type !== 'ratio' || isNaN(v) || v < 0.5 || v > 0.95) return ''
  const n = Math.round(v * 100)
  const tens = Math.floor(n / 10)
  const units = n % 10
  return digits[tens] + (units > 0 ? digits[units] : '') + '折，用戶實付原價 ' + Math.round(v * 100) + '%'
})

const submit = () => form.post('/admin/coupon-chains')
</script>

<template>
  <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-2xl mx-auto">
    <div class="mb-6">
      <Link href="/admin/coupon-chains" class="text-sm text-gray-500 hover:text-gray-700">&larr; 返回折扣碼管理</Link>
      <h1 class="text-2xl font-bold text-gray-900 mt-2">新增輪換折扣碼</h1>
      <p class="mt-1 text-sm text-gray-500">設定後系統自動生成首支代碼，並在每次兌換後補發新代碼。</p>
    </div>

    <form @submit.prevent="submit" class="space-y-6 bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl p-6">

      <!-- 別名 -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">別名（用於佔位符，英數字與底線）</label>
        <div class="flex items-center gap-2">
          <span class="text-gray-400 font-mono">{</span>
          <input
            v-model="form.alias"
            type="text"
            maxlength="50"
            class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono lowercase focus:border-brand-teal focus:ring-1 focus:ring-brand-teal outline-none"
            placeholder="moneylesson1"
          />
          <span class="text-gray-400 font-mono">}</span>
        </div>
        <p class="text-xs text-gray-400 mt-1">在課程促銷內容中輸入 <code class="bg-gray-100 px-1 rounded">&#123;{{ form.alias || 'alias' }}&#125;</code> 即可顯示當前代碼</p>
        <p v-if="form.errors.alias" class="text-sm text-red-600 mt-1">{{ form.errors.alias }}</p>
      </div>

      <!-- 折扣類型 -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">折扣類型</label>
        <div class="flex gap-4">
          <label class="flex items-center gap-2 text-sm">
            <input type="radio" value="fixed" v-model="form.type" class="text-brand-teal" /> 固定金額
          </label>
          <label class="flex items-center gap-2 text-sm">
            <input type="radio" value="ratio" v-model="form.type" class="text-brand-teal" /> 折數
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
          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand-teal focus:ring-1 focus:ring-brand-teal outline-none"
        />
        <p v-if="ratioHint" class="text-xs text-brand-teal mt-1">{{ ratioHint }}</p>
        <p v-if="form.errors.value" class="text-sm text-red-600 mt-1">{{ form.errors.value }}</p>
      </div>

      <!-- 適用範圍 -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">適用範圍</label>
        <div class="flex gap-4 mb-2">
          <label class="flex items-center gap-2 text-sm">
            <input type="radio" :value="true" v-model="siteWide" class="text-brand-teal" /> 全站通用
          </label>
          <label class="flex items-center gap-2 text-sm">
            <input type="radio" :value="false" v-model="siteWide" class="text-brand-teal" /> 指定課程
          </label>
        </div>
        <select
          v-if="!siteWide"
          v-model="form.course_id"
          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand-teal focus:ring-1 focus:ring-brand-teal outline-none"
        >
          <option v-for="c in courses" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
        <p v-if="form.errors.course_id" class="text-sm text-red-600 mt-1">{{ form.errors.course_id }}</p>
      </div>

      <!-- 每碼名額 -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">每支代碼使用名額（0 = 無限制，不自動補碼）</label>
        <input
          v-model="form.code_max_uses"
          type="number"
          min="0"
          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand-teal focus:ring-1 focus:ring-brand-teal outline-none"
        />
        <p class="text-xs text-gray-400 mt-1">設為 1 = 每人一碼，兌換後立即補發新碼</p>
        <p v-if="form.errors.code_max_uses" class="text-sm text-red-600 mt-1">{{ form.errors.code_max_uses }}</p>
      </div>

      <!-- 備註 -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">備註說明（選填）</label>
        <input
          v-model="form.note"
          type="text"
          maxlength="255"
          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand-teal focus:ring-1 focus:ring-brand-teal outline-none"
        />
      </div>

      <!-- 啟用 -->
      <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" v-model="form.is_active" class="rounded text-brand-teal" /> 啟用此輪換折扣碼
      </label>

      <div class="flex items-center gap-3 pt-2">
        <button
          type="submit"
          :disabled="form.processing"
          class="px-4 py-2 rounded-lg bg-brand-teal text-white text-sm font-semibold hover:bg-brand-teal/90 disabled:opacity-50"
        >
          建立並生成首支代碼
        </button>
        <Link href="/admin/coupon-chains" class="text-sm text-gray-500 hover:text-gray-700">取消</Link>
      </div>
    </form>
  </div>
</template>
