<script setup>
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

const props = defineProps({
  courseId: {
    type: Number,
    required: true,
  },
})

const page = usePage()

const email = ref('')
const code = ref('')
const step = ref('email') // 'email' or 'code'
const processing = ref(false)
const errors = ref({})

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
    <h3 class="text-lg font-semibold text-gray-900 mb-4">免費訂閱</h3>

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
          class="block w-full rounded-lg border-gray-300 px-4 py-3 text-base shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          :class="{ 'border-red-300': errors.email }"
        />
        <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email }}</p>
      </div>

      <button
        type="submit"
        :disabled="processing || !email"
        class="w-full px-6 py-3 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
      >
        {{ processing ? '發送中...' : '取得驗證碼' }}
      </button>
    </form>

    <!-- Step 2: Enter verification code -->
    <form v-else @submit.prevent="verifyCode" class="space-y-4">
      <p class="text-sm text-gray-600">
        驗證碼已發送至 <span class="font-medium text-gray-900">{{ email }}</span>
      </p>

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
          class="block w-full rounded-lg border-gray-300 px-4 py-3 text-base text-center tracking-widest font-mono shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          :class="{ 'border-red-300': errors.code }"
        />
        <p v-if="errors.code" class="mt-1 text-sm text-red-600">{{ errors.code }}</p>
      </div>

      <button
        type="submit"
        :disabled="processing || !code"
        class="w-full px-6 py-3 bg-indigo-600 text-white rounded-lg font-semibold hover:bg-indigo-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
      >
        {{ processing ? '驗證中...' : '確認訂閱' }}
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
