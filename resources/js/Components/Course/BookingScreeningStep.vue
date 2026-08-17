<script setup>
import { ref, computed, onUnmounted } from 'vue'

/**
 * Step 1 of the booking wizard — the five-question gate (011 US24).
 *
 * The questions and their option labels come from the server; the scoring does
 * not (FR-124). This component knows only whether the answers passed, which is
 * also all the endpoint returns.
 *
 * The 60-second wait runs here rather than on the server (FR-128): holding a
 * request open for a minute would occupy a PHP-FPM worker, and a dropped
 * connection would present a finished verdict as a failure.
 */
const props = defineProps({
  course: { type: Object, required: true },
  questions: { type: Array, default: () => [] },
  // The wizard's own reactive form. Its fields are written in place — the
  // answers have to travel with the booking submit later on (FR-129).
  form: { type: Object, required: true },
})

const emit = defineEmits(['passed'])

const REVIEW_SECONDS = 15

const errors = ref({})
const submitting = ref(false)
const remaining = ref(0)
const declined = ref(false)
let timer = null

// Everyone waits the same 60 seconds, pass or fail (D99): letting the accepted
// through instantly would make the speed difference say the verdict out loud.
const reviewing = computed(() => remaining.value > 0)

const identityFilled = computed(() => props.form.name.trim() !== '' && props.form.email.trim() !== '')

const allAnswered = computed(() => props.questions.every(q => !!props.form[q.field]))

const ready = computed(() => identityFilled.value && allAnswered.value)

// Five questions with five options each is a wall of text to open a page with,
// and the first thing a visitor sees should still be two fields. The
// questionnaire is revealed once there is a name and address to attach it to,
// and then one question at a time — each answer uncovers the next, so the
// visible form never gets longer than the thing being asked right now.
// A prefilled draft has already been through all of this: show it whole.
const identityConfirmed = ref(props.questions.length > 0 && props.questions.every(q => !!props.form[q.field]))

function revealQuestions() {
  if (identityFilled.value) identityConfirmed.value = true
}

/** Every answered question, plus the next one. */
const visibleQuestions = computed(() =>
  props.questions.filter((q, i) => props.questions.slice(0, i).every(p => !!props.form[p.field]))
)

const answeredCount = computed(() => props.questions.filter(q => !!props.form[q.field]).length)

/** What was last sent for review, so an unchanged form is not re-reviewed. */
const reviewedSnapshot = ref(null)

const snapshot = () => JSON.stringify([
  props.form.email.trim(),
  props.questions.map(q => props.form[q.field]),
])

const alreadyCleared = computed(() => reviewedSnapshot.value !== null && reviewedSnapshot.value === snapshot())

function stopTimer() {
  clearInterval(timer)
  timer = null
}

onUnmounted(stopTimer)

function startReview(passed) {
  remaining.value = REVIEW_SECONDS

  stopTimer()
  timer = setInterval(() => {
    remaining.value -= 1

    if (remaining.value <= 0) {
      stopTimer()
      finish(passed)
    }
  }, 1000)
}

function finish(passed) {
  if (passed) {
    reviewedSnapshot.value = snapshot()
    emit('passed')
    return
  }

  declined.value = true
}

async function submit() {
  if (!ready.value || submitting.value) return

  // Coming back to a step that already cleared, without touching anything:
  // reviewing the same answers a second time is theatre with no audience.
  if (alreadyCleared.value) {
    emit('passed')
    return
  }

  errors.value = {}
  submitting.value = true

  try {
    const { data } = await window.axios.post(`/course/${props.course.id}/screen`, {
      name: props.form.name,
      email: props.form.email,
      ...Object.fromEntries(props.questions.map(q => [q.field, props.form[q.field]])),
    })

    startReview(data?.passed === true)
  } catch (e) {
    const fields = e.response?.data?.errors

    if (fields) {
      errors.value = fields
    } else {
      errors.value = { submit: e.response?.data?.message || '送出失敗，請稍後再試。' }
    }
  } finally {
    submitting.value = false
  }
}

function firstError(key) {
  const e = errors.value[key]
  return Array.isArray(e) ? e[0] : e
}

const progress = computed(() => Math.round(((REVIEW_SECONDS - remaining.value) / REVIEW_SECONDS) * 100))

const inputClass = 'block w-full rounded-lg border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-brand-teal focus:ring-brand-teal'
</script>

