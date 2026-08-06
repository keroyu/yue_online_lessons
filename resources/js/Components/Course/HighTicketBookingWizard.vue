<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import EmailReviewNotice from '@/Components/EmailReviewNotice.vue'
import { useDelayedConfirm } from '@/composables/useDelayedConfirm'

const props = defineProps({
  course: { type: Object, required: true },
  // Previous answers — either for the logged-in owner of the lead, or for
  // whoever holds the resume token from a 「通知新時段」 mail (`draft.resume`).
  draft: { type: Object, default: null },
})

const page = usePage()

// 1 資料 → 2 承諾 → 3 時段 → 4 確認. Step 1 reveals the questionnaire inline
// once there is an email to attach it to, so the first thing a visitor sees is
// still just two fields (011 US9).
const STEPS = [
  { n: 1, label: '資料' },
  { n: 2, label: '承諾' },
  { n: 3, label: '時段' },
  { n: 4, label: '確認' },
]

const COMMITMENTS = [
  '我已有初步想法，願意投入時間學習並持續執行，而不是隨意了解。',
  '我願意接受務實建議，調整原本的想法與做法。',
  '如果確認方向適合，我願意採取下一步，而不是只停留在想像階段。',
]

// A waitlisted applicant coming back from the slot-available mail already
// answered everything except the one thing that did not exist yet (FR-042):
// drop them on the picker rather than walking them through it all again.
const resuming = props.draft?.resume === true

const step = ref(resuming ? 3 : 1)
const questionnaireOpen = ref(resuming)

const form = reactive({
  name: props.draft?.name || page.props.auth?.user?.real_name || page.props.auth?.user?.nickname || '',
  email: props.draft?.email || page.props.auth?.user?.email || '',
  phone: props.draft?.phone || '',
  occupation: props.draft?.occupation || '',
  bottleneck: props.draft?.bottleneck || '',
  expertise: props.draft?.expertise || '',
  social_url: props.draft?.social_url || '',
  code: props.draft?.code || '',
  slot_starts_at: '',
})

// Already accepted when the application was first submitted (`commitments_accepted_at`);
// re-ticking them would read as doubting an answer we already have on file.
const commitments = ref(COMMITMENTS.map(() => resuming))
const errors = ref({})

onMounted(() => {
  if (resuming) fetchSlots()
})

// ---- step 1 ----------------------------------------------------------------

const identityFilled = computed(() => form.name.trim() !== '' && form.email.trim() !== '')

const questionnaireFilled = computed(() =>
  form.phone.trim() !== ''
  && form.occupation.trim() !== ''
  && form.bottleneck.trim() !== ''
  && form.expertise.trim() !== ''
)

function openQuestionnaire() {
  if (!identityFilled.value) return
  questionnaireOpen.value = true
}

// Mirrors the server's `url:http,https` (FR-027). Not a regex: the URL
// constructor is the same parser the browser uses, and it rejects both the
// scheme-less "instagram.com/me" and the dangerous "javascript:" in one go.
// The server still validates — this only saves a round-trip that would
// otherwise dump the visitor back on step 1 after they pressed 送出.
const SOCIAL_URL_HINT = '社群網址須為完整網址，並以 http:// 或 https:// 開頭'

const socialUrlInvalid = computed(() => {
  const raw = form.social_url.trim()

  if (raw === '') return false // optional field

  try {
    return !['http:', 'https:'].includes(new URL(raw).protocol)
  } catch {
    return true
  }
})

// ---- step 2 ----------------------------------------------------------------

const allCommitted = computed(() => commitments.value.every(Boolean))

// ---- step 3 ----------------------------------------------------------------

const slotGroups = ref([])
const slotMinutes = ref(30)
const codeApplied = ref(false)
const codeChecked = ref(false)
const slotsLoading = ref(false)

async function fetchSlots() {
  slotsLoading.value = true
  try {
    const query = form.code.trim() ? `?code=${encodeURIComponent(form.code.trim())}` : ''
    const { data } = await window.axios.get(`/course/${props.course.id}/booking-slots${query}`)
    slotGroups.value = data.slots
    slotMinutes.value = data.minutes
    codeApplied.value = data.code_applied
    codeChecked.value = form.code.trim() !== ''

    // A longer session can invalidate an already-picked time.
    if (form.slot_starts_at && !availableValues.value.includes(form.slot_starts_at)) {
      form.slot_starts_at = ''
    }
  } catch {
    slotGroups.value = []
  } finally {
    slotsLoading.value = false
  }
}

