<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { marked } from 'marked'
import CommentThread from '@/Components/Classroom/CommentThread.vue'

const renderMd = (md) => marked.parse(md || '', { breaks: true })

const props = defineProps({
  assignment: {
    type: Object,
    required: true,
  },
  comments: {
    type: Array,
    default: () => [],
  },
  courseId: {
    type: Number,
    required: true,
  },
  lessonId: {
    type: Number,
    required: true,
  },
})

const submitContent = ref('')
const submitting = ref(false)

const markdownPlaceholder = `請在此輸入作業內容，支援 Markdown 格式，例如：

**粗體文字**　*斜體*　~~刪除線~~

- 無序清單項目一
- 無序清單項目二

1. 有序清單一
2. 有序清單二

> 引用文字或補充說明

[連結文字](https://example.com)`

const submitAssignment = () => {
  if (!submitContent.value.trim()) return
  submitting.value = true

  router.post(`/member/classroom/${props.courseId}/assignment/${props.assignment.id}/comments`, {
    content: submitContent.value,
    parent_id: null,
  }, {
    onSuccess: () => { submitContent.value = '' },
    onFinish: () => { submitting.value = false },
    preserveScroll: true,
  })
}
</script>

<template>
  <div class="mt-10">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-xl font-bold text-gray-900">本節作業</h2>
      <span
        v-if="assignment.is_completed"
        class="flex items-center gap-1 text-xs font-medium text-green-700 bg-green-50 border border-green-200 px-2 py-1 rounded-full"
      >
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
        </svg>
        已完成
      </span>
    </div>

    <!-- Assignment question -->
    <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 mb-6">
      <div class="assignment-content" v-html="renderMd(assignment.md_content)" />
    </div>

    <!-- Submit form (only if no top-level submission yet) -->
    <div v-if="comments.length === 0" class="mb-6">
      <textarea
        v-model="submitContent"
        rows="6"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
        :placeholder="markdownPlaceholder"
      />
      <div class="mt-2 flex justify-end">
        <button
          :disabled="submitting || !submitContent.trim()"
          class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
          @click="submitAssignment"
        >
          {{ submitting ? '送出中...' : '送出' }}
        </button>
      </div>
    </div>

    <!-- Comments / submissions -->
    <div v-if="comments.length > 0" class="mb-4">
      <h4 class="text-sm font-medium text-gray-700 mb-3">我的提交記錄</h4>
      <CommentThread
        :comments="comments"
        :assignment-id="assignment.id"
        :course-id="courseId"
      />
    </div>
  </div>
</template>
