<script setup>
import { ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { marked } from 'marked'

const renderer = new marked.Renderer()
renderer.link = ({ href, title, text }) => {
  const titleAttr = title ? ` title="${title}"` : ''
  return `<a href="${href}"${titleAttr} target="_blank" rel="noopener noreferrer">${text}</a>`
}

const renderMd = (md) => marked.parse(md || '', { breaks: true, renderer })

const props = defineProps({
  assignment: { type: Object, required: true },
  comments:   { type: Array,  default: () => [] },
  courseId:   { type: Number, required: true },
  lessonId:   { type: Number, required: true },
})

const page      = usePage()
const authUser  = () => page.props.auth?.user
const isAdmin   = (user) => user?.is_admin
const isOwn     = (item) => authUser()?.id === item.user?.id

const formatDate = (d) => d
  ? new Date(d).toLocaleString('zh-TW', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' })
  : ''

const baseUrl = () =>
  `/member/classroom/${props.courseId}/assignment/${props.assignment.id}/comments`

const partialOpts = {
  only: ['currentLesson'],
  preserveState: true,
  preserveScroll: true,
}

// ── Expand / collapse ─────────────────────────────────────────────────────
const showThread = ref(false)

// ── Submit (first or follow-up) ───────────────────────────────────────────
const inputContent = ref('')
const submitting   = ref(false)

const placeholder = `請輸入作業內容，支援 Markdown 格式

### 三級標題（建議從 ### 開始，# 後面記得加空格）
#### 四級標題

**粗體文字**　*斜體*　~~刪除線~~

- 清單項目一（- 後面記得加空格）
- 清單項目二

1. 有序清單一（數字和點號後面記得加空格）
2. 有序清單二

> 引用或補充說明

[超連結文字](https://example.com)`

const followUpPlaceholder = `追加補充或提問，支援 Markdown 格式

**粗體**　*斜體*　### 三級標題（# 後面記得加空格）

- 列點（- 後面記得加空格）　> 引用　[連結](https://example.com)`

const handleSubmit = () => {
  if (!inputContent.value.trim() || submitting.value) return
  submitting.value = true
  router.post(baseUrl(), { content: inputContent.value, parent_id: null }, {
    ...partialOpts,
    onSuccess: () => { inputContent.value = '' },
    onFinish:  () => { submitting.value = false },
  })
}

// ── Edit / Delete ─────────────────────────────────────────────────────────
const editingId   = ref(null)
const editContent = ref('')

const openEdit = (item) => { editingId.value = item.id; editContent.value = item.content }

const submitEdit = (commentId) => {
  router.put(`${baseUrl()}/${commentId}`, { content: editContent.value }, {
    ...partialOpts,
    onSuccess: () => { editingId.value = null },
  })
}

const deleteItem = (commentId, hasReplies) => {
  if (hasReplies && !confirm('刪除後老師批改也將消失，確認刪除？')) return
  router.delete(`${baseUrl()}/${commentId}`, partialOpts)
}
</script>

<template>
  <div class="mt-8 mb-[10px]">
    <div class="rounded-lg bg-white border border-indigo-100 overflow-hidden">

      <!-- ── Question ──────────────────────────────────────────────── -->
      <div class="px-6 pt-6 pb-5">
        <div class="flex items-start justify-between gap-4 mb-3">
          <div class="assignment-content flex-1" v-html="renderMd(assignment.md_content)" />
          <span
            v-if="assignment.is_completed"
            class="flex-shrink-0 flex items-center gap-1 text-xs font-medium text-green-700 bg-green-50 border border-green-200 px-2.5 py-1 rounded-full"
          >
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
            已完成
          </span>
        </div>
        <div class="flex justify-end">
          <button
            class="inline-flex items-center gap-1.5 text-xs font-medium text-[#3F83A3] border border-[#3F83A3]/30 bg-white px-3 py-1.5 rounded-full hover:bg-[#3F83A3]/10 transition-colors"
            @click="showThread = !showThread"
          >
            <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="showThread ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
            {{ showThread ? '收合回答' : '展開回答' }}
          </button>
        </div>
      </div>

      <!-- ── Thread ────────────────────────────────────────────────── -->
      <div v-if="comments.length > 0 && showThread" class="bg-indigo-50 px-6 pt-[10px] pb-4 space-y-3">
        <template v-for="comment in comments" :key="comment.id">

          <!-- Student top-level submission -->
          <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="px-4 pt-3 pb-1 flex items-center justify-between">
              <span class="text-xs font-semibold text-gray-700">{{ comment.user?.nickname }}</span>
              <div class="flex items-center gap-2 text-xs text-gray-400">
                <span v-if="comment.is_edited" class="bg-gray-100 px-1.5 py-0.5 rounded">已編輯</span>
                <span>{{ formatDate(comment.created_at) }}</span>
              </div>
            </div>
            <div v-if="editingId !== comment.id" class="px-4 pb-4">
              <div class="assignment-content text-gray-800" v-html="renderMd(comment.content)" />
              <div v-if="isOwn(comment)" class="mt-2 flex gap-1">
                <button class="text-xs text-gray-400 px-2 py-0.5 rounded hover:bg-gray-100 transition-colors" @click="openEdit(comment)">編輯</button>
                <button class="text-xs text-red-400 px-2 py-0.5 rounded hover:bg-red-50 transition-colors" @click="deleteItem(comment.id, comment.replies?.length > 0)">刪除</button>
              </div>
            </div>
            <div v-else class="px-4 pb-4">
              <textarea v-model="editContent" rows="4" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-200 resize-none" />
              <div class="mt-2 flex gap-2 justify-end">
                <button class="text-sm text-gray-500 px-3 py-1.5 rounded-lg hover:bg-gray-100 transition-colors" @click="editingId = null">取消</button>
                <button class="text-sm bg-gray-700 text-white px-4 py-1.5 rounded-lg hover:bg-gray-800 transition-colors" @click="submitEdit(comment.id)">儲存</button>
              </div>
            </div>
          </div>

          <!-- Replies -->
          <template v-for="reply in comment.replies" :key="reply.id">

            <!-- Instructor reply (teal) -->
            <div v-if="isAdmin(reply.user)" class="rounded-lg overflow-hidden ml-[100px] border" style="background-color:rgba(63,131,163,0.08);border-color:rgba(63,131,163,0.2)">
              <div class="px-4 pt-3 pb-1 flex items-center justify-between">
                <div class="flex items-center gap-1.5">
                  <span class="text-xs font-semibold text-brand-teal">{{ reply.user?.nickname }}</span>
                  <span class="text-xs bg-brand-teal/20 text-brand-teal px-1.5 py-0.5 rounded">講師</span>
                  <span v-if="reply.is_edited" class="text-xs text-gray-400 bg-white/60 px-1.5 py-0.5 rounded">已編輯</span>
                </div>
                <span class="text-xs text-gray-400">{{ formatDate(reply.created_at) }}</span>
              </div>
              <div class="assignment-content px-4 pb-4" v-html="renderMd(reply.content)" />
            </div>

            <!-- Student follow-up (white) -->
            <div v-else class="bg-white rounded-lg shadow-sm overflow-hidden">
              <div class="px-4 pt-3 pb-1 flex items-center justify-between">
                <span class="text-xs font-semibold text-gray-700">{{ reply.user?.nickname }}</span>
                <div class="flex items-center gap-2 text-xs text-gray-400">
                  <span v-if="reply.is_edited" class="bg-gray-100 px-1.5 py-0.5 rounded">已編輯</span>
                  <span>{{ formatDate(reply.created_at) }}</span>
                </div>
              </div>
              <div v-if="editingId !== reply.id" class="px-4 pb-4">
                <div class="assignment-content text-sm text-gray-800" v-html="renderMd(reply.content)" />
                <div v-if="isOwn(reply)" class="mt-2 flex gap-1">
                  <button class="text-xs text-gray-400 px-2 py-0.5 rounded hover:bg-gray-100 transition-colors" @click="openEdit(reply)">編輯</button>
                  <button class="text-xs text-red-400 px-2 py-0.5 rounded hover:bg-red-50 transition-colors" @click="deleteItem(reply.id, false)">刪除</button>
                </div>
              </div>
              <div v-else class="px-4 pb-4">
                <textarea v-model="editContent" rows="3" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-200 resize-none" />
                <div class="mt-2 flex gap-2 justify-end">
                  <button class="text-sm text-gray-500 px-3 py-1.5 rounded-lg hover:bg-gray-100 transition-colors" @click="editingId = null">取消</button>
                  <button class="text-sm bg-gray-700 text-white px-4 py-1.5 rounded-lg hover:bg-gray-800 transition-colors" @click="submitEdit(reply.id)">儲存</button>
                </div>
              </div>
            </div>

          </template>
        </template>
      </div>

      <!-- ── Input ─────────────────────────────────────────────────── -->
      <div v-show="showThread" class="bg-indigo-50 px-6 py-5 border-t border-indigo-100">
        <textarea
          v-model="inputContent"
          :rows="comments.length === 0 ? 5 : 3"
          :placeholder="comments.length === 0 ? placeholder : followUpPlaceholder"
          class="w-full bg-white border border-indigo-100 px-4 py-3 text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-200 resize-none"
        />
        <div class="mt-3 flex justify-end">
          <button
            :disabled="!inputContent.trim() || submitting"
            class="px-6 py-2 bg-[#3F83A3] hover:bg-[#336d8a] disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-full transition-colors"
            @click="handleSubmit"
          >
            {{ submitting ? '送出中...' : '送出' }}
          </button>
        </div>
      </div>

    </div>
  </div>
</template>

<style scoped>
button { cursor: pointer; }
</style>