const availableValues = computed(() =>
  slotGroups.value.flatMap(g => g.times.map(t => t.value))
)

const hasSlots = computed(() => availableValues.value.length > 0)

const selectedSlotLabel = computed(() => {
  for (const group of slotGroups.value) {
    const hit = group.times.find(t => t.value === form.slot_starts_at)
    if (hit) return `${group.date} ${hit.label}`
  }
  return ''
})

// ---- navigation ------------------------------------------------------------

async function goTo(n) {
  if (n > step.value && !canLeave(step.value)) return
  if (n === 3) await fetchSlots()
  step.value = n
}

function canLeave(n) {
  if (n === 1) return identityFilled.value && questionnaireFilled.value && !socialUrlInvalid.value
  if (n === 2) return allCommitted.value
  if (n === 3) return form.slot_starts_at !== ''
  return true
}

const maxReachable = computed(() => {
  if (!canLeave(1)) return 1
  if (!canLeave(2)) return 2
  if (!canLeave(3)) return 3
  return 4
})

function editFrom(n) {
  step.value = n
}

// ---- submit ----------------------------------------------------------------

const submitting = ref(false)
const submitted = ref(false)
const waitlisted = ref(false)
const mailSent = ref(true)
const holdExpiresAt = ref(null)
const confirmedSlotLabel = ref('')
const confirmedMinutes = ref(30)

/**
 * Show a 422's field errors and land on the step that owns the first offending
 * one. Returns false when the response carries no field errors, so the caller
 * can fall back to a generic message.
 */
function routeFieldErrors(e) {
  const fields = e.response?.data?.errors

  if (!fields) return false

  errors.value = fields
  const keys = Object.keys(fields)

  if (keys.some(k => ['name', 'email', 'phone', 'occupation', 'bottleneck', 'expertise', 'social_url'].includes(k))) {
    step.value = 1
    questionnaireOpen.value = true
  } else if (keys.includes('commitments')) {
    step.value = 2
  } else if (keys.includes('slot_starts_at')) {
    step.value = 3
  }

  return true
}

// ---- email double-check (011 FR-059) ---------------------------------------

// A mistyped address fails silently: the application succeeds, the slot is
// held, and the applicant simply never hears anything until the hold expires.
// So the first press does not submit — it puts the address in front of them
// and makes them wait before the button will fire. Shared with the drip claim
// form so both guards stay the same strength (010 D18).
const {
  confirming: emailConfirming,
  countdown: emailCountdown,
  start: startEmailReview,
  reset: resetEmailConfirm,
} = useDelayedConfirm()

// Leaving step 4 — or editing anything — starts the check over.
watch(step, resetEmailConfirm)
watch(() => form.email, resetEmailConfirm)

function requestSubmit() {
  if (socialUrlInvalid.value) return
  if (!startEmailReview()) return

  submit()
}

function editEmail() {
  resetEmailConfirm()
  step.value = 1
}

async function submit() {
  errors.value = {}
  submitting.value = true

  try {
    const { data } = await window.axios.post(`/course/${props.course.id}/book`, {
      name: form.name,
      email: form.email,
      phone: form.phone,
      occupation: form.occupation,
      bottleneck: form.bottleneck,
      expertise: form.expertise,
      social_url: form.social_url || null,
      commitments: commitments.value,
      slot_starts_at: form.slot_starts_at,
      code: form.code || null,
    })

    mailSent.value = data?.mail_sent !== false
    holdExpiresAt.value = data?.hold_expires_at ?? null
    confirmedSlotLabel.value = data?.slot_label ?? selectedSlotLabel.value
    confirmedMinutes.value = data?.minutes ?? slotMinutes.value
    submitted.value = true
    startCountdown()
  } catch (e) {
    const status = e.response?.status

    if (status === 409) {
      // Somebody took it while the form was open — refetch and send them back.
      errors.value = { slot: e.response?.data?.message || '該時段剛被預約，請重新選擇' }
      form.slot_starts_at = ''
      await fetchSlots()
      step.value = 3
    } else if (routeFieldErrors(e)) {
      // handled
    } else {
      errors.value = { submit: e.response?.data?.message || '送出失敗，請稍後再試。' }
    }
  } finally {
    submitting.value = false
  }
}