<template>
  <!-- ── Turned away ── -->
  <div v-if="declined" class="py-4 text-center">
    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">
      <svg class="h-6 w-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
    </div>
    <p class="text-base font-bold text-gray-900">審核結果</p>
    <!-- Grey, not red: nothing here was filled in wrongly. And deliberately no
         score and no reason — that would read as a guide to the retry. -->
    <p class="mx-auto mt-3 max-w-md text-sm leading-relaxed text-gray-600">
      感謝您的申請。根據您的現況，我們判斷現階段可能不是最適合安排一對一諮詢和推進下一步計劃的時機，因此此次先不安排預約。謝謝您的理解，祝您接下來的規劃順利。
    </p>
  </div>

  <!-- ── Automatic review in progress ── -->
  <div v-else-if="reviewing" class="py-6 text-center">
    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-brand-teal/10">
      <svg class="h-6 w-6 animate-spin text-brand-teal" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
      </svg>
    </div>
    <p class="text-base font-bold text-gray-900">自動審核中</p>
    <p class="mt-2 text-sm text-gray-600">正在依你的回答確認是否適合安排 1v1 諮詢，請稍候。</p>

    <div class="mx-auto mt-5 h-1.5 w-full max-w-sm overflow-hidden rounded-full bg-gray-100">
      <div class="h-full rounded-full bg-brand-teal transition-all duration-1000 ease-linear" :style="{ width: `${progress}%` }" />
    </div>
    <p class="mt-2 font-mono text-xs tabular-nums text-gray-500">約 {{ remaining }} 秒</p>
    <p class="mt-3 text-xs text-gray-400">請勿關閉此頁面。</p>
  </div>

  <!-- ── The questions ── -->
  <div v-else class="mt-6 space-y-5">
    <div class="grid gap-4 sm:grid-cols-2">
      <div>
        <label class="mb-1 block text-xs font-medium text-gray-600">Email <span class="text-red-500">*</span></label>
        <input v-model="form.email" type="email" placeholder="your@email.com" :class="[inputClass, { 'border-red-300': errors.email }]" />
        <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ firstError('email') }}</p>
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium text-gray-600">暱稱 <span class="text-red-500">*</span></label>
        <input v-model="form.name" type="text" placeholder="怎麼稱呼你" :class="[inputClass, { 'border-red-300': errors.name }]" />
        <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ firstError('name') }}</p>
      </div>
    </div>

    <!-- Nothing below this line exists until there is somebody to attach it to. -->
    <button
      v-if="!identityConfirmed"
      type="button"
      :disabled="!identityFilled"
      class="w-full cursor-pointer rounded-lg border border-brand-gold-dark/50 bg-brand-gold px-4 py-3 font-semibold text-brand-navy shadow-sm transition-all hover:bg-brand-gold-dark disabled:cursor-not-allowed disabled:opacity-50"
      @click="revealQuestions"
    >
      下一步
    </button>

    <template v-else>
      <div class="flex items-center justify-between gap-3 border-t border-gray-100 pt-4">
        <p class="text-xs text-gray-500">以下五題請據實回答。</p>
        <p class="shrink-0 text-xs font-medium tabular-nums text-gray-400">{{ answeredCount }} / {{ questions.length }}</p>
      </div>

      <div v-for="q in visibleQuestions" :key="q.field" class="space-y-2">
        <p class="text-sm font-semibold text-gray-900">
          {{ questions.indexOf(q) + 1 }}. {{ q.title }} <span class="text-red-500">*</span>
        </p>

        <div class="space-y-1.5">
          <label
            v-for="opt in q.options"
            :key="opt.value"
            class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition"
            :class="form[q.field] === opt.value ? 'border-brand-teal/40 bg-brand-teal/5' : 'border-gray-200 hover:bg-gray-50'"
          >
            <input
              v-model="form[q.field]"
              type="radio"
              :name="q.field"
              :value="opt.value"
              class="mt-0.5 cursor-pointer border-gray-300 text-brand-teal focus:ring-brand-teal"
            />
            <span class="text-sm leading-relaxed text-gray-700">{{ opt.label }}</span>
          </label>
        </div>

        <p v-if="errors[q.field]" class="text-sm text-red-600">{{ firstError(q.field) }}</p>
      </div>
    </template>

    <div v-if="errors.submit" class="rounded-xl border border-red-300 bg-red-50 p-4">
      <p class="text-sm leading-relaxed break-words text-red-700">{{ errors.submit }}</p>
    </div>

    <button
      v-if="identityConfirmed"
      type="button"
      :disabled="!ready || submitting"
      class="w-full cursor-pointer rounded-lg border border-brand-gold-dark/50 bg-brand-gold px-4 py-3 font-semibold text-brand-navy shadow-sm transition-all hover:bg-brand-gold-dark disabled:cursor-not-allowed disabled:opacity-50"
      @click="submit"
    >
      <template v-if="submitting">送出中…</template>
      <template v-else-if="alreadyCleared">下一步</template>
      <template v-else>送出審核</template>
    </button>
  </div>
</template>
