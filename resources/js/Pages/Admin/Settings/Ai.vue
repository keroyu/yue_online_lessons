<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import HintBox from '@/Components/Admin/HintBox.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  credentials: { type: Object, required: true },
  models: { type: Object, default: () => ({}) },
  features: { type: Object, default: () => ({}) },
  prompts: { type: Array, default: () => [] },
})

const form = useForm({
  openai_api_key: '',
  openai_default_model: props.credentials.default_model,
  prompts: props.prompts.map((prompt) => ({
    id: prompt.id,
    instructions: prompt.instructions,
    model: prompt.model ?? '',
    max_output_tokens: prompt.max_output_tokens ?? null,
  })),
})

// Grouped for display only — the grouping key comes from the data, so a new AI
// feature shows up here without touching this file (000 US10).
const groups = computed(() => {
  const byFeature = new Map()

  props.prompts.forEach((prompt, index) => {
    if (!byFeature.has(prompt.feature)) {
      byFeature.set(prompt.feature, { key: prompt.feature, label: prompt.feature_label, rows: [] })
    }
    byFeature.get(prompt.feature).rows.push({ ...prompt, index })
  })

  return [...byFeature.values()]
})

const modelOptions = computed(() => Object.entries(props.models).map(([value, label]) => ({ value, label })))

const formatDate = (iso) => {
  if (!iso) return '—'
  return new Date(iso).toLocaleString('zh-TW', { dateStyle: 'medium', timeStyle: 'short' })
}

const submit = () => {
  form.post('/admin/settings/ai', { preserveScroll: true })
}

const labelClasses = 'block text-sm font-medium text-gray-700 mb-1'
const inputClasses = 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-teal/30 focus:border-brand-teal text-sm'
const sectionClasses = 'bg-white shadow-sm rounded-lg p-6 space-y-4'
</script>

<template>
  <div class="max-w-3xl mx-auto py-8 px-4 space-y-6">
    <h1 class="text-xl font-bold text-gray-900">AI 設定</h1>

    <form @submit.prevent="submit" class="space-y-6">
      <!-- 服務憑證 -->
      <div :class="sectionClasses">
        <div class="flex items-center justify-between border-b pb-2">
          <h2 class="text-base font-semibold text-gray-800">OpenAI</h2>
          <span
            class="text-xs px-2 py-0.5 rounded-full"
            :class="credentials.enabled ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
          >
            {{ credentials.enabled ? '已啟用' : '未設定' }}
          </span>
        </div>

        <p class="text-xs text-gray-500">
          未填 API Key 時，全站所有 AI 功能會<strong class="text-gray-700">靜默跳過</strong>，
          其餘流程照常運作、不會出錯 —— 所以本機開發與測試環境不需要填。
        </p>

        <HintBox title="要去哪裡拿 API Key？">
          <ol class="space-y-1 list-decimal list-inside">
            <li>登入 <span class="font-mono">platform.openai.com</span>，進入 API keys 頁面。</li>
            <li>建立一組新的 secret key，複製後貼到下方欄位。</li>
            <li>金鑰只會完整顯示一次，離開頁面後就看不到了，請先存好。</li>
            <li>該帳號需要有可用餘額，否則呼叫會回 429。</li>
          </ol>
        </HintBox>

        <div>
          <label :class="labelClasses">API Key</label>
          <input
            v-model="form.openai_api_key"
            type="password"
            autocomplete="new-password"
            :placeholder="credentials.api_key_preview || '尚未設定'"
            :class="inputClasses"
          />
          <p class="text-xs text-gray-500 mt-1">留白表示維持原本的金鑰不變。</p>
          <p v-if="form.errors.openai_api_key" class="text-xs text-red-600 mt-1">{{ form.errors.openai_api_key }}</p>
        </div>

        <div>
          <label :class="labelClasses">全站預設模型</label>
          <select v-model="form.openai_default_model" :class="inputClasses">
            <option value="">（使用系統內建預設）</option>
            <option v-for="option in modelOptions" :key="option.value" :value="option.value">
              {{ option.label }}
            </option>
          </select>
          <p class="text-xs text-gray-500 mt-1">下方各功能若沒有指定模型，就會用這一個。</p>
        </div>
      </div>

      <!-- 功能與 Prompt -->
      <div v-for="group in groups" :key="group.key" :class="sectionClasses">
        <h2 class="text-base font-semibold text-gray-800 border-b pb-2">{{ group.label }}</h2>

        <div v-for="row in group.rows" :key="row.id" class="space-y-3 pt-2 first:pt-0">
          <div class="border-t first:border-t-0 pt-4 first:pt-0">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
              <h3 class="text-sm font-semibold text-gray-900">{{ row.label }}</h3>
              <span class="text-xs text-gray-400">最後更新：{{ formatDate(row.updated_at) }}</span>
            </div>
            <p v-if="row.description" class="text-xs text-gray-500 mt-1">{{ row.description }}</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label :class="labelClasses">使用模型</label>
              <select v-model="form.prompts[row.index].model" :class="inputClasses">
                <option value="">（使用全站預設）</option>
                <option v-for="option in modelOptions" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option>
              </select>
            </div>

            <div>
              <label :class="labelClasses">輸出上限（tokens）</label>
              <input
                v-model.number="form.prompts[row.index].max_output_tokens"
                type="number"
                min="1"
                placeholder="留白使用預設"
                :class="inputClasses"
              />
            </div>
          </div>

          <div>
            <label :class="labelClasses">指示內容（instructions）</label>
            <textarea
              v-model="form.prompts[row.index].instructions"
              rows="12"
              :class="[inputClasses, 'font-mono text-xs leading-relaxed']"
            />
            <p
              v-if="form.errors[`prompts.${row.index}.instructions`]"
              class="text-xs text-red-600 mt-1"
            >
              {{ form.errors[`prompts.${row.index}.instructions`] }}
            </p>
          </div>
        </div>
      </div>

      <div v-if="!groups.length" :class="sectionClasses">
        <p class="text-sm text-gray-500">目前沒有任何 AI 功能的 prompt 設定。</p>
      </div>

      <div class="flex justify-end">
        <button
          type="submit"
          :disabled="form.processing"
          class="px-5 py-2 bg-brand-teal text-white text-sm font-medium rounded-lg hover:bg-brand-teal/90 disabled:opacity-50 transition-colors"
        >
          {{ form.processing ? '儲存中…' : '儲存設定' }}
        </button>
      </div>
    </form>
  </div>
</template>
