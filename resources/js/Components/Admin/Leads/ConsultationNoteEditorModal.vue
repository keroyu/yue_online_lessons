<script setup>
import { computed, ref, watch, onMounted, onUnmounted } from 'vue'

// The globally configured axios (CSRF token) — same pattern as the other admin modals.
const axios = window.axios

const props = defineProps({
  show: { type: Boolean, default: false },
  // Mutated in place on save so the row behind the modal stays in step; the
  // panel hands us the very object it renders.
  note: { type: Object, default: null },
  // 'summary' = 顧問自己看的內部判斷；'followup' = 要寄給客戶的那封信（011 US35）。
  mode: { type: String, default: 'summary' },
})

const emit = defineEmits(['close'])

/**
 * 兩個欄位差的只有這張表 —— 標題、綁哪個欄位、兩個端點與按鈕字樣（D135）。
 *
 * 複製一份元件比較快，但這支元件的實質不是中間那個 textarea，是圍著它的那一圈：
 * 背景關閉的 mousedown/mouseup 判定、中文輸入法的 Esc、未存變更攔截、scroll lock。
 * 那些每一條都是回報過的 bug 修出來的，複製等於把它們複製成兩份，而下一次修正
 * 只會修好其中一份。
 */
const MODES = {
  summary: {
    title: '客戶摘要',
    field: 'summary',
    generatedAt: 'summary_generated_at',
    editedAt: 'summary_edited_at',
    save: (id) => `/admin/consultation-notes/${id}/summary`,
    generate: (id) => `/admin/consultation-notes/${id}/regenerate-summary`,
    saveLabel: '儲存摘要',
    savingLabel: '儲存中…',
    savedFlash: '摘要已儲存',
    generateLabel: '重新產生摘要',
    generatingLabel: '產生中…',
    generatedFlash: '摘要已重新產生',
    confirmGenerate: '重新產生摘要會覆寫目前的內容（包含手動修改過的部分），確定嗎？',
    confirmClose: '摘要還沒儲存，關閉會失去這次的修改。確定關閉嗎？',
    emptyHint: '尚未產生摘要',
    noTranscriptHint: '尚無逐字稿，無法產生摘要',
    placeholder: '面談結束後由 AI 自動填入，也可以直接在這裡寫。',
  },
  followup: {
    title: '追銷 Email',
    field: 'followup_email',
    generatedAt: 'followup_email_generated_at',
    editedAt: 'followup_email_edited_at',
    save: (id) => `/admin/consultation-notes/${id}/followup-email`,
    generate: (id) => `/admin/consultation-notes/${id}/generate-followup-email`,
    saveLabel: '儲存追銷信',
    savingLabel: '儲存中…',
    savedFlash: '追銷信已儲存',
    generateLabel: '產生追銷信',
    generatingLabel: '產生中…（約 10–40 秒）',
    generatedFlash: '追銷信已產生',
    confirmGenerate: '產生追銷信會覆寫目前的內容（包含手動修改過的部分），確定嗎？',
    confirmClose: '追銷信還沒儲存，關閉會失去這次的修改。確定關閉嗎？',
    emptyHint: '尚未產生追銷信',
    noTranscriptHint: '尚無逐字稿，無法產生追銷信',
    placeholder: '按下「產生追銷信」由 AI 依面談內容判斷購買障礙後撰寫，也可以直接在這裡寫。',
  },
}

const config = computed(() => MODES[props.mode] ?? MODES.summary)

const draft = ref('')
const saving = ref(false)
const regenerating = ref(false)
const message = ref('')
const error = ref('')

watch(() => [props.show, props.note?.id, props.mode], ([show]) => {
  if (show && props.note) {
    draft.value = props.note[config.value.field] ?? ''
  }
  message.value = ''
  error.value = ''
}, { immediate: true })

const formatDate = (iso) => (iso ? new Date(iso).toLocaleString('zh-TW', { dateStyle: 'medium', timeStyle: 'short' }) : null)

const flash = (text) => {
  message.value = text
  error.value = ''
  setTimeout(() => (message.value = ''), 2500)
}

const save = async () => {
  const { field, editedAt } = config.value

  saving.value = true
  error.value = ''
  try {
    const { data } = await axios.patch(config.value.save(props.note.id), { [field]: draft.value })
    props.note[field] = draft.value
    props.note[editedAt] = data[editedAt]
    flash(config.value.savedFlash)
  } catch (e) {
    error.value = e.response?.data?.message || '儲存失敗'
  } finally {
    saving.value = false
  }
}

const regenerate = async () => {
  const { field, generatedAt, editedAt } = config.value

  if (!window.confirm(config.value.confirmGenerate)) {
    return
  }

  regenerating.value = true
  error.value = ''
  try {
    const { data } = await axios.post(config.value.generate(props.note.id))
    draft.value = data[field]
    props.note[field] = data[field]
    props.note[generatedAt] = data[generatedAt]
    props.note[editedAt] = null
    flash(config.value.generatedFlash)
  } catch (e) {
    error.value = e.response?.data?.message || '產生失敗'
  } finally {
    regenerating.value = false
  }
}

/**
 * Closing is guarded, because everything in this modal is unsaved until the
 * admin presses 儲存摘要 and the three ways out (ESC, backdrop, 關閉) all
 * discard it silently.
 */
