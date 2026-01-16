<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'
import VerificationCodeInput from '@/Components/VerificationCodeInput.vue'

const page = usePage()
const flash = computed(() => page.props.flash)

const step = ref('email') // 'email' or 'code'
const isNewUser = ref(false)
const loading = ref(false)

const emailForm = useForm({
  email: '',
})

const codeForm = useForm({
  email: '',
  code: '',
  agree_terms: false,
})

const sendCode = () => {
  loading.value = true
  emailForm.post('/login/send-code', {
    preserveScroll: true,
    onSuccess: () => {
      codeForm.email = emailForm.email
      step.value = 'code'
      // Check if email exists (simplified - assume new user if no error about existing)
      isNewUser.value = true
    },
    onFinish: () => {
      loading.value = false
    },
  })
}

const verifyCode = () => {
  loading.value = true
  codeForm.post('/login/verify', {
    onFinish: () => {
      loading.value = false
    },
  })
}

const goBack = () => {
  step.value = 'email'
  codeForm.code = ''
  codeForm.agree_terms = false
}

const resendCode = () => {
  loading.value = true
  emailForm.post('/login/send-code', {
    preserveScroll: true,
    onFinish: () => {
      loading.value = false
    },
  })
}

// Watch for code completion
const onCodeComplete = (code) => {
  codeForm.code = code
}
</script>

<template>
  <Head title="登入" />

  <div class="min-h-[calc(100vh-200px)] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full">
      <div class="bg-white rounded-lg shadow-sm p-8">
        <!-- Logo -->
        <div class="text-center mb-8">
          <h1 class="text-2xl font-bold text-gray-900">
            歡迎回來
          </h1>
          <p class="mt-2 text-gray-600">
            {{ step === 'email' ? '請輸入您的 Email' : '請輸入驗證碼' }}
          </p>
        </div>

        <!-- Flash messages -->
        <div v-if="flash?.success" class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
          {{ flash.success }}
        </div>

        <!-- Email Step -->
        <form v-if="step === 'email'" @submit.prevent="sendCode" class="space-y-6">
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
              Email
            </label>
            <input
              id="email"
              type="email"
              v-model="emailForm.email"
              :disabled="loading"
              placeholder="your@email.com"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-200 focus:border-indigo-500 disabled:bg-gray-100"
            />
            <p v-if="emailForm.errors.email" class="mt-1 text-sm text-red-600">
              {{ emailForm.errors.email }}
            </p>
          </div>

          <button
            type="submit"
            :disabled="loading || !emailForm.email"
            class="w-full py-3 px-4 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors"
          >
            <span v-if="loading" class="flex items-center justify-center">
              <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              發送中...
            </span>
            <span v-else>發送驗證碼</span>
          </button>
        </form>

        <!-- Code Step -->
        <form v-else @submit.prevent="verifyCode" class="space-y-6">
          <div class="text-center">
            <p class="text-sm text-gray-600 mb-4">
              驗證碼已發送至 <strong>{{ codeForm.email }}</strong>
            </p>

            <VerificationCodeInput
              v-model="codeForm.code"
              :disabled="loading"
              @complete="onCodeComplete"
            />

            <p v-if="codeForm.errors.code" class="mt-2 text-sm text-red-600">
              {{ codeForm.errors.code }}
            </p>
          </div>

          <!-- Terms checkbox for new users -->
          <div v-if="isNewUser" class="pt-4">
            <label class="flex items-start gap-2 cursor-pointer">
              <input
                type="checkbox"
                v-model="codeForm.agree_terms"
                :disabled="loading"
                class="mt-1 h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
              />
              <span class="text-sm text-gray-600">
                我已閱讀並同意
                <a href="#" class="text-indigo-600 hover:underline">服務條款</a>
                和
                <a href="#" class="text-indigo-600 hover:underline">隱私政策</a>
              </span>
            </label>
            <p v-if="codeForm.errors.agree_terms" class="mt-1 text-sm text-red-600">
              {{ codeForm.errors.agree_terms }}
            </p>
          </div>

          <button
            type="submit"
            :disabled="loading || codeForm.code.length !== 6 || (isNewUser && !codeForm.agree_terms)"
            class="w-full py-3 px-4 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors"
          >
            <span v-if="loading" class="flex items-center justify-center">
              <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              驗證中...
            </span>
            <span v-else>登入</span>
          </button>

          <div class="flex items-center justify-between text-sm">
            <button
              type="button"
              @click="goBack"
              :disabled="loading"
              class="text-gray-500 hover:text-gray-700"
            >
              &larr; 返回
            </button>
            <button
              type="button"
              @click="resendCode"
              :disabled="loading"
              class="text-indigo-600 hover:text-indigo-800"
            >
              重新發送驗證碼
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
