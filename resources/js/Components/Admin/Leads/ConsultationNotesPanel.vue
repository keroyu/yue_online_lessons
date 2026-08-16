<script setup>
import { reactive, ref } from 'vue'
import axios from 'axios'

/**
 * Every consultation this customer has had (011 US23 / FR-116).
 *
 * The list is keyed on email server-side, so a repeat customer's earlier
 * sessions show up here even though they belong to a different lead.
 */
const props = defineProps({
  notes: { type: Array, default: () => [] },
})

// Per-note UI state, created lazily so a customer with ten sessions does not
// pay for ten open editors.
const state = reactive({})

const stateFor = (note) => {
  if (!state[note.id]) {
    state[note.id] = {
      summary: note.summary ?? '',
      transcript: note.transcript ?? '',
      transcriptOpen: false,
      savingSummary: false,
      savingTranscript: false,
      regenerating: false,
      message: '',
      error: '',
    }
  }
  return state[note.id]
}

const copied = ref(null)

const formatDate = (iso) => {
  if (!iso) return null
  return new Date(iso).toLocaleString('zh-TW', { dateStyle: 'medium', timeStyle: 'short' })
}

const flash = (s, message) => {
  s.message = message
  s.error = ''
  setTimeout(() => (s.message = ''), 2500)
}

const saveSummary = async (note) => {
  const s = stateFor(note)
  s.savingSummary = true
  s.error = ''
  try {
    const { data } = await axios.patch(`/admin/consultation-notes/${note.id}/summary`, { summary: s.summary })
    note.summary = s.summary
    note.summary_edited_at = data.summary_edited_at
    flash(s, '摘要已儲存')
  } catch (e) {
    s.error = e.response?.data?.message || '儲存失敗'
  } finally {
    s.savingSummary = false
  }
}

const saveTranscript = async (note) => {
  const s = stateFor(note)
  s.savingTranscript = true
  s.error = ''
  try {
    const { data } = await axios.patch(`/admin/consultation-notes/${note.id}/transcript`, { transcript: s.transcript })
    note.transcript = s.transcript
    note.transcript_edited_at = data.transcript_edited_at
    flash(s, '逐字稿已儲存')
  } catch (e) {
    s.error = e.response?.data?.message || '儲存失敗'
  } finally {
    s.savingTranscript = false
  }
}

const regenerate = async (note) => {
  const s = stateFor(note)

  if (!window.confirm('重新產生摘要會覆寫目前的內容（包含手動修改過的部分），確定嗎？')) {
    return
  }

  s.regenerating = true
  s.error = ''
  try {
    const { data } = await axios.post(`/admin/consultation-notes/${note.id}/regenerate-summary`)
    s.summary = data.summary
    note.summary = data.summary
    note.summary_generated_at = data.summary_generated_at
    note.summary_edited_at = null
    flash(s, '摘要已重新產生')
  } catch (e) {
    s.error = e.response?.data?.message || '產生失敗'
  } finally {
    s.regenerating = false
  }
}

const copyTranscript = async (note) => {
  await navigator.clipboard.writeText(stateFor(note).transcript || '')
  copied.value = note.id
  setTimeout(() => (copied.value = null), 2000)
}

const btn = 'px-3 py-1.5 text-xs font-medium rounded-md border transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed'
</script>

