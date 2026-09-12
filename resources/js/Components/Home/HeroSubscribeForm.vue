<script setup>
import { ref, computed, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import EmailReviewNotice from '@/Components/EmailReviewNotice.vue'
import { useDelayedConfirm } from '@/composables/useDelayedConfirm'

const page = usePage()

const nickname = ref('')
const email = ref('')
const website = ref('') // honeypot
const processing = ref(false)
const errors = ref({})

const flash = computed(() => page.props.flash || {})
const subscribed = computed(() => !!flash.value.newsletter_subscribed)
const info = computed(() => flash.value.newsletter_info || '')

// Two-stage submit (FR-066). This path sends no verification code, so a
// mistyped address fails silently — the welcome mail goes nowhere and nobody is
// told. This pause is the only thing left that can catch it.
const { confirming, countdown, start: startReview, reset: resetReview } = useDelayedConfirm()

watch(email, resetReview)

const submitLabel = computed(() => {
  if (processing.value) return '送出中…'
  if (countdown.value > 0) return `再看一眼（${countdown.value}）`
  if (confirming.value) return '確認訂閱'
  return '免費訂閱'
})

const requestSubmit = () => {
  if (!startReview()) return

  processing.value = true
  errors.value = {}

  router.post('/newsletter/quick-subscribe', {
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
  <!-- Success replaces the form in place — no redirect, no scroll jump (FR-017) -->
  <div v-if="subscribed" class="rounded-lg border border-brand-teal bg-brand-teal/5 px-4 py-3">
    <p class="font-semibold text-brand-teal">✓ 訂閱成功，歡迎信已寄出</p>
    <p class="mt-1 text-sm text-gray-600">
      請到信箱收信（找不到時看一下「促銷」「廣告」分頁與垃圾郵件）。
    </p>
  </div>

  <div v-else>
    <form class="flex flex-col gap-2 sm:flex-row" @submit.prevent="requestSubmit">
      <input
        v-model="nickname"
        type="text"
        required
        maxlength="50"
        placeholder="怎麼稱呼你"
        class="w-full border border-gray-300 px-3 py-2.5 text-gray-900 placeholder-gray-400 outline-none transition-colors focus:border-brand-teal focus:ring-1 focus:ring-brand-teal sm:w-40"
        :class="{ 'border-red-400': errors.nickname }"
      />
      <input
        v-model="email"
        type="email"
        required
        placeholder="你的 Email"
        class="w-full flex-1 border border-gray-300 px-3 py-2.5 text-gray-900 placeholder-gray-400 outline-none transition-colors focus:border-brand-teal focus:ring-1 focus:ring-brand-teal sm:max-w-56"
        :class="{ 'border-red-400': errors.email }"
      />
      <!-- honeypot: visually hidden -->
      <input v-model="website" type="text" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true" />
      <button
        type="submit"
        :disabled="processing || !email || !nickname || countdown > 0"
        class="cursor-pointer whitespace-nowrap bg-brand-teal px-6 py-2.5 font-semibold text-white transition-colors hover:bg-brand-teal/90 disabled:cursor-not-allowed disabled:opacity-50"
      >
        {{ submitLabel }}
      </button>
    </form>

    <p v-if="info" class="mt-2 text-sm text-amber-700">{{ info }}</p>
    <p v-else-if="errors.email || errors.nickname" class="mt-2 text-sm text-red-600">
      {{ errors.email || errors.nickname }}
    </p>

    <EmailReviewNotice v-if="confirming" :email="email" class="mt-3" @edit="resetReview">
      電子報會寄到這個地址，打錯的話你不會收到任何通知。
    </EmailReviewNotice>

    <!-- Newsletter consent, not the free-claim notice: that one talks about
         free resources and application eligibility, which is false here (FR-071) -->
    <p class="mt-2 text-xs leading-relaxed text-gray-500">
      訂閱即代表同意接收本站電子報，你可以隨時從信件底部一鍵退訂。
    </p>
  </div>
</template>