/** No slots exist yet — keep the application so the admin can notify later. */
async function submitWaitlist() {
  errors.value = {}
  submitting.value = true

  try {
    await window.axios.post(`/course/${props.course.id}/waitlist`, {
      name: form.name,
      email: form.email,
      phone: form.phone,
      occupation: form.occupation,
      bottleneck: form.bottleneck,
      expertise: form.expertise,
      social_url: form.social_url || null,
      commitments: commitments.value,
      code: form.code || null,
    })

    waitlisted.value = true
    submitted.value = true
  } catch (e) {
    // Field errors used to collapse into 「請稍後再試」 here — advice that can
    // never work, on a message that never said which field was wrong.
    if (!routeFieldErrors(e)) {
      errors.value = { slot: e.response?.data?.message || '送出失敗，請稍後再試。' }
    }
  } finally {
    submitting.value = false
  }
}

// ---- countdown -------------------------------------------------------------

const remaining = ref('')
let timer = null

function startCountdown() {
  if (!holdExpiresAt.value) return

  const tick = () => {
    const left = new Date(holdExpiresAt.value).getTime() - Date.now()

    if (left <= 0) {
      remaining.value = '已逾時'
      clearInterval(timer)
      return
    }

    const m = Math.floor(left / 60000)
    const s = Math.floor((left % 60000) / 1000)
    remaining.value = `${m}:${String(s).padStart(2, '0')}`
  }

  tick()
  timer = setInterval(tick, 1000)
}

onUnmounted(() => clearInterval(timer))

// Step 4's review rows. Out of the template because the invalid-URL row needs
// more than a label/value pair, and because every one of these lives on step 1
// — which is why the block gets a single 修改 rather than one button per row.
const reviewRows = computed(() => [
  { label: 'Email', value: form.email },
  { label: '暱稱', value: form.name },
  { label: '手機電話', value: form.phone },
  { label: '職業和從事時長', value: form.occupation },
  { label: '事業瓶頸', value: form.bottleneck },
  { label: '知識或能力專長', value: form.expertise },
  { label: '經營社群網址', value: form.social_url || '—', invalid: socialUrlInvalid.value, hint: SOCIAL_URL_HINT },
])

function firstError(key) {
  const e = errors.value[key]
  return Array.isArray(e) ? e[0] : e
}

const inputClass = 'block w-full rounded-lg border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-brand-teal focus:ring-brand-teal'
</script>

