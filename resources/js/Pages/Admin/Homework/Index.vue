<script setup>
import { ref, watch, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { marked } from 'marked'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  submissions: Object,
  courses: Array,
  lessons: Array,
  filters: Object,
  assignmentsMap: Array,
})

const page = usePage()

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

const renderMd = (md) => marked.parse(md || '', { breaks: true })

// Assignment form
const showAssignmentForm = ref(null)
const assignmentForm = ref({ md_content: '', lesson_id: '' })
const previewMode = ref(false)

const lessonsWithoutAssignment = computed(() => {
  const assignedLessonIds = new Set(props.assignmentsMap.map(a => a.lesson_id))
  return props.lessons.filter(l => !assignedLessonIds.has(l.id))
})

// Group lessons by chapter, with assignment status merged in
const lessonGroups = computed(() => {
  if (!selectedCourseId.value || !props.lessons.length) return []
  const assignedMap = Object.fromEntries(props.assignmentsMap.map(a => [a.lesson_id, a]))

  const groups = []
  let currentChapterId = undefined

  for (const lesson of props.lessons) {
    if (lesson.chapter_id !== currentChapterId) {
      currentChapterId = lesson.chapter_id
      groups.push({ chapterId: lesson.chapter_id, chapterTitle: lesson.chapter_title, lessons: [] })
    }
    groups.at(-1).lessons.push({ ...lesson, assignment: assignedMap[lesson.id] ?? null })
  }
  return groups
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

// Submission expand/collapse
const expandedSubmissions = ref({})

const toggleSubmission = (id) => {
  if (expandedSubmissions.value[id]) {
    editingComment.value = null
    expandedSubmissions.value[id] = false
  } else {
    expandedSubmissions.value[id] = true
  }
}

watch(() => props.submissions.current_page, () => {
  expandedSubmissions.value = {}
})

// Reply panel
const replyPanel = ref({ open: false, submission: null })
const replyContent = ref('')
const replyTextarea = ref(null)

const openReplyPanel = (sub) => {
  replyContent.value = ''
  replyPanel.value = { open: true, submission: sub }
  nextTick(() => replyTextarea.value?.focus())
}

const closeReplyPanel = () => {
  replyPanel.value = { open: false, submission: null }
  replyContent.value = ''
}

const submitReply = () => {
  const sub = replyPanel.value.submission
  if (!sub || !replyContent.value.trim()) return

  router.post(`/admin/homework/${sub.assignment.id}/comments`, {
    content: replyContent.value,
    parent_id: sub.id,
  }, {
    only: ['submissions', 'flash'],
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      replyContent.value = ''
      replyPanel.value = { open: false, submission: null }
    },
  })
}

const handleKeydown = (e) => {
  if (e.key === 'Escape' && replyPanel.value.open) closeReplyPanel()
}
onMounted(() => window.addEventListener('keydown', handleKeydown))
onUnmounted(() => window.removeEventListener('keydown', handleKeydown))

// Edit comment
const editingComment = ref(null)
const editCommentForm = ref({ content: '' })

const openEditComment = (comment) => {
  editingComment.value = comment.id
  editCommentForm.value = { content: comment.content }
}

const submitEditComment = (assignmentId, commentId) => {
  router.put(`/admin/homework/${assignmentId}/comments/${commentId}`, editCommentForm.value, {
    only: ['submissions', 'flash'],
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => { editingComment.value = null },
  })
}

const deleteComment = (assignmentId, commentId) => {
  if (!confirm('確定刪除此留言？')) return
  router.delete(`/admin/homework/${assignmentId}/comments/${commentId}`, {
    only: ['submissions', 'flash'],
    preserveState: true,
    preserveScroll: true,
  })
}

const formatDate = (d) => d ? new Date(d).toLocaleString('zh-TW') : ''
</script>

<template>
  <div
    class="transition-[padding-right] duration-[250ms] ease-out"
    :class="{ 'pr-96': replyPanel.open }"
  >
  <div class="px-4 sm:px-6 lg:px-8 max-w-6xl mx-auto">
      <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">作業批改專區</h1>
      </div>

      <!-- Submissions List -->
      <div class="mb-8 bg-white rounded-lg shadow-sm border border-gray-200">
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
          <div v-for="sub in submissions.data" :key="sub.id">

            <!-- Header row（永遠可見，點擊展開/折疊） -->
            <div
              class="flex items-center px-4 py-3 cursor-pointer select-none hover:bg-gray-50 transition-colors"
              @click="toggleSubmission(sub.id)"
            >
              <!-- 左：學員 + 麵包屑 -->
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                  <span class="text-sm font-medium text-gray-900">{{ sub.user.nickname }}</span>
                  <span class="text-xs text-gray-400">{{ sub.user.email }}</span>
                  <span v-if="sub.is_edited" class="text-xs bg-gray-100 px-1.5 py-0.5 rounded">已編輯</span>
                  <span v-if="sub.replies?.length > 0" class="text-xs bg-blue-50 text-blue-600 border border-blue-200 px-1.5 py-0.5 rounded">已回覆</span>
                </div>
                <div class="text-xs text-gray-500 mt-0.5">
                  {{ sub.assignment.lesson.course.name }} › {{ sub.assignment.lesson.title }}
                </div>
              </div>

              <!-- 中：提交時間 -->
              <span class="text-xs text-gray-400 mx-4 shrink-0">{{ formatDate(sub.created_at) }}</span>

              <!-- 右：完成狀態（@click.stop 防冒泡） -->
              <div class="flex items-center gap-2 shrink-0" @click.stop>
                <a
                  :href="`/member/classroom/${sub.assignment.lesson.course.id}?lesson_id=${sub.assignment.lesson.id}&preview_user_id=${sub.user.id}`"
                  target="_blank"
                  class="text-xs text-gray-500 border border-gray-200 bg-white px-2 py-1 rounded hover:bg-gray-50 hover:border-gray-300 transition-colors"
                >預覽</a>
                <span v-if="sub.completion" class="text-xs text-green-600 font-medium">
                  ✓ 已完成 {{ formatDate(sub.completion.created_at) }}
                </span>
                <button
                  v-else
                  class="text-xs bg-green-50 border border-green-200 text-green-700 px-2 py-1 rounded hover:bg-green-100 hover:border-green-300 transition-colors"
                  @click="router.post(`/admin/homework/${sub.assignment.id}/completions/${sub.user.id}`, {}, { only: ['submissions', 'assignmentsMap', 'flash'], preserveState: true, preserveScroll: true })"
                >標記已完成</button>
              </div>

              <!-- 展開箭頭 -->
              <svg
                class="w-4 h-4 text-gray-400 ml-3 shrink-0 transition-transform duration-200"
                :class="expandedSubmissions[sub.id] ? 'rotate-180' : ''"
                fill="none" viewBox="0 0 24 24" stroke="currentColor"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </div>

            <!-- 展開內容 -->
            <div v-show="expandedSubmissions[sub.id]" class="border-t border-gray-100 px-4 py-4 bg-gray-50/40">

              <!-- Submission content -->
              <div v-if="editingComment !== sub.id">
                <div class="assignment-content bg-gray-50 border border-gray-100 rounded p-3" v-html="renderMd(sub.content)" />
                <div class="mt-1 flex gap-1">
                  <button class="text-xs text-indigo-600 px-2 py-0.5 rounded hover:bg-indigo-50 transition-colors" @click="openEditComment(sub)">編輯</button>
                  <button class="text-xs text-red-500 px-2 py-0.5 rounded hover:bg-red-50 transition-colors" @click="deleteComment(sub.assignment.id, sub.id)">刪除</button>
                </div>
              </div>
              <div v-else class="mt-1">
                <textarea v-model="editCommentForm.content" rows="4" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" />
                <div class="mt-1 flex gap-2">
                  <button class="text-sm bg-indigo-600 text-white px-3 py-1.5 rounded hover:bg-indigo-700 transition-colors" @click="submitEditComment(sub.assignment.id, sub.id)">儲存</button>
                  <button class="text-sm text-gray-500 px-2 py-1 rounded hover:bg-gray-100 transition-colors" @click="editingComment = null">取消</button>
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
                    <div class="assignment-content" v-html="renderMd(reply.content)" />
                    <div class="mt-0.5 flex gap-1">
                      <button class="text-xs text-indigo-600 px-2 py-0.5 rounded hover:bg-indigo-50 transition-colors" @click="openEditComment(reply)">編輯</button>
                      <button class="text-xs text-red-500 px-2 py-0.5 rounded hover:bg-red-50 transition-colors" @click="deleteComment(sub.assignment.id, reply.id)">刪除</button>
                    </div>
                  </div>
                  <div v-else>
                    <textarea v-model="editCommentForm.content" rows="2" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" />
                    <div class="mt-1 flex gap-2">
                      <button class="text-sm bg-indigo-600 text-white px-2 py-1 rounded hover:bg-indigo-700 transition-colors" @click="submitEditComment(sub.assignment.id, reply.id)">儲存</button>
                      <button class="text-sm text-gray-500 px-2 py-0.5 rounded hover:bg-gray-100 transition-colors" @click="editingComment = null">取消</button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- 回覆批改 → 開啟右側面板 -->
              <div class="mt-3">
                <button
                  class="text-xs text-indigo-600 border border-indigo-200 bg-indigo-50 px-2.5 py-1 rounded hover:bg-indigo-100 hover:border-indigo-300 transition-colors"
                  @click="openReplyPanel(sub)"
                >回覆批改</button>
              </div>

            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="submissions.last_page > 1" class="px-4 py-3 border-t border-gray-200 flex items-center justify-between text-sm">
          <span class="text-gray-500">第 {{ submissions.current_page }} / {{ submissions.last_page }} 頁，共 {{ submissions.total }} 筆</span>
          <div class="flex gap-1">
            <button
              :disabled="submissions.current_page === 1"
              class="px-3 py-1 rounded bg-gray-100 text-gray-700 hover:bg-gray-200 disabled:opacity-40 disabled:cursor-not-allowed"
              @click="router.get('/admin/homework', { ...filters, page: submissions.current_page - 1 }, { only: ['submissions'], preserveState: true, preserveScroll: true })"
            >‹</button>
            <button
              v-for="page in submissions.last_page"
              :key="page"
              class="px-3 py-1 rounded"
              :class="page === submissions.current_page ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
              @click="router.get('/admin/homework', { ...filters, page }, { only: ['submissions'], preserveState: true, preserveScroll: true })"
            >{{ page }}</button>
            <button
              :disabled="submissions.current_page === submissions.last_page"
              class="px-3 py-1 rounded bg-gray-100 text-gray-700 hover:bg-gray-200 disabled:opacity-40 disabled:cursor-not-allowed"
              @click="router.get('/admin/homework', { ...filters, page: submissions.current_page + 1 }, { only: ['submissions'], preserveState: true, preserveScroll: true })"
            >›</button>
          </div>
        </div>
      </div>

      <!-- Assignment Management Section -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200">
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

        <!-- Lesson table grouped by chapter -->
        <div v-else-if="lessonGroups.length">
          <table class="w-full text-sm">
            <thead>
              <tr class="bg-gray-50 border-b border-gray-200 text-xs text-gray-500 uppercase tracking-wide">
                <th class="px-4 py-2.5 text-left">小節標題</th>
                <th class="px-4 py-2.5 text-center w-28">已完成學員</th>
                <th class="px-4 py-2.5 text-right w-40">操作</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="group in lessonGroups" :key="group.chapterId ?? 'standalone'">
                <!-- Chapter header row -->
                <tr class="bg-gray-50 border-y border-gray-200">
                  <td colspan="3" class="px-4 py-2 text-xs font-semibold text-gray-500 tracking-wide">
                    {{ group.chapterTitle ?? '獨立小節' }}
                  </td>
                </tr>
                <!-- Lesson rows -->
                <template v-for="row in group.lessons" :key="row.id">
                  <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3 pl-7">
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
                      <div v-if="row.assignment" class="flex items-center justify-end gap-2">
                        <button
                          class="text-xs text-indigo-600 border border-indigo-200 bg-indigo-50 px-2.5 py-1 rounded hover:bg-indigo-100 hover:border-indigo-300 transition-colors"
                          @click="openEditForm(row.assignment)"
                        >編輯</button>
                        <button
                          v-if="row.assignment.is_published"
                          class="text-xs text-orange-600 border border-orange-200 bg-orange-50 px-2.5 py-1 rounded hover:bg-orange-100 hover:border-orange-300 transition-colors"
                          @click="router.post(`/admin/homework/${row.assignment.id}/unpublish`, {}, { only: ['assignmentsMap', 'flash'], preserveState: true, preserveScroll: true })"
                        >下架</button>
                        <button
                          v-else
                          class="text-xs text-green-700 border border-green-200 bg-green-50 px-2.5 py-1 rounded hover:bg-green-100 hover:border-green-300 transition-colors"
                          @click="router.post(`/admin/homework/${row.assignment.id}/publish`, {}, { only: ['assignmentsMap', 'flash'], preserveState: true, preserveScroll: true })"
                        >上架</button>
                      </div>
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
                    <td colspan="3" class="px-4 pb-4 pt-0 bg-indigo-50/40">
                      <div class="border border-indigo-200 rounded-lg p-4 bg-white shadow-sm">
                        <div class="flex items-center justify-between mb-3">
                          <p class="text-sm font-semibold text-gray-800">編輯題目 — {{ row.title }}</p>
                          <div class="flex gap-1 bg-gray-100 rounded-md p-0.5">
                            <button class="text-xs px-3 py-1 rounded" :class="!editPreview ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500'" @click="editPreview = false">編輯</button>
                            <button class="text-xs px-3 py-1 rounded" :class="editPreview ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500'" @click="editPreview = true">預覽</button>
                          </div>
                        </div>
                        <textarea v-if="!editPreview" v-model="editForm.md_content" rows="8" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Markdown 格式..." />
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
                    <td colspan="3" class="px-4 pb-4 pt-0 bg-indigo-50/40">
                      <div class="border border-indigo-200 rounded-lg p-4 bg-white shadow-sm">
                        <div class="flex items-center justify-between mb-3">
                          <p class="text-sm font-semibold text-gray-800">新增題目 — {{ row.title }}</p>
                          <div class="flex gap-1 bg-gray-100 rounded-md p-0.5">
                            <button class="text-xs px-3 py-1 rounded" :class="!previewMode ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500'" @click="previewMode = false">編輯</button>
                            <button class="text-xs px-3 py-1 rounded" :class="previewMode ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500'" @click="previewMode = true">預覽</button>
                          </div>
                        </div>
                        <textarea v-if="!previewMode" v-model="assignmentForm.md_content" rows="8" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" placeholder="Markdown 格式..." />
                        <div v-else class="prose prose-sm max-w-none border border-gray-200 rounded-md p-4 bg-gray-50 min-h-32" v-html="renderMd(assignmentForm.md_content)" />
                        <div class="mt-3 flex gap-2">
                          <button class="text-sm bg-indigo-600 text-white px-4 py-1.5 rounded-md hover:bg-indigo-700 font-medium" @click="submitAssignment(row.id)">建立題目</button>
                          <button class="text-sm text-gray-500 px-3 py-1.5 rounded-md hover:bg-gray-100" @click="showAssignmentForm = null">取消</button>
                        </div>
              </div>
                    </td>
                  </tr>
                </template>
              </template>
            </tbody>
          </table>
        </div>

        <div v-else class="px-6 py-8 text-center text-sm text-gray-400">
          此課程目前沒有小節
        </div>
      </div>
  </div>
  </div>

  <!-- Overlay -->
  <Transition name="fade">
    <div
      v-if="replyPanel.open"
      class="fixed inset-0 bg-black/30 z-40"
      @click="closeReplyPanel"
    />
  </Transition>

  <!-- Reply Panel -->
  <Transition name="slide-in">
    <div
      v-if="replyPanel.open"
      class="fixed right-0 top-0 h-screen w-96 bg-white shadow-2xl z-50 flex flex-col"
    >
      <!-- Panel header -->
      <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 bg-gray-50 shrink-0">
        <div>
          <p class="text-sm font-semibold text-gray-900">回覆批改</p>
          <p class="text-xs text-gray-500 mt-0.5">
            {{ replyPanel.submission?.user.nickname }}
            · {{ replyPanel.submission?.assignment.lesson.title }}
          </p>
        </div>
        <button
          class="p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded transition-colors"
          @click="closeReplyPanel"
        >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Textarea -->
      <div class="flex-1 overflow-y-auto px-4 py-3">
        <label class="text-xs font-medium text-gray-700 mb-1 block">批改內容（支援 Markdown）</label>
        <textarea
          ref="replyTextarea"
          v-model="replyContent"
          rows="12"
          class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 resize-none"
          placeholder="輸入批改內容..."
        />
      </div>

      <!-- Actions（固定底部） -->
      <div class="shrink-0 px-4 py-3 border-t border-gray-200 bg-gray-50">
        <button
          class="w-full bg-indigo-600 text-white py-2 rounded text-sm font-medium hover:bg-indigo-700 transition-colors"
          @click="submitReply"
        >送出回覆</button>
        <button
          class="mt-2 w-full text-gray-500 text-sm py-1.5 hover:bg-gray-100 rounded transition-colors"
          @click="closeReplyPanel"
        >取消</button>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
button { cursor: pointer; }

.slide-in-enter-active,
.slide-in-leave-active {
  transition: transform 0.25s ease-out;
}
.slide-in-enter-from,
.slide-in-leave-to {
  transform: translateX(100%);
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
