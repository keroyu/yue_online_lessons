<script setup>
import { ref, watch, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { marked } from 'marked'

const props = defineProps({
  submissions: Object,
  courses: Array,
  lessons: Array,
  filters: Object,
  assignmentsMap: Array,
})

const page = usePage()
const flash = computed(() => page.props.flash)

const selectedCourseId = ref(props.filters.course_id ?? '')
const selectedLessonId = ref(props.filters.lesson_id ?? '')

watch(selectedCourseId, (val) => {
  selectedLessonId.value = ''
  router.get('/admin/homework', { course_id: val || undefined }, { preserveState: true, replace: true })
})

watch(selectedLessonId, (val) => {
  router.get('/admin/homework', {
    course_id: selectedCourseId.value || undefined,
    lesson_id: val || undefined,
  }, { preserveState: true, replace: true })
})

const renderMd = (md) => marked.parse(md || '')

// Assignment form
const showAssignmentForm = ref(null)
const assignmentForm = ref({ md_content: '', lesson_id: '' })
const previewMode = ref(false)

const lessonsWithoutAssignment = computed(() => {
  const assignedLessonIds = new Set(props.assignmentsMap.map(a => a.lesson_id))
  return props.lessons.filter(l => !assignedLessonIds.has(l.id))
})

// Unified lesson table: all lessons for selected course, with assignment status merged in
const lessonTableRows = computed(() => {
  if (!selectedCourseId.value || !props.lessons.length) return []
  const assignedMap = Object.fromEntries(props.assignmentsMap.map(a => [a.lesson_id, a]))
  return props.lessons.map((lesson, index) => ({
    ...lesson,
    ep: index + 1,
    assignment: assignedMap[lesson.id] ?? null,
  }))
})

const openCreateForm = (lessonId) => {
  showAssignmentForm.value = lessonId
  assignmentForm.value = { md_content: '', lesson_id: lessonId }
  previewMode.value = false
}

const submitAssignment = (lessonId) => {
  router.post(`/admin/lessons/${lessonId}/assignment`, assignmentForm.value, {
    only: ['assignmentsMap', 'flash'],
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      showAssignmentForm.value = null
      assignmentForm.value = { md_content: '', lesson_id: '' }
    },
  })
}

// Edit assignment
const editingAssignment = ref(null)
const editForm = ref({ md_content: '' })
const editPreview = ref(false)

const openEditForm = (assignment) => {
  editingAssignment.value = assignment.id
  editForm.value = { md_content: assignment.md_content }
  editPreview.value = false
}

const submitEdit = (assignmentId) => {
  router.put(`/admin/homework/${assignmentId}`, editForm.value, {
    only: ['assignmentsMap', 'flash'],
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => { editingAssignment.value = null },
  })
}

// Reply form
const replyForms = ref({})

const getReplyForm = (commentId) => {
  if (!replyForms.value[commentId]) {
    replyForms.value[commentId] = { content: '', show: false }
  }
  return replyForms.value[commentId]
}

const submitReply = (assignmentId, parentId) => {
  const form = replyForms.value[parentId]
  if (!form?.content.trim()) return

  router.post(`/admin/homework/${assignmentId}/comments`, {
    content: form.content,
    parent_id: parentId,
  }, {
    onSuccess: () => { form.content = ''; form.show = false },
  })
}

// Edit comment
const editingComment = ref(null)
const editCommentForm = ref({ content: '' })

const openEditComment = (comment) => {
  editingComment.value = comment.id
  editCommentForm.value = { content: comment.content }
}

const submitEditComment = (assignmentId, commentId) => {
  router.put(`/admin/homework/${assignmentId}/comments/${commentId}`, editCommentForm.value, {
    onSuccess: () => { editingComment.value = null },
  })
}

const deleteComment = (assignmentId, commentId) => {
  if (!confirm('確定刪除此留言？')) return
  router.delete(`/admin/homework/${assignmentId}/comments/${commentId}`)
}

const formatDate = (d) => d ? new Date(d).toLocaleString('zh-TW') : ''
</script>