<template>
  <div class="border-t border-gray-200 pt-4 space-y-4">
    <h4 class="text-xs font-semibold text-gray-500">面談紀錄（{{ notes.length }} 場）</h4>

    <p v-if="!notes.length" class="text-sm text-gray-400">
      —　這位客戶還沒有面談紀錄。預約確認後會自動建立，逐字稿於會議結束、Zoom 產出錄影後補上。
    </p>

    <div
      v-for="note in notes"
      :key="note.id"
      class="rounded-lg border border-gray-200 bg-white p-4 space-y-3"
    >
      <!-- 場次抬頭 -->
      <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1">
        <div class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
          <span class="text-sm font-semibold text-gray-900">{{ formatDate(note.met_at) || '時間未定' }}</span>
          <span v-if="note.course" class="text-xs text-gray-500">{{ note.course.name }}</span>
          <span v-if="note.consultant" class="text-xs text-gray-500">· {{ note.consultant.nickname }}</span>
        </div>
        <span v-if="!note.transcript" class="text-xs text-gray-400">尚無逐字稿</span>
      </div>

      <!-- 摘要 -->
      <div>
        <div class="flex items-center justify-between mb-1">
          <label class="text-xs font-medium text-gray-500">客戶摘要</label>
          <span class="text-xs text-gray-400">
            <template v-if="note.summary_edited_at">人工編輯於 {{ formatDate(note.summary_edited_at) }}</template>
            <template v-else-if="note.summary_generated_at">AI 產生於 {{ formatDate(note.summary_generated_at) }}</template>
          </span>
        </div>
        <textarea
          v-model="stateFor(note).summary"
          rows="8"
          placeholder="面談結束後由 AI 自動填入，也可以直接在這裡寫。"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-brand-teal/30 focus:border-brand-teal"
        />
        <div class="mt-2 flex flex-wrap items-center gap-2">
          <button
            type="button"
            :class="[btn, 'border-brand-teal bg-brand-teal text-white hover:bg-brand-teal/90']"
            :disabled="stateFor(note).savingSummary"
            @click="saveSummary(note)"
          >
            {{ stateFor(note).savingSummary ? '儲存中…' : '儲存摘要' }}
          </button>
          <button
            type="button"
            :class="[btn, 'border-gray-300 text-gray-700 hover:bg-gray-50']"
            :disabled="stateFor(note).regenerating || !note.transcript"
            :title="note.transcript ? '' : '尚無逐字稿，無法產生摘要'"
            @click="regenerate(note)"
          >
            {{ stateFor(note).regenerating ? '產生中…' : '重新產生摘要' }}
          </button>
          <span v-if="stateFor(note).message" class="text-xs text-green-600">{{ stateFor(note).message }}</span>
          <span v-if="stateFor(note).error" class="text-xs text-red-600">{{ stateFor(note).error }}</span>
        </div>
      </div>

      <!-- 逐字稿 -->
      <div class="border-t border-gray-100 pt-3">
        <button
          type="button"
          class="flex items-center gap-1.5 text-xs font-medium text-gray-600 hover:text-gray-900 cursor-pointer transition-colors"
          @click="stateFor(note).transcriptOpen = !stateFor(note).transcriptOpen"
        >
          <svg
            class="w-3.5 h-3.5 transition-transform"
            :class="stateFor(note).transcriptOpen ? 'rotate-90' : ''"
            fill="none" stroke="currentColor" viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
          面談逐字稿
          <span v-if="note.transcript_edited_at" class="text-gray-400 font-normal">（已人工修訂）</span>
        </button>

        <div v-if="stateFor(note).transcriptOpen" class="mt-2 space-y-2">
          <p class="text-xs text-gray-500">
            講者已匿名化為「顧問」與「客戶」，原始姓名不會存進資料庫。修正辨識錯誤後可按「重新產生摘要」得到更準的摘要。
          </p>
          <textarea
            v-model="stateFor(note).transcript"
            rows="16"
            placeholder="會議結束、Zoom 產出錄影後自動填入。"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg font-mono text-xs leading-relaxed focus:ring-2 focus:ring-brand-teal/30 focus:border-brand-teal"
          />
          <div class="flex flex-wrap items-center gap-2">
            <button
              type="button"
              :class="[btn, 'border-brand-teal bg-brand-teal text-white hover:bg-brand-teal/90']"
              :disabled="stateFor(note).savingTranscript"
              @click="saveTranscript(note)"
            >
              {{ stateFor(note).savingTranscript ? '儲存中…' : '儲存逐字稿' }}
            </button>
            <button
              type="button"
              :class="[btn, 'border-gray-300 text-gray-700 hover:bg-gray-50']"
              @click="copyTranscript(note)"
            >
              {{ copied === note.id ? '已複製' : '複製全文' }}
            </button>
            <span v-if="note.transcript_fetched_at" class="text-xs text-gray-400">
              取回於 {{ formatDate(note.transcript_fetched_at) }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
