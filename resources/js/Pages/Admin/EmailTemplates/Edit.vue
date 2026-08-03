<script setup>
import { useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { marked } from 'marked'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  template: {
    type: Object,
    required: true,
  },
  availableVariables: {
    type: Array,
    default: () => [],
  },
})

const form = useForm({
  name: props.template.name,
  subject: props.template.subject,
  body_type: props.template.body_type || 'markdown',
  body_md: props.template.body_md,
})

const bodyTextarea = ref(null)
const showPreview = ref(false)

const isHtml = computed(() => form.body_type === 'html')

// Preview in an isolated iframe: the real mail blade carries no CSS at all, so
// rendering inside the admin page's `prose` styles made the preview look better
// than the delivered mail. `sandbox` also stops pasted <script> from running.
const renderedPreview = computed(() =>
  isHtml.value ? form.body_md || '' : marked(form.body_md || '', { breaks: true })
)

const insertAtCursor = (variable) => {
  const textarea = bodyTextarea.value
  if (!textarea) {
    form.body_md += variable
    return
  }
  const start = textarea.selectionStart
  const end = textarea.selectionEnd
  form.body_md = form.body_md.substring(0, start) + variable + form.body_md.substring(end)
  setTimeout(() => {
    textarea.focus()
    textarea.selectionStart = textarea.selectionEnd = start + variable.length
  }, 0)
}

const submit = () => {
  form.put(`/admin/email-templates/${props.template.id}`)
}
</script>

<template>
  <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-4xl mx-auto">
      <div class="mb-6">
        <a href="/admin/email-templates" class="text-sm text-brand-teal hover:underline">← 返回模板列表</a>
        <h1 class="mt-2 text-2xl font-bold text-gray-900">編輯模板：{{ template.name }}</h1>
        <p class="mt-1 text-sm text-gray-500">事件類型：{{ template.event_type }}</p>
      </div>

      <form @submit.prevent="submit" class="space-y-6">
        <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl p-6 space-y-6">
          <!-- Name -->
          <div>
            <label class="block text-sm font-semibold text-gray-900 mb-1">模板名稱</label>
            <input
              v-model="form.name"
              type="text"
              class="mt-1 block w-full rounded-lg border-gray-300 px-4 py-2.5 text-sm shadow-sm focus:border-brand-teal focus:ring-brand-teal"
              :class="{ 'border-red-300': form.errors.name }"
            />
            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
          </div>

          <!-- Subject -->
          <div>
            <label class="block text-sm font-semibold text-gray-900 mb-1">郵件主旨</label>
            <input
              v-model="form.subject"
              type="text"
              class="mt-1 block w-full rounded-lg border-gray-300 px-4 py-2.5 text-sm shadow-sm focus:border-brand-teal focus:ring-brand-teal"
              :class="{ 'border-red-300': form.errors.subject }"
            />
            <p v-if="form.errors.subject" class="mt-1 text-sm text-red-600">{{ form.errors.subject }}</p>
          </div>

          <!-- Body -->
          <div>
            <div class="flex flex-wrap items-center justify-between gap-3 mb-2">
              <label class="block text-sm font-semibold text-gray-900">郵件內容</label>
              <div class="flex items-center gap-3">
                <div class="inline-flex rounded-lg border border-gray-300 overflow-hidden">
                  <button
                    v-for="mode in [{ value: 'markdown', label: 'Markdown' }, { value: 'html', label: 'HTML' }]"
                    :key="mode.value"
                    type="button"
                    @click="form.body_type = mode.value"
                    class="px-3 py-1.5 text-xs font-medium transition-colors cursor-pointer"
                    :class="form.body_type === mode.value
                      ? 'bg-brand-teal text-white'
                      : 'bg-white text-gray-600 hover:bg-gray-50'"
                  >
                    {{ mode.label }}
                  </button>
                </div>
                <button
                  type="button"
                  @click="showPreview = !showPreview"
                  class="text-sm text-brand-teal hover:underline cursor-pointer"
                >
                  {{ showPreview ? '編輯模式' : '預覽' }}
                </button>
              </div>
            </div>

            <p class="mb-2 text-xs text-gray-500">
              <template v-if="isHtml">
                HTML 模式：內容原樣寄出，不做任何轉換或過濾，請自行確保標籤完整（Email 排版請用 inline style）。
              </template>
              <template v-else>
                Markdown 模式：按一次 Enter 就是換行，空一行則是新段落。
              </template>
              切換格式只改變內容的解讀方式，不會自動轉換既有內容。
            </p>

            <!-- Variable insert buttons -->
            <div v-if="availableVariables.length > 0" class="flex flex-wrap gap-2 mb-2">
              <span class="text-xs text-gray-500 self-center">插入變數：</span>
              <button
                v-for="variable in availableVariables"
                :key="variable.key"
                type="button"
                @click="insertAtCursor(variable.key)"
                class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-brand-teal/10 text-brand-teal hover:bg-brand-teal/10 border border-brand-teal/30 cursor-pointer"
              >
                {{ variable.label }}
              </button>
            </div>

            <!-- Preview mode -->
            <iframe
              v-if="showPreview"
              :srcdoc="renderedPreview"
              sandbox
              title="郵件內容預覽"
              class="block w-full h-96 rounded-lg border border-gray-300 bg-white"
            />
            <!-- Edit mode -->
            <textarea
              v-else
              ref="bodyTextarea"
              v-model="form.body_md"
              rows="14"
              class="block w-full rounded-lg border-gray-300 px-4 py-3 text-sm shadow-sm font-mono focus:border-brand-teal focus:ring-brand-teal"
              :class="{ 'border-red-300': form.errors.body_md }"
              :placeholder="isHtml ? '貼上完整的 HTML 內容...' : '使用 Markdown 格式撰寫郵件內容...'"
            />
            <p v-if="form.errors.body_type" class="mt-1 text-sm text-red-600">{{ form.errors.body_type }}</p>
            <p v-if="form.errors.body_md" class="mt-1 text-sm text-red-600">{{ form.errors.body_md }}</p>
          </div>
        </div>

        <div class="flex items-center justify-end gap-3">
          <a
            href="/admin/email-templates"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
          >
            取消
          </a>
          <button
            type="submit"
            :disabled="form.processing"
            class="px-6 py-2 text-sm font-medium text-white bg-brand-teal border border-transparent rounded-lg hover:bg-brand-teal/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ form.processing ? '儲存中...' : '儲存' }}
          </button>
        </div>
      </form>
    </div>
</template>