<template>
  <div class="bg-white rounded-xl border border-gray-200 p-5 sm:p-6 shadow-sm">
    <!-- ── Submitted: waiting for the emailed confirmation ── -->
    <div v-if="submitted" class="text-center py-4">
      <template v-if="waitlisted">
        <div class="mx-auto w-12 h-12 bg-brand-teal/10 rounded-full flex items-center justify-center mb-4">
          <svg class="w-6 h-6 text-brand-teal" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <p class="text-base font-bold text-gray-900">申請已收到</p>
        <p class="mt-2 text-sm text-gray-600 leading-relaxed">
          目前沒有開放的時段。新時段釋出時，我們會以 Email 通知你優先預約。
        </p>
        <p class="mt-4 text-sm text-gray-700">
          通知會寄到
          <span class="font-mono font-semibold text-gray-900 break-all">{{ form.email }}</span>
        </p>
        <p class="mt-1 text-xs text-gray-500">
          若這個地址不對，你不會收到通知 —— 請重新提出一次申請，用正確的 Email。
        </p>
      </template>

      <template v-else-if="mailSent">
        <div class="mx-auto w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center mb-4">
          <svg class="w-6 h-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
          </svg>
        </div>
        <p class="text-base font-bold text-gray-900">申請已送出，時段暫時保留中</p>
        <p class="mt-2 text-sm text-gray-600 leading-relaxed">
          請於 <strong class="text-amber-700">1 小時內</strong>收取 Email 並點擊信中的確認連結，才算完成預約。
        </p>

        <div class="mt-4 inline-block rounded-xl bg-amber-50 border border-amber-200 px-5 py-3 text-sm text-amber-900">
          <p class="font-semibold tabular-nums">{{ confirmedSlotLabel }}（{{ confirmedMinutes }} 分鐘）</p>
          <p v-if="remaining" class="mt-1 text-xs">
            保留剩餘時間：<span class="font-mono font-bold">{{ remaining }}</span>
          </p>
        </div>

        <!-- Spelled out so a typo is discoverable here rather than after the
             hold has quietly expired. -->
        <p class="mt-4 text-sm text-gray-700">
          確認信已寄到
          <span class="font-mono font-semibold text-gray-900 break-all">{{ form.email }}</span>
        </p>
        <p class="mt-1 text-xs text-gray-500">
          若這個地址不對，你不會收到信 —— 請重新提出一次申請，用正確的 Email。
        </p>

        <p class="mt-3 text-xs text-gray-500">
          沒收到信？請檢查垃圾郵件匣。逾時未確認，時段會自動釋出給其他人。
        </p>
      </template>

      <template v-else>
        <div class="mx-auto w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mb-4">
          <svg class="w-6 h-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <p class="text-base font-bold text-gray-900">申請已收到</p>
        <p class="mt-2 text-sm text-gray-600 leading-relaxed">
          但確認信寄送失敗，我們會主動與你聯絡安排時段。
        </p>
      </template>
    </div>

    <!-- ── The wizard ── -->
    <template v-else>
      <h3 class="text-base font-bold text-gray-900">預約 1v1 諮詢</h3>
      <p class="mt-1 text-xs text-gray-500">
        名額有限，我們會依申請內容判斷方向是否適合，請據實填寫。
      </p>

      <div v-if="resuming" class="mt-4 rounded-lg bg-brand-teal/5 border border-brand-teal/20 px-3 py-2.5 text-xs text-gray-700">
        歡迎回來，{{ form.name }}。你先前填寫的資料已帶入，直接選定時段即可；如需修改，可回到上方步驟。
      </div>

      <!-- Progress -->
      <div class="mt-5 flex items-center">
        <template v-for="(s, i) in STEPS" :key="s.n">
          <button
            type="button"
            :disabled="s.n > maxReachable"
            class="flex items-center gap-1.5 text-xs font-medium transition"
            :class="[
              s.n <= maxReachable ? 'cursor-pointer hover:opacity-70' : 'cursor-not-allowed opacity-40',
              s.n === step ? 'text-brand-teal' : 'text-gray-400',
            ]"
            @click="goTo(s.n)"
          >
            <span
              class="w-5 h-5 rounded-full flex items-center justify-center text-[11px] font-bold"
              :class="s.n === step ? 'bg-brand-teal text-white' : (s.n < step ? 'bg-brand-teal/15 text-brand-teal' : 'bg-gray-100 text-gray-400')"
            >{{ s.n }}</span>
            <span class="hidden sm:inline">{{ s.label }}</span>
          </button>
          <div v-if="i < STEPS.length - 1" class="flex-1 h-px mx-2" :class="s.n < step ? 'bg-brand-teal/30' : 'bg-gray-200'" />
        </template>
      </div>

      <!-- ── Step 1: identity + questionnaire ── -->
      <div v-show="step === 1" class="mt-6 space-y-4">
        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Email <span class="text-red-500">*</span></label>
            <input v-model="form.email" type="email" placeholder="your@email.com" :class="[inputClass, { 'border-red-300': errors.email }]" />
            <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ firstError('email') }}</p>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">暱稱 <span class="text-red-500">*</span></label>
            <input v-model="form.name" type="text" placeholder="怎麼稱呼你" :class="[inputClass, { 'border-red-300': errors.name }]" />
            <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ firstError('name') }}</p>
          </div>
        </div>

        <button
          v-if="!questionnaireOpen"
          type="button"
          :disabled="!identityFilled"
          class="w-full px-4 py-3 rounded-lg font-semibold bg-brand-gold hover:bg-brand-gold-dark text-brand-navy border border-brand-gold-dark/50 transition-all shadow-sm cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
          @click="openQuestionnaire"
        >
          開始申請
        </button>

        <div v-else class="space-y-4 pt-2 border-t border-gray-100">
          <p class="text-xs text-gray-500 pt-2">以下為簡易問卷，<span class="text-red-500">*</span> 為必填。</p>

          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">手機電話 <span class="text-red-500">*</span></label>
            <input v-model="form.phone" type="tel" placeholder="0912345678" :class="[inputClass, { 'border-red-300': errors.phone }]" />
            <p v-if="errors.phone" class="mt-1 text-sm text-red-600">{{ firstError('phone') }}</p>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">職業和從事時長 <span class="text-red-500">*</span></label>
            <input v-model="form.occupation" type="text" placeholder="例：平面設計師，做了 6 年" :class="[inputClass, { 'border-red-300': errors.occupation }]" />
            <p v-if="errors.occupation" class="mt-1 text-sm text-red-600">{{ firstError('occupation') }}</p>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">目前的事業瓶頸 <span class="text-red-500">*</span></label>
            <textarea v-model="form.bottleneck" rows="3" placeholder="卡在哪裡？越具體，我們越幫得上忙" :class="[inputClass, { 'border-red-300': errors.bottleneck }]" />
            <p v-if="errors.bottleneck" class="mt-1 text-sm text-red-600">{{ firstError('bottleneck') }}</p>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">你的知識或能力專長 <span class="text-red-500">*</span></label>
            <textarea v-model="form.expertise" rows="3" placeholder="你會什麼、別人會來問你什麼" :class="[inputClass, { 'border-red-300': errors.expertise }]" />
            <p v-if="errors.expertise" class="mt-1 text-sm text-red-600">{{ firstError('expertise') }}</p>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">經營社群網址<span class="text-gray-400">（如有）</span></label>
            <input v-model="form.social_url" type="url" placeholder="https://instagram.com/..." :class="[inputClass, { 'border-red-300': errors.social_url || socialUrlInvalid }]" />
            <p v-if="errors.social_url" class="mt-1 text-sm text-red-600">{{ firstError('social_url') }}</p>
            <p v-else-if="socialUrlInvalid" class="mt-1 text-sm text-red-600">{{ SOCIAL_URL_HINT }}</p>
          </div>

          <button
            type="button"
            :disabled="!questionnaireFilled"
            class="w-full px-4 py-3 rounded-lg font-semibold bg-brand-gold hover:bg-brand-gold-dark text-brand-navy border border-brand-gold-dark/50 transition-all shadow-sm cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
            @click="goTo(2)"
          >
            下一步
          </button>
        </div>
      </div>

      <!-- ── Step 2: commitments ── -->
      <div v-show="step === 2" class="mt-6 space-y-4">
        <p class="text-sm font-semibold text-gray-900">預約前，請先確認：</p>

        <label
          v-for="(text, i) in COMMITMENTS"
          :key="i"
          class="flex items-start gap-3 rounded-lg border p-3 cursor-pointer transition"
          :class="commitments[i] ? 'border-brand-teal/40 bg-brand-teal/5' : 'border-gray-200 hover:bg-gray-50'"
        >
          <input v-model="commitments[i]" type="checkbox" class="mt-0.5 rounded border-gray-300 text-brand-teal focus:ring-brand-teal cursor-pointer" />
          <span class="text-sm text-gray-700 leading-relaxed">{{ text }}</span>
        </label>

        <p v-if="errors.commitments" class="text-sm text-red-600">{{ firstError('commitments') }}</p>
        <p v-if="!allCommitted" class="text-xs text-gray-500">請確認全部項目後繼續。</p>

        <div class="flex gap-3">
          <button type="button" class="px-4 py-3 rounded-lg text-sm text-gray-600 border border-gray-200 cursor-pointer hover:bg-gray-50 transition" @click="goTo(1)">上一步</button>
          <button
            type="button"
            :disabled="!allCommitted"
            class="flex-1 px-4 py-3 rounded-lg font-semibold bg-brand-gold hover:bg-brand-gold-dark text-brand-navy border border-brand-gold-dark/50 transition-all shadow-sm cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
            @click="goTo(3)"
          >
            下一步
          </button>
        </div>
      </div>

      <!-- ── Step 3: slot picker ── -->
      <div v-show="step === 3" class="mt-6 space-y-4">
        <div v-if="errors.slot" class="rounded-xl border border-red-300 bg-red-50 p-4">
          <p class="text-sm leading-relaxed text-red-700 break-words">{{ errors.slot }}</p>
        </div>

        <!-- Code beside the picker, not above it: stacked, it read as one more
             optional field to scroll past, and the 15 extra minutes it buys is
             the whole reason somebody was given one. -->
        <div class="grid gap-4 sm:grid-cols-[minmax(0,14rem)_1fr] sm:items-start">
          <div class="rounded-xl border-2 border-dashed border-brand-gold/70 bg-brand-gold/10 p-3">
            <p class="text-sm font-bold text-brand-navy">預約優惠碼</p>
            <p class="mt-0.5 text-xs text-gray-600">填入可將諮詢延長為 45 分鐘</p>
            <div class="mt-2 flex gap-2">
              <input
                v-model="form.code"
                type="text"
                placeholder="選填"
                class="w-full min-w-0 rounded-lg border border-gray-300 px-3 py-2 text-sm uppercase tabular-nums focus:border-brand-teal focus:ring-brand-teal"
                @keyup.enter="fetchSlots"
              />
              <button
                type="button"
                class="shrink-0 px-3 py-2 rounded-lg text-sm font-semibold bg-brand-navy text-white cursor-pointer hover:opacity-90 transition"
                @click="fetchSlots"
              >
                套用
              </button>
            </div>
            <p v-if="codeApplied" class="mt-2 text-xs font-semibold text-green-700">已套用，諮詢延長為 {{ slotMinutes }} 分鐘。</p>
            <p v-else-if="codeChecked" class="mt-2 text-xs text-amber-700">此優惠碼無效，將以 30 分鐘進行。</p>
          </div>

          <div>
            <div v-if="slotsLoading" class="py-8 text-center text-sm text-gray-400">載入時段中…</div>

            <div v-else-if="!hasSlots" class="rounded-lg bg-gray-50 border border-gray-200 p-5 text-center">
              <p class="text-sm text-gray-600">目前沒有開放的時段。</p>
              <p class="mt-1 text-xs text-gray-500">送出申請後，新時段釋出時我們會主動以 Email 通知你。</p>
              <button
                type="button"
                :disabled="submitting || socialUrlInvalid"
                class="mt-4 w-full px-4 py-3 rounded-lg font-semibold bg-brand-gold hover:bg-brand-gold-dark text-brand-navy border border-brand-gold-dark/50 transition-all shadow-sm cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                @click="submitWaitlist"
              >
                {{ submitting ? '送出中…' : '送出申請並等候通知' }}
              </button>
              <p v-if="socialUrlInvalid" class="mt-2 text-xs text-red-600">
                {{ SOCIAL_URL_HINT }}
                <button type="button" class="underline cursor-pointer hover:opacity-70" @click="editFrom(1)">前往修改</button>
              </p>
            </div>

            <div v-else class="space-y-4 max-h-80 overflow-y-auto pr-1">
              <p class="text-xs text-gray-500">一場 {{ slotMinutes }} 分鐘，請選擇開始時間。</p>
              <div v-for="group in slotGroups" :key="group.date">
                <p class="text-xs font-semibold text-gray-700 mb-2">{{ group.date }}</p>
                <div class="flex flex-wrap gap-2">
                  <button
                    v-for="t in group.times"
                    :key="t.value"
                    type="button"
                    class="px-3 py-2 rounded-lg text-sm border tabular-nums cursor-pointer transition"
                    :class="form.slot_starts_at === t.value
                      ? 'bg-brand-teal text-white border-brand-teal ring-2 ring-brand-teal/30'
                      : 'border-gray-200 text-gray-700 hover:bg-gray-50'"
                    @click="form.slot_starts_at = t.value"
                  >
                    {{ t.label }}
                  </button>
                </div>
              </div>
            </div>

            <p v-if="errors.slot_starts_at" class="mt-2 text-sm text-red-600">{{ firstError('slot_starts_at') }}</p>
          </div>
        </div>

        <div class="flex gap-3">
          <button type="button" class="px-4 py-3 rounded-lg text-sm text-gray-600 border border-gray-200 cursor-pointer hover:bg-gray-50 transition" @click="goTo(2)">上一步</button>
          <button
            type="button"
            :disabled="!form.slot_starts_at"
            class="flex-1 px-4 py-3 rounded-lg font-semibold bg-brand-gold hover:bg-brand-gold-dark text-brand-navy border border-brand-gold-dark/50 transition-all shadow-sm cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
            @click="goTo(4)"
          >
            下一步
          </button>
        </div>
      </div>

      <!-- ── Step 4: review ── -->
      <div v-show="step === 4" class="mt-6 space-y-4">
        <p class="text-sm font-semibold text-gray-900">請再確認一次你的申請資料</p>

        <!-- One 修改 per destination, not per row: all seven fields below live
             on step 1, so seven buttons were seven ways to do one thing. -->
        <div class="rounded-lg border border-gray-200 overflow-hidden">
          <div class="flex items-center justify-between gap-3 border-b border-gray-200 bg-gray-50 px-4 py-2">
            <p class="text-xs font-semibold text-gray-600">申請資料</p>
            <button
              type="button"
              class="text-xs cursor-pointer hover:opacity-70 transition"
              :class="socialUrlInvalid ? 'font-semibold text-red-600' : 'text-brand-teal'"
              @click="editFrom(1)"
            >
              修改
            </button>
          </div>

          <dl class="divide-y divide-gray-100 text-sm">
            <div
              v-for="row in reviewRows"
              :key="row.label"
              class="flex items-start gap-3 px-4 py-3"
              :class="{ 'bg-red-50': row.invalid }"
            >
              <dt class="w-28 shrink-0 text-xs" :class="row.invalid ? 'text-red-700' : 'text-gray-500'">{{ row.label }}</dt>
              <dd class="flex-1 break-words whitespace-pre-wrap" :class="row.invalid ? 'text-red-800' : 'text-gray-800'">
                {{ row.value }}
                <span v-if="row.invalid" class="mt-1 block text-xs font-medium text-red-600">{{ row.hint }}</span>
              </dd>
            </div>
          </dl>
        </div>

        <!-- Separate card because its 修改 goes somewhere else (step 3). -->
        <div class="rounded-lg border border-gray-200 overflow-hidden">
          <div class="flex items-center justify-between gap-3 border-b border-gray-200 bg-gray-50 px-4 py-2">
            <p class="text-xs font-semibold text-gray-600">諮詢時段</p>
            <button type="button" class="text-xs text-brand-teal cursor-pointer hover:opacity-70 transition" @click="editFrom(3)">
              修改
            </button>
          </div>
          <p class="px-4 py-3 text-sm font-medium text-gray-900">
            {{ selectedSlotLabel }}（{{ slotMinutes }} 分鐘）
          </p>
        </div>

        <!-- Only reachable on a resumed draft: step 1 will not let an invalid
             URL through, but a lead saved before FR-027 tightened can arrive
             here with one already in the form. -->
        <p v-if="socialUrlInvalid" class="text-sm text-red-600">
          社群網址格式不正確，請先修改後再送出。留白也可以，這欄是選填的。
        </p>

        <div class="rounded-lg bg-red-50 border border-red-200 p-4">
          <p class="text-sm font-bold text-red-800">若確定預約卻無故不出席，我們將永久黑名單。</p>
          <p class="mt-1 text-xs text-red-700 leading-relaxed">
            顧問的時間是保留給你的。如果臨時無法出席，請提前回信告知，我們會為你改期。
          </p>
        </div>

        <!-- A block, not a red line: the most useful thing here is an address
             the applicant has to act on (FR-066), and a one-line error is easy
             to skim past. -->
        <div v-if="errors.submit" class="rounded-xl border border-red-300 bg-red-50 p-4">
          <p class="text-sm font-semibold text-red-800">無法送出</p>
          <p class="mt-1 text-sm leading-relaxed text-red-700 break-words">{{ errors.submit }}</p>
        </div>

        <!-- Appears after the first press. The address is the only thing in it
             that matters, so it is the only thing set large. -->
        <EmailReviewNotice v-if="emailConfirming" :email="form.email" @edit="editEmail">
          確認連結會寄到這個地址，打錯的話你不會收到任何通知，時段也會在 1 小時後自動釋出。
        </EmailReviewNotice>

        <div class="flex gap-3">
          <button type="button" class="px-4 py-3 rounded-lg text-sm text-gray-600 border border-gray-200 cursor-pointer hover:bg-gray-50 transition" @click="goTo(3)">上一步</button>
          <button
            type="button"
            :disabled="submitting || socialUrlInvalid || emailCountdown > 0"
            class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-3 rounded-lg font-semibold bg-brand-gold hover:bg-brand-gold-dark text-brand-navy border border-brand-gold-dark/50 transition-all shadow-sm cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
            @click="requestSubmit"
          >
            <svg v-if="submitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
            <template v-if="submitting">送出中…</template>
            <template v-else-if="emailCountdown > 0">請再看一眼 Email（{{ emailCountdown }}）</template>
            <template v-else-if="emailConfirming">Email 正確，確認送出</template>
            <template v-else>送出申請</template>
          </button>
        </div>
      </div>
    </template>
  </div>
</template>
