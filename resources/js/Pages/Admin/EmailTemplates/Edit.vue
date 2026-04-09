<script setup>
import { useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { marked } from 'marked'
import AdminLayout from '@/Layouts/AdminLayout.vue'

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
  body_md: props.template.body_md,
})

const bodyTextarea = ref(null)
const showPreview = ref(false)

const renderedPreview = computed(() => marked(form.body_md || '', { breaks: true }))

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
  <AdminLayout>
    <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-4xl mx-auto">
      <div class="mb-6">
        <a href="/admin/email-templates" class="text-sm text-indigo-600 hover:underline">← 返回模板列表</a>
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
              class="mt-1 block w-full rounded-lg border-gray-300 px-4 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
              class="mt-1 block w-full rounded-lg border-gray-300 px-4 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              :class="{ 'border-red-300': form.errors.subject }"
            />
            <p v-if="form.errors.subject" class="mt-1 text-sm text-red-600">{{ form.errors.subject }}</p>
          </div>

          <!-- Body -->
          <div>
            <div class="flex items-center justify-between mb-2">
              <label class="block text-sm font-semibold text-gray-900">郵件內容（Markdown）</label>
              <button
                type="button"
                @click="showPreview = !showPreview"
                class="text-sm text-indigo-600 hover:underline"
              >
                {{ showPreview ? '編輯模式' : '預覽' }}
              </button>
            </div>

            <!-- Variable insert buttons -->
            <div v-if="availableVariables.length > 0" class="flex flex-wrap gap-2 mb-2">
              <span class="text-xs text-gray-500 self-center">插入變數：</span>
              <button
                v-for="variable in availableVariables"
                :key="variable.key"
                type="button"
                @click="insertAtCursor(variable.key)"
                class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 cursor-pointer"
              >
                {{ variable.label }}
              </button>
            </div>

            <!-- Preview mode -->
            <div
              v-if="showPreview"
              class="min-h-48 rounded-lg border border-gray-300 px-4 py-3 bg-gray-50 prose prose-sm max-w-none text-sm"
              v-html="renderedPreview"
            />
            <!-- Edit mode -->
            <textarea
              v-else
              ref="bodyTextarea"
              v-model="form.body_md"
              rows="14"
              class="block w-full rounded-lg border-gray-300 px-4 py-3 text-sm shadow-sm font-mono focus:border-indigo-500 focus:ring-indigo-500"
              :class="{ 'border-red-300': form.errors.body_md }"
              placeholder="使用 Markdown 格式撰寫郵件內容..."
            />
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
            class="px-6 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ form.processing ? '儲存中...' : '儲存' }}
          </button>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>
