<script setup>
import { ref } from 'vue'
import axios from 'axios'
import ConsultationSummaryModal from '@/Components/Admin/Leads/ConsultationSummaryModal.vue'

/**
 * Every consultation this customer has had (011 US23 / FR-116).
 *
 * The list is keyed on email server-side, so a repeat customer's earlier
 * sessions show up here even though they belong to a different lead.
 *
 * One line per session, two links (FR-120). The summary opens in a modal
 * because it is the part anyone actually reads; the transcript only downloads,
 * because a wall of dialogue inside an already-expanded table row was making
 * the sessions above and below it impossible to scan.
 */
const props = defineProps({
  notes: { type: Array, default: () => [] },
})

const openNote = ref(null)
const deleting = ref(null)
const error = ref('')

const formatDate = (iso) => {
  if (!iso) return null
  return new Date(iso).toLocaleString('zh-TW', { dateStyle: 'medium', timeStyle: 'short' })
}

const formatSize = (bytes) => {
  if (!bytes) return ''
  return bytes < 1024 ? `${bytes} B` : `${Math.round(bytes / 1024)} KB`
}

/**
 * Delete a whole session (FR-118).
 *
 * The warning escalates with what is actually at stake: an empty record costs
 * nothing to lose, but a proofread transcript cannot be re-fetched once Zoom's
 * cloud recording expires, so that case spells out what disappears.
 */
const destroy = async (note) => {
  const when = formatDate(note.met_at) || '這場'
  const hasContent = !!(note.summary || note.transcript_bytes)

  const warning = hasContent
    ? `確定刪除 ${when} 的面談紀錄？\n\n逐字稿與摘要會一併刪除且無法復原。Zoom 雲端錄影過期後就再也抓不回來了。`
    : `確定刪除 ${when} 的面談紀錄？\n\n這是一場空紀錄（沒有逐字稿與摘要）。`

  if (!window.confirm(warning)) {
    return
  }

  deleting.value = note.id
  error.value = ''
  try {
    await axios.delete(`/admin/consultation-notes/${note.id}`)
    const index = props.notes.findIndex((n) => n.id === note.id)
    if (index !== -1) props.notes.splice(index, 1)
  } catch (e) {
    error.value = e.response?.data?.message || '刪除失敗'
  } finally {
    deleting.value = null
  }
}

const link = 'text-xs font-medium cursor-pointer transition-colors'
</script>

<template>
  <div class="border-t border-gray-200 pt-4 space-y-2">
    <h4 class="text-xs font-semibold text-gray-500">面談紀錄（{{ notes.length }} 場）</h4>

    <p v-if="!notes.length" class="text-sm text-gray-400">
      —　這位客戶還沒有面談紀錄。預約確認後會自動建立，逐字稿於會議結束、Zoom 產出錄影後補上。
    </p>

    <div
      v-for="note in notes"
      :key="note.id"
      class="rounded-lg border border-gray-200 bg-white px-4 py-3 flex flex-wrap items-center gap-x-4 gap-y-2"
    >
      <!-- 場次 -->
      <div class="min-w-0 flex flex-wrap items-baseline gap-x-2">
        <span class="text-sm font-semibold text-gray-900">{{ formatDate(note.met_at) || '時間未定' }}</span>
        <span v-if="note.course" class="text-xs text-gray-500">{{ note.course.name }}</span>
        <span v-if="note.consultant" class="text-xs text-gray-500">· {{ note.consultant.nickname }}</span>
      </div>

      <!-- 兩個連結 -->
      <div class="flex items-center gap-4 ml-auto">
        <button
          type="button"
          :class="[link, note.summary ? 'text-brand-teal hover:underline' : 'text-gray-400 hover:text-gray-700']"
          @click="openNote = note"
        >
          {{ note.summary ? '查看摘要' : '撰寫摘要' }}
        </button>

        <a
          v-if="note.transcript_bytes"
          :href="`/admin/consultation-notes/${note.id}/transcript.txt`"
          :class="[link, 'text-brand-teal hover:underline']"
          :title="`下載逐字稿（${formatSize(note.transcript_bytes)}）`"
        >
          下載逐字稿 <span class="text-gray-400 font-normal">{{ formatSize(note.transcript_bytes) }}</span>
        </a>
        <span v-else class="text-xs text-gray-400">尚無逐字稿</span>

        <button
          type="button"
          class="text-xs text-gray-300 hover:text-red-600 cursor-pointer transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          title="刪除這場面談紀錄"
          :disabled="deleting === note.id"
          @click="destroy(note)"
        >
          {{ deleting === note.id ? '刪除中…' : '刪除' }}
        </button>
      </div>
    </div>

    <p v-if="error" class="text-xs text-red-600">{{ error }}</p>

    <ConsultationSummaryModal :show="!!openNote" :note="openNote" @close="openNote = null" />
  </div>
</template>
