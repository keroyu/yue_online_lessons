<script setup>
import { ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { marked } from 'marked'

const props = defineProps({
  comments: {
    type: Array,
    default: () => [],
  },
  assignmentId: {
    type: Number,
    required: true,
  },
  courseId: {
    type: Number,
    required: true,
  },
})

const page = usePage()
const authUser = () => page.props.auth?.user

const renderMd = (md) => marked.parse(md || '')

const formatDate = (d) => {
  if (!d) return ''
  return new Date(d).toLocaleString('zh-TW', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' })
}

// Reply form state
const replyForms = ref({})
const getReplyForm = (commentId) => {
  if (!replyForms.value[commentId]) {
    replyForms.value[commentId] = { content: '', show: false }
  }
  return replyForms.value[commentId]
}

const submitReply = (parentId) => {
  const form = replyForms.value[parentId]
  if (!form?.content.trim()) return

  router.post(`/member/classroom/${props.courseId}/assignment/${props.assignmentId}/comments`, {
    content: form.content,
    parent_id: parentId,
  }, {
    only: ['currentLesson'],
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => { form.content = ''; form.show = false },
  })
}

// Edit state
const editingId = ref(null)
const editForm = ref({ content: '' })

const openEdit = (comment) => {
  editingId.value = comment.id
  editForm.value = { content: comment.content }
}

const submitEdit = (commentId) => {
  router.put(`/member/classroom/${props.courseId}/assignment/${props.assignmentId}/comments/${commentId}`, editForm.value, {
    only: ['currentLesson'],
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => { editingId.value = null },
  })
}

const deleteComment = (commentId, hasReplies) => {
  if (hasReplies && !confirm('刪除後老師批改也將消失，確認刪除？')) return
  router.delete(`/member/classroom/${props.courseId}/assignment/${props.assignmentId}/comments/${commentId}`, {
    only: ['currentLesson'],
    preserveState: true,
    preserveScroll: true,
  })
}

const isOwn = (comment) => authUser()?.id === comment.user?.id
</script>

<template>
  <div class="space-y-4">
    <div v-if="comments.length === 0" class="text-sm text-gray-500 italic">
      尚未提交作業
    </div>

    <div v-for="comment in comments" :key="comment.id" class="border border-gray-200 rounded-lg p-4 bg-white">
      <!-- Top-level comment header -->
      <div class="flex items-center justify-between mb-2 text-xs text-gray-500">
        <div class="flex items-center gap-1.5">
          <span class="font-medium text-gray-800">{{ comment.user?.nickname }}</span>
          <span v-if="comment.user?.is_admin" class="bg-indigo-100 text-indigo-600 px-1.5 py-0.5 rounded">管理員</span>
          <span v-if="comment.is_edited" class="bg-gray-100 px-1.5 py-0.5 rounded">已編輯</span>
        </div>
        <span>{{ formatDate(comment.created_at) }}</span>
      </div>

      <!-- Content / Edit form -->
      <div v-if="editingId !== comment.id">
        <div class="prose prose-sm max-w-none text-gray-800" v-html="renderMd(comment.content)" />
        <div v-if="isOwn(comment)" class="mt-2 flex gap-3">
          <button class="text-xs text-indigo-600 hover:underline" @click="openEdit(comment)">編輯</button>
          <button
            class="text-xs text-red-500 hover:underline"
            @click="deleteComment(comment.id, comment.replies?.length > 0)"
          >刪除</button>
        </div>
      </div>
      <div v-else>
        <textarea
          v-model="editForm.content"
          rows="4"
          class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
        />
        <div class="mt-2 flex gap-2">
          <button class="text-sm bg-indigo-600 text-white px-3 py-1.5 rounded hover:bg-indigo-700" @click="submitEdit(comment.id)">儲存</button>
          <button class="text-sm text-gray-500 hover:underline" @click="editingId = null">取消</button>
        </div>
      </div>

      <!-- Replies -->
      <div v-if="comment.replies?.length" class="mt-3 space-y-2 pl-4 border-l-2 border-indigo-100">
        <div v-for="reply in comment.replies" :key="reply.id" class="text-sm">
          <div class="flex items-center gap-1.5 text-xs text-gray-500 mb-1">
            <span class="font-medium text-gray-800">{{ reply.user?.nickname }}</span>
            <span v-if="reply.user?.is_admin" class="bg-indigo-100 text-indigo-600 px-1.5 py-0.5 rounded">管理員</span>
            <span v-if="reply.is_edited" class="bg-gray-100 px-1.5 py-0.5 rounded">已編輯</span>
            <span class="ml-auto">{{ formatDate(reply.created_at) }}</span>
          </div>
          <div v-if="editingId !== reply.id">
            <div class="text-gray-700 whitespace-pre-wrap">{{ reply.content }}</div>
            <div v-if="isOwn(reply)" class="mt-1 flex gap-3">
              <button class="text-xs text-indigo-600 hover:underline" @click="openEdit(reply)">編輯</button>
              <button class="text-xs text-red-500 hover:underline" @click="deleteComment(reply.id, false)">刪除</button>
            </div>
          </div>
          <div v-else>
            <textarea v-model="editForm.content" rows="2" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" />
            <div class="mt-1 flex gap-2">
              <button class="text-sm bg-indigo-600 text-white px-2 py-1 rounded hover:bg-indigo-700" @click="submitEdit(reply.id)">儲存</button>
              <button class="text-sm text-gray-500 hover:underline" @click="editingId = null">取消</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Reply form (top-level only) -->
      <div class="mt-3">
        <button
          v-if="!getReplyForm(comment.id).show"
          class="text-xs text-indigo-600 hover:underline"
          @click="getReplyForm(comment.id).show = true"
        >追加補充</button>
        <div v-else class="mt-1">
          <textarea
            v-model="getReplyForm(comment.id).content"
            rows="3"
            class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
            placeholder="追加補充內容..."
          />
          <div class="mt-1 flex gap-2">
            <button
              class="text-sm bg-indigo-600 text-white px-3 py-1.5 rounded hover:bg-indigo-700"
              @click="submitReply(comment.id)"
            >送出</button>
            <button class="text-sm text-gray-500 hover:underline" @click="getReplyForm(comment.id).show = false">取消</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
