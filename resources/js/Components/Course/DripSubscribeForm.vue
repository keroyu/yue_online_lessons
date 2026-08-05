<script setup>
import { ref, computed, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import EmailReviewNotice from '@/Components/EmailReviewNotice.vue'
import { useEmailReview } from '@/composables/useEmailReview'

const props = defineProps({
  courseId: {
    type: Number,
    required: true,
  },
  courseName: {
    type: String,
    default: '',
  },
})

const page = usePage()

const email = ref('')
const nickname = ref('')
const website = ref('') // honeypot
const processing = ref(false)
const errors = ref({})

// Carries the email that already claimed this product, so the form can say
// exactly which inbox the content went to instead of leaving the visitor stuck.
const claimedEmail = computed(() => page.props.flash?.drip_already_claimed || '')
const alreadyClaimed = computed(() => !!claimedEmail.value)

// Two-stage submit (US15). The claim no longer sends a verification code, so a
// mistyped address means the ebook goes nowhere and nobody is ever told —
// this pause is the only place left that can catch it.
const { confirming, countdown, start: startReview, reset: resetReview } = useEmailReview()

watch(email, resetReview)

const submitLabel = computed(() => {
  if (processing.value) return '送出中…'
  if (countdown.value > 0) return `請再看一眼 Email（${countdown.value}）`
  if (confirming.value) return 'Email 正確，確認領取'
  return '免費領取'
})

const requestSubmit = () => {
  if (!startReview()) return

  processing.value = true
  errors.value = {}

  router.post('/drip/subscribe', {
    course_id: props.courseId,
    email: email.value,
    nickname: nickname.value,
    website: website.value,
  }, {
    preserveScroll: true,
    onFinish: () => {
      processing.value = false
      resetReview()
    },
    onError: (errs) => {
      errors.value = errs
    },
  })
}
</script>

<template>
  <div class="bg-white rounded-xl border border-gray-200 p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">立刻免費領取{{ courseName ? `【${courseName}】` : '' }}！</h3>

    <!-- Already claimed: the content lives in their inbox, not on the site, so
         point them at the mail rather than at a login they gain nothing from -->
    <div
      v-if="alreadyClaimed"
      class="mb-5 rounded-lg border-2 border-brand-gold bg-brand-gold/10 px-4 py-4"
    >
      <div class="flex items-start gap-3">
        <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-brand-gold">
          <svg class="h-4 w-4 text-brand-navy" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
          </svg>
        </span>
        <div class="text-sm leading-relaxed">
          <p class="text-base font-bold text-brand-navy">這個 Email 已經領取過了</p>
          <p class="mt-1.5 text-gray-700">
            內容是<strong class="text-brand-navy">寄到信箱</strong>的，不會在網站上觀看。
            請到<strong class="text-brand-navy">{{ claimedEmail }}</strong>收信；
            找不到的話看一下「促銷」「廣告」分頁或垃圾郵件，並把我們加入白名單。
          </p>
          <p class="mt-2 text-gray-700">
            想再領一份？把上面的 Email 換成<strong class="text-brand-navy">另一個信箱</strong>再送出一次即可。
          </p>
        </div>
      </div>
    </div>

    <form @submit.prevent="requestSubmit" class="space-y-4">
      <div>
        <label for="drip-email" class="block text-sm font-medium text-gray-700 mb-1">
          Email
        </label>
        <input
          id="drip-email"
          v-model="email"
          type="email"
          placeholder="請輸入您的 Email"
          required
          class="block w-full rounded-lg border-gray-300 px-4 py-3 text-base shadow-sm focus:border-brand-teal focus:ring-brand-teal"
          :class="{ 'border-red-300': errors.email }"
        />
        <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email }}</p>
      </div>

      <div>
        <label for="drip-nickname" class="block text-sm font-medium text-gray-700 mb-1">
          暱稱
        </label>
        <input
          id="drip-nickname"
          v-model="nickname"
          type="text"
          placeholder="請輸入您的暱稱"
          required
          maxlength="50"
          class="block w-full rounded-lg border-gray-300 px-4 py-3 text-base shadow-sm focus:border-brand-teal focus:ring-brand-teal"
          :class="{ 'border-red-300': errors.nickname }"
        />
        <p v-if="errors.nickname" class="mt-1 text-sm text-red-600">{{ errors.nickname }}</p>
      </div>

      <!-- honeypot: visually hidden -->
      <input v-model="website" type="text" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true" />

      <EmailReviewNotice v-if="confirming" :email="email" @edit="resetReview">
        電子書會寄到這個地址，打錯的話你不會收到任何通知。
        來信者為「經營者時間銀行」，找不到時請檢查垃圾郵件與「促銷」「廣告」分頁。
      </EmailReviewNotice>

      <button
        type="submit"
        :disabled="processing || !email || !nickname || countdown > 0"
        class="w-full px-6 py-3 bg-brand-gold hover:bg-brand-gold-dark text-brand-navy border border-brand-gold-dark/50 rounded-lg font-semibold transition-all disabled:opacity-50 disabled:cursor-not-allowed"
      >
        {{ submitLabel }}
      </button>
    </form>
  </div>
</template>