<template>
  <AdminLayout>
    <div class="px-4 sm:px-6 lg:px-8 max-w-6xl mx-auto">
      <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">作業批改專區</h1>
      </div>

      <!-- Flash -->
      <div v-if="flash?.success" class="mb-4 p-3 bg-green-50 border border-green-200 rounded text-green-700 text-sm">
        {{ flash.success }}
      </div>
      <div v-if="flash?.error" class="mb-4 p-3 bg-red-50 border border-red-200 rounded text-red-700 text-sm">
        {{ flash.error }}
      </div>

      <!-- Assignment Management Section -->
      <div class="mb-8 bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-4 py-3 border-b border-gray-200 flex items-center gap-3">
          <h2 class="font-semibold text-gray-700">作業題目管理</h2>
          <div class="flex items-center gap-2 ml-auto">
            <label class="text-sm text-gray-500">篩選課程：</label>
            <select v-model="selectedCourseId" class="text-sm border border-gray-300 rounded-md px-2 py-1.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
              <option value="">請選擇課程</option>
              <option v-for="c in courses" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>
        </div>

        <!-- No course selected -->
        <div v-if="!selectedCourseId" class="px-6 py-10 text-center text-sm text-gray-400">
          請先選擇課程，以管理各小節的作業題目
        </div>

        <!-- Lesson table -->
        <div v-else-if="lessonTableRows.length">
          <table class="w-full text-sm">
            <thead>
              <tr class="bg-gray-50 border-b border-gray-200 text-xs text-gray-500 uppercase tracking-wide">
                <th class="px-4 py-2.5 text-center w-16">EP</th>
                <th class="px-4 py-2.5 text-left">小節標題</th>
                <th class="px-4 py-2.5 text-center w-28">已完成學員</th>
                <th class="px-4 py-2.5 text-right w-40">操作</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <template v-for="row in lessonTableRows" :key="row.id">
                <!-- Main row -->
                <tr class="hover:bg-gray-50 transition-colors">
                  <td class="px-4 py-3 text-center">
                    <span class="inline-flex items-center justify-center w-8 h-6 rounded bg-gray-100 text-gray-600 text-xs font-mono font-medium">
                      {{ row.ep }}
                    </span>
                  </td>
                  <td class="px-4 py-3">
                    <span class="font-medium text-gray-800">{{ row.title }}</span>
                  </td>
                  <td class="px-4 py-3 text-center">
                    <span
                      v-if="row.assignment"
                      class="inline-flex items-center gap-1.5 text-sm font-semibold"
                      :class="row.assignment.completions_count > 0 ? 'text-indigo-700' : 'text-gray-400'"
                    >
                      <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                      </svg>
                      {{ row.assignment.completions_count }}
                    </span>
                    <span v-else class="text-xs text-gray-400">—</span>
                  </td>
                  <td class="px-4 py-3 text-right">
                    <!-- Has assignment -->
                    <div v-if="row.assignment" class="flex items-center justify-end gap-2">
                      <button
                        class="text-xs text-indigo-600 hover:text-indigo-800 hover:underline"
                        @click="openEditForm(row.assignment)"
                      >編輯</button>
                      <span class="text-gray-300">|</span>
                      <button
                        v-if="row.assignment.is_published"
                        class="text-xs text-orange-500 hover:text-orange-700 hover:underline"
                        @click="router.post(`/admin/homework/${row.assignment.id}/unpublish`, {}, { only: ['assignmentsMap', 'flash'], preserveState: true, preserveScroll: true })"
                      >下架</button>
                      <button
                        v-else
                        class="text-xs text-green-600 hover:text-green-800 hover:underline"
                        @click="router.post(`/admin/homework/${row.assignment.id}/publish`, {}, { only: ['assignmentsMap', 'flash'], preserveState: true, preserveScroll: true })"
                      >上架</button>
                    </div>
                    <!-- No assignment -->
                    <button
                      v-else
                      class="inline-flex items-center gap-1 text-xs font-medium text-indigo-600 border border-indigo-200 bg-indigo-50 hover:bg-indigo-100 px-2.5 py-1 rounded-md transition-colors"
                      @click="openCreateForm(row.id)"
                    >
                      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                      </svg>
                      新增題目
                    </button>
                  </td>
                </tr>

                <!-- Inline edit form -->
                <tr v-if="editingAssignment === row.assignment?.id" :key="`edit-${row.id}`">
                  <td colspan="4" class="px-4 pb-4 pt-0 bg-indigo-50/40">
                    <div class="border border-indigo-200 rounded-lg p-4 bg-white shadow-sm">
                      <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-semibold text-gray-800">編輯題目 — {{ row.title }}</p>
                        <div class="flex gap-1 bg-gray-100 rounded-md p-0.5">
                          <button
                            class="text-xs px-3 py-1 rounded"
                            :class="!editPreview ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500'"
                            @click="editPreview = false"
                          >編輯</button>
                          <button
                            class="text-xs px-3 py-1 rounded"
                            :class="editPreview ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500'"
                            @click="editPreview = true"
                          >預覽</button>
                        </div>
                      </div>
                      <textarea
                        v-if="!editPreview"
                        v-model="editForm.md_content"
                        rows="8"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Markdown 格式..."
                      />
                      <div v-else class="prose prose-sm max-w-none border border-gray-200 rounded-md p-4 bg-gray-50 min-h-32" v-html="renderMd(editForm.md_content)" />
                      <div class="mt-3 flex gap-2">
                        <button class="text-sm bg-indigo-600 text-white px-4 py-1.5 rounded-md hover:bg-indigo-700 font-medium" @click="submitEdit(row.assignment.id)">儲存</button>
                        <button class="text-sm text-gray-500 px-3 py-1.5 rounded-md hover:bg-gray-100" @click="editingAssignment = null">取消</button>
                      </div>
                    </div>
                  </td>
                </tr>

                <!-- Inline create form -->
                <tr v-if="showAssignmentForm === row.id" :key="`create-${row.id}`">
                  <td colspan="4" class="px-4 pb-4 pt-0 bg-indigo-50/40">
                    <div class="border border-indigo-200 rounded-lg p-4 bg-white shadow-sm">
                      <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-semibold text-gray-800">新增題目 — {{ row.title }}</p>
                        <div class="flex gap-1 bg-gray-100 rounded-md p-0.5">
                          <button
                            class="text-xs px-3 py-1 rounded"
                            :class="!previewMode ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500'"
                            @click="previewMode = false"
                          >編輯</button>
                          <button
                            class="text-xs px-3 py-1 rounded"
                            :class="previewMode ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500'"
                            @click="previewMode = true"
                          >預覽</button>
                        </div>
                      </div>
                      <textarea
                        v-if="!previewMode"
                        v-model="assignmentForm.md_content"
                        rows="8"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        placeholder="Markdown 格式..."
                      />
                      <div v-else class="prose prose-sm max-w-none border border-gray-200 rounded-md p-4 bg-gray-50 min-h-32" v-html="renderMd(assignmentForm.md_content)" />
                      <div class="mt-3 flex gap-2">
                        <button class="text-sm bg-indigo-600 text-white px-4 py-1.5 rounded-md hover:bg-indigo-700 font-medium" @click="submitAssignment(row.id)">建立題目</button>
                        <button class="text-sm text-gray-500 px-3 py-1.5 rounded-md hover:bg-gray-100" @click="showAssignmentForm = null">取消</button>
                      </div>
                    </div>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>

        <div v-else class="px-6 py-8 text-center text-sm text-gray-400">
          此課程目前沒有小節
        </div>
      </div>

      <!-- Submissions List -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
          <h2 class="font-semibold text-gray-700">學員提交列表</h2>
          <div class="flex gap-2">
            <select v-model="selectedCourseId" class="text-sm border border-gray-300 rounded px-2 py-1">
              <option value="">全部課程</option>
              <option v-for="c in courses" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
            <select v-if="selectedCourseId && lessons.length" v-model="selectedLessonId" class="text-sm border border-gray-300 rounded px-2 py-1">
              <option value="">全部小節</option>
              <option v-for="l in lessons" :key="l.id" :value="l.id">{{ l.title }}</option>
            </select>
          </div>
        </div>

        <div v-if="submissions.data.length === 0" class="p-8 text-center text-gray-500 text-sm">
          目前沒有學員提交記錄
        </div>

        <div v-else class="divide-y divide-gray-200">
          <div v-for="sub in submissions.data" :key="sub.id" class="p-4">
            <!-- Header -->
            <div class="flex items-start justify-between mb-3">
              <div>
                <span class="text-sm font-medium text-gray-900">{{ sub.user.nickname }}</span>
                <span class="text-xs text-gray-400 ml-2">{{ sub.user.email }}</span>
                <div class="text-xs text-gray-500 mt-0.5">
                  {{ sub.assignment.lesson.course.name }} › {{ sub.assignment.lesson.title }}
                </div>
              </div>
              <div class="flex items-center gap-2 text-xs text-gray-400">
                <span>{{ formatDate(sub.created_at) }}</span>
                <span v-if="sub.is_edited" class="bg-gray-100 px-1.5 py-0.5 rounded">已編輯</span>
                <!-- Mark complete -->
                <span v-if="sub.completion" class="text-green-600 font-medium">✓ 已完成 {{ formatDate(sub.completion.created_at) }}</span>
                <button
                  v-else
                  class="text-xs bg-green-50 border border-green-200 text-green-700 px-2 py-1 rounded hover:bg-green-100"
                  @click="router.post(`/admin/homework/${sub.assignment.id}/completions/${sub.user.id}`)"
                >標記已完成</button>
              </div>
            </div>

            <!-- Submission content -->
            <div v-if="editingComment !== sub.id">
              <div class="prose prose-sm max-w-none bg-gray-50 border border-gray-100 rounded p-3" v-html="renderMd(sub.content)" />
              <div class="mt-1 flex gap-2">
                <button class="text-xs text-indigo-600 hover:underline" @click="openEditComment(sub)">編輯</button>
                <button class="text-xs text-red-500 hover:underline" @click="deleteComment(sub.assignment.id, sub.id)">刪除</button>
              </div>
            </div>
            <div v-else class="mt-1">
              <textarea v-model="editCommentForm.content" rows="4" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" />
              <div class="mt-1 flex gap-2">
                <button class="text-sm bg-indigo-600 text-white px-3 py-1.5 rounded hover:bg-indigo-700" @click="submitEditComment(sub.assignment.id, sub.id)">儲存</button>
                <button class="text-sm text-gray-500 hover:underline" @click="editingComment = null">取消</button>
              </div>
            </div>

            <!-- Replies -->
            <div v-if="sub.replies.length" class="mt-3 space-y-2 pl-4 border-l-2 border-gray-200">
              <div v-for="reply in sub.replies" :key="reply.id" class="text-sm">
                <div class="flex items-center gap-1 text-xs text-gray-500 mb-1">
                  <span class="font-medium">{{ reply.user.nickname }}</span>
                  <span v-if="reply.user.is_admin" class="bg-indigo-100 text-indigo-600 px-1 rounded">管理員</span>
                  <span v-if="reply.is_edited">· 已編輯</span>
                  <span class="ml-auto">{{ formatDate(reply.created_at) }}</span>
                </div>
                <div v-if="editingComment !== reply.id">
                  <div class="text-gray-700">{{ reply.content }}</div>
                  <div class="mt-0.5 flex gap-2">
                    <button class="text-xs text-indigo-600 hover:underline" @click="openEditComment(reply)">編輯</button>
                    <button class="text-xs text-red-500 hover:underline" @click="deleteComment(sub.assignment.id, reply.id)">刪除</button>
                  </div>
                </div>
                <div v-else>
                  <textarea v-model="editCommentForm.content" rows="2" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" />
                  <div class="mt-1 flex gap-2">
                    <button class="text-sm bg-indigo-600 text-white px-2 py-1 rounded hover:bg-indigo-700" @click="submitEditComment(sub.assignment.id, reply.id)">儲存</button>
                    <button class="text-sm text-gray-500 hover:underline" @click="editingComment = null">取消</button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Reply form -->
            <div class="mt-3">
              <button
                v-if="!getReplyForm(sub.id).show"
                class="text-xs text-indigo-600 hover:underline"
                @click="getReplyForm(sub.id).show = true"
              >回覆批改</button>
              <div v-else class="mt-1">
                <textarea
                  v-model="getReplyForm(sub.id).content"
                  rows="3"
                  class="w-full border border-gray-300 rounded px-3 py-2 text-sm"
                  placeholder="批改內容..."
                />
                <div class="mt-1 flex gap-2">
                  <button
                    class="text-sm bg-indigo-600 text-white px-3 py-1.5 rounded hover:bg-indigo-700"
                    @click="submitReply(sub.assignment.id, sub.id)"
                  >送出回覆</button>
                  <button class="text-sm text-gray-500 hover:underline" @click="getReplyForm(sub.id).show = false">取消</button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="submissions.last_page > 1" class="px-4 py-3 border-t border-gray-200 flex items-center justify-between text-sm">
          <span class="text-gray-500">共 {{ submissions.total }} 筆</span>
          <div class="flex gap-1">
            <button
              v-for="page in submissions.last_page"
              :key="page"
              class="px-3 py-1 rounded"
              :class="page === submissions.current_page ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
              @click="router.get('/admin/homework', { ...filters, page }, { preserveState: true })"
            >{{ page }}</button>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