const dirty = computed(() => draft.value !== (props.note?.[config.value.field] ?? ''))

const requestClose = () => {
  if (dirty.value && !window.confirm(config.value.confirmClose)) {
    return
  }

  emit('close')
}

// ESC to close + body scroll lock, matching ReferrerDetailModal.
const handleKeydown = (e) => {
  if (e.key !== 'Escape' || !props.show) return

  // 中文輸入法的 Esc 是「取消候選字」，不是「關掉這個視窗」。`isComposing`
  // 在組字期間為 true；keyCode 229 是 Safari 與部分 IME 仍會送出的舊訊號。
  if (e.isComposing || e.keyCode === 229) return

  requestClose()
}
watch(() => props.show, (v) => {
  document.body.style.overflow = v ? 'hidden' : ''
})
onMounted(() => document.addEventListener('keydown', handleKeydown))
onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
  document.body.style.overflow = ''
})

/**
 * 背景關閉：MUST 是「按下」與「放開」都落在背景上。
 *
 * 這是使用者回報「編輯到一半視窗自己關掉」的成因：在 textarea 裡拖曳選字、
 * 手指滑出面板才放開時，瀏覽器會把 click 派送到 mousedown 與 mouseup 的
 * 共同祖先 —— 也就是這個根節點 —— 於是 `e.target === e.currentTarget` 成立，
 * 一次單純的選字被判成「點了背景」。只看 click 的寫法無法分辨這兩件事。
 *
 * 順帶修好反方向：背景那一層是獨立的 `fixed` 節點且疊在上面，點它時
 * `e.target` 從來就不是根節點，所以先前點背景其實關不掉。
 */
const backdrop = ref(null)
const pressedOnBackdrop = ref(false)

const isBackdrop = (el, root) => el === root || el === backdrop.value

const handleBackdropMousedown = (e) => {
  pressedOnBackdrop.value = isBackdrop(e.target, e.currentTarget)
}

const handleBackdropClick = (e) => {
  const released = isBackdrop(e.target, e.currentTarget)
  const pressed = pressedOnBackdrop.value
  pressedOnBackdrop.value = false

  if (pressed && released) requestClose()
}

const btn = 'px-4 py-2 text-sm font-medium rounded-lg border transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed'
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100"
      leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0"
    >
      <div
        v-if="show && note"
        class="fixed inset-0 z-50 overflow-y-auto"
        @mousedown="handleBackdropMousedown"
        @click="handleBackdropClick"
      >
        <div ref="backdrop" class="fixed inset-0 bg-black/50" aria-hidden="true" />

        <div class="flex min-h-full items-center justify-center p-4">
          <div class="relative bg-white rounded-lg shadow-xl max-w-3xl w-full max-h-[88vh] flex flex-col" @click.stop>
            <!-- Header -->
            <div class="border-b border-gray-200 px-6 py-4 flex items-start justify-between gap-4 rounded-t-lg">
              <div class="min-w-0">
                <h2 class="text-lg font-bold text-gray-900">{{ config.title }}</h2>
                <p class="text-sm text-gray-500 truncate">
                  {{ formatDate(note.met_at) || '時間未定' }}
                  <template v-if="note.course"> · {{ note.course.name }}</template>
                  <template v-if="note.consultant"> · {{ note.consultant.nickname }}</template>
                </p>
              </div>
              <button
                type="button"
                class="text-gray-400 hover:text-gray-700 cursor-pointer transition-colors shrink-0"
                aria-label="關閉"
                @click="requestClose"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Body -->
            <div class="flex-1 overflow-y-auto px-6 py-4 space-y-2">
              <p class="text-xs text-gray-400">
                <template v-if="note[config.editedAt]">人工編輯於 {{ formatDate(note[config.editedAt]) }}</template>
                <template v-else-if="note[config.generatedAt]">AI 產生於 {{ formatDate(note[config.generatedAt]) }}</template>
                <template v-else>{{ config.emptyHint }}</template>
              </p>
              <textarea
                v-model="draft"
                rows="18"
                :placeholder="config.placeholder"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm leading-relaxed focus:ring-2 focus:ring-brand-teal/30 focus:border-brand-teal"
              />
            </div>

            <!-- Footer -->
            <div class="border-t border-gray-200 px-6 py-4 flex flex-wrap items-center gap-2 rounded-b-lg">
              <button
                type="button"
                :class="[btn, 'border-brand-teal bg-brand-teal text-white hover:bg-brand-teal/90']"
                :disabled="saving"
                @click="save"
              >
                {{ saving ? config.savingLabel : config.saveLabel }}
              </button>
              <button
                type="button"
                :class="[btn, 'border-gray-300 text-gray-700 hover:bg-gray-50']"
                :disabled="regenerating || !note.transcript_bytes"
                :title="note.transcript_bytes ? '' : config.noTranscriptHint"
                @click="regenerate"
              >
                {{ regenerating ? config.generatingLabel : config.generateLabel }}
              </button>
              <span v-if="message" class="text-xs text-green-600">{{ message }}</span>
              <span v-if="error" class="text-xs text-red-600">{{ error }}</span>
              <button
                type="button"
                :class="[btn, 'ml-auto border-transparent text-gray-500 hover:bg-gray-100']"
                @click="requestClose"
              >
                關閉
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
