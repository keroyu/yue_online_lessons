<script setup>
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
  token: {
    type: String,
    required: true,
  },
  email: {
    type: String,
    default: '',
  },
  status: {
    type: String,
    default: '',
  },
})

const processing = ref(false)

const confirmUnsubscribe = () => {
  processing.value = true
  router.post(`/newsletter/unsubscribe/${props.token}`, {}, {
    onFinish: () => {
      processing.value = false
    },
  })
}
</script>

<template>
  <Head title="退訂電子報" />

  <div class="max-w-md mx-auto px-4 py-16 text-center">
    <template v-if="status === 'unsubscribed'">
      <h1 class="text-2xl font-bold text-gray-900">你已退訂</h1>
      <p class="text-gray-500 mt-3">{{ email }} 已不在電子報清單中。你的會員身分與已購課程完全保留。</p>
    </template>

    <template v-else>
      <h1 class="text-2xl font-bold text-gray-900">確認退訂電子報？</h1>
      <p class="text-gray-500 mt-3">
        退訂後將不再收到 <strong>{{ email }}</strong> 的電子報，
        但你的會員身分、已購課程與積分完全保留。
      </p>

      <button
        type="button"
        :disabled="processing"
        class="mt-6 bg-gray-900 text-white px-6 py-2.5 font-medium disabled:opacity-50"
        @click="confirmUnsubscribe"
      >{{ processing ? '處理中…' : '確認退訂' }}</button>
    </template>
  </div>
</template>
