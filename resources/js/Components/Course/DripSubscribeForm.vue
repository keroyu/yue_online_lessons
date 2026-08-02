<script setup>
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

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
const code = ref('')
const nickname = ref(page.props.flash?.drip_nickname || '')
const step = ref('email') // 'email' or 'code'
const processing = ref(false)
const errors = ref({})

// Carries the email that already claimed this product, so the form can say
// exactly which inbox the content went to instead of leaving the visitor stuck.
const claimedEmail = computed(() => page.props.flash?.drip_already_claimed || '')
const alreadyClaimed = computed(() => !!claimedEmail.value)

// Check flash data for step progression
const flashEmail = computed(() => page.props.flash?.drip_email)
const flashCourseId = computed(() => page.props.flash?.drip_course_id)

// If we have flash data, we're on step 2
if (flashEmail?.value && flashCourseId?.value == props.courseId) {
  step.value = 'code'
  email.value = flashEmail.value
}

const sendCode = () => {
  processing.value = true
  errors.value = {}

  router.post('/drip/subscribe', {
    course_id: props.courseId,
    email: email.value,
    nickname: nickname.value,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      step.value = 'code'
      processing.value = false
    },
    onError: (errs) => {
      errors.value = errs
      processing.value = false
    },
  })
}

const verifyCode = () => {
  processing.value = true
  errors.value = {}

  router.post('/drip/verify', {
    course_id: props.courseId,
    email: email.value,
    code: code.value,
    nickname: nickname.value,
  }, {
    onError: (errs) => {
      errors.value = errs
      processing.value = false
    },
  })
}

const goBack = () => {
  step.value = 'email'
  code.value = ''
  errors.value = {}
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

    <!-- Step 1: Enter email -->
    <form v-if="step === 'email'" @submit.prevent="sendCode" class="space-y-4">
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

      <button
        type="submit"
        :disabled="processing || !email || !nickname"
        class="w-full px-6 py-3 bg-brand-gold hover:bg-brand-gold-dark text-brand-navy border border-brand-gold-dark/50 rounded-lg font-semibold transition-all disabled:opacity-50 disabled:cursor-not-allowed"
      >
        {{ processing ? '發送中...' : '取得驗證碼' }}
      </button>
    </form>

    <!-- Step 2: Enter verification code -->
    <form v-else @submit.prevent="verifyCode" class="space-y-4">
      <p class="text-sm text-gray-600">
        驗證碼已發送至 <span class="font-medium text-gray-900">{{ email }}</span>
      </p>
      <p class="text-xs text-gray-400 mt-1">來信者為「經營者時間銀行」，找不到時請檢查垃圾郵件</p>

      <div>
        <label for="drip-code" class="block text-sm font-medium text-gray-700 mb-1">
          驗證碼
        </label>
        <input
          id="drip-code"
          v-model="code"
          type="text"
          inputmode="numeric"
          placeholder="請輸入 6 位驗證碼"
          maxlength="6"
          required
          class="block w-full rounded-lg border-gray-300 px-4 py-3 text-base text-center tracking-widest font-mono shadow-sm focus:border-brand-teal focus:ring-brand-teal"
          :class="{ 'border-red-300': errors.code }"
        />
        <p v-if="errors.code" class="mt-1 text-sm text-red-600">{{ errors.code }}</p>
      </div>

      <button
        type="submit"
        :disabled="processing || !code"
        class="w-full px-6 py-3 bg-brand-gold hover:bg-brand-gold-dark text-brand-navy border border-brand-gold-dark/50 rounded-lg font-semibold transition-all disabled:opacity-50 disabled:cursor-not-allowed"
      >
        {{ processing ? '驗證中...' : '確認驗證碼' }}
      </button>

      <button
        type="button"
        @click="goBack"
        class="w-full text-sm text-gray-500 hover:text-gray-700"
      >
        使用其他 Email
      </button>
    </form>
  </div>
</template>
