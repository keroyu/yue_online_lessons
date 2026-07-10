<script setup>
import { ref, computed, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import VerificationCodeInput from '@/Components/VerificationCodeInput.vue'

defineProps({
  source: {
    type: String,
    default: '',
  },
})

const page = usePage()

const step = ref(1)
const email = ref('')
const code = ref('')
const website = ref('') // honeypot
const processing = ref(false)
const error = ref('')

const flash = computed(() => page.props.flash || {})
const subscribed = computed(() => !!flash.value.newsletter_subscribed)
const info = computed(() => flash.value.newsletter_info)

// React to server flash after each Inertia visit.
watch(
  () => [flash.value.newsletter_code_sent, flash.value.newsletter_subscribed, flash.value.newsletter_info],
  () => {
    if (flash.value.newsletter_code_sent) {
      step.value = 2
      if (flash.value.newsletter_email) email.value = flash.value.newsletter_email
    }
  }
)

const sendCode = () => {
  error.value = ''
  processing.value = true
  router.post('/newsletter/subscribe', { email: email.value, website: website.value }, {
    preserveScroll: true,
    onError: (errors) => {
      error.value = errors.email || '訂閱失敗'
    },
    onFinish: () => {
      processing.value = false
    },
  })
}

const verifyCode = () => {
  error.value = ''
  processing.value = true
  router.post('/newsletter/verify', { email: email.value, code: code.value }, {
    preserveScroll: true,
    onError: (errors) => {
      error.value = errors.code || '驗證失敗'
    },
    onFinish: () => {
      processing.value = false
    },
  })
}
</script>

<template>
  <div class="bg-brand-teal/5 border border-brand-teal/20 p-5">
    <template v-if="subscribed">
      <p class="text-brand-teal font-medium">✓ 訂閱成功，歡迎信已寄出！</p>
    </template>

    <template v-else>
      <h3 class="font-semibold text-gray-900">訂閱電子報，同時成為會員</h3>
      <p class="text-sm text-gray-500 mt-1">用 Email 接收最新教學分享。注意：過久不開信將被取消訂閱。</p>

      <p v-if="info" class="text-sm text-gray-600 mt-2">{{ info }}</p>

      <form v-if="step === 1" class="mt-3 flex flex-col sm:flex-row gap-2" @submit.prevent="sendCode">
        <input
          v-model="email"
          type="email"
          required
          placeholder="you@example.com"
          class="flex-1 bg-white border border-gray-400 px-3 py-2 text-gray-900 placeholder-gray-400 focus:border-brand-teal focus:ring-1 focus:ring-brand-teal outline-none"
        />
        <!-- honeypot: visually hidden -->
        <input v-model="website" type="text" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true" />
        <button
          type="submit"
          :disabled="processing"
          class="bg-brand-teal text-white px-5 py-2 font-medium hover:bg-brand-teal/90 cursor-pointer transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >{{ processing ? '傳送中…' : '訂閱' }}</button>
      </form>

      <div v-else class="mt-3">
        <p class="text-sm text-gray-600 mb-2">驗證碼已寄到 <strong>{{ email }}</strong>，請輸入 6 碼：</p>
        <VerificationCodeInput v-model="code" :disabled="processing" @complete="verifyCode" />
        <button
          type="button"
          :disabled="processing || code.length < 6"
          class="mt-3 bg-brand-teal text-white px-5 py-2 font-medium hover:bg-brand-teal/90 cursor-pointer transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          @click="verifyCode"
        >{{ processing ? '驗證中…' : '完成訂閱' }}</button>
      </div>

      <p v-if="error" class="text-sm text-red-600 mt-2">{{ error }}</p>
    </template>
  </div>
</template>
