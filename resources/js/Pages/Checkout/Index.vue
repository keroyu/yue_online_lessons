<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

defineOptions({ layout: false })

const props = defineProps({
  items:   { type: Array,  default: () => [] },
  total:   { type: Number, default: 0 },
  prefill: { type: Object, default: () => ({ name: null, email: null, phone: null }) },
})

const page = usePage()
const isAuthenticated = computed(() => !!page.props.auth?.user)

// Guest cart loaded from localStorage
const guestItems = ref([])
const guestTotal  = computed(() => guestItems.value.reduce((sum, i) => sum + (Number(i.price) || 0), 0))

const displayItems = computed(() =>
  isAuthenticated.value ? props.items : guestItems.value.map(i => ({
    id: i.id,
    course: { id: i.id, name: i.name, price: i.price, thumbnail: i.thumbnail ?? null },
  }))
)
const displayTotal = computed(() => isAuthenticated.value ? props.total : guestTotal.value)
const courseIds    = computed(() =>
  isAuthenticated.value
    ? props.items.map(i => i.course.id)
    : guestItems.value.map(i => i.id)
)

onMounted(() => {
  if (!isAuthenticated.value) {
    try {
      guestItems.value = JSON.parse(localStorage.getItem('guest_cart') || '[]')
    } catch {
      guestItems.value = []
    }
  }
})

// Buyer form
const name       = ref(props.prefill?.name  ?? '')
const email      = ref(props.prefill?.email ?? '')
const phone      = ref(props.prefill?.phone ?? '')
const agreeTerms = ref(false)

const emailPurchaseError = ref('')

const checkEmailPurchases = async () => {
  emailPurchaseError.value = ''
  const e = email.value.trim()
  if (!e || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e) || !courseIds.value.length) return
  try {
    const res = await window.axios.post('/api/checkout/check-email', {
      email: e,
      course_ids: courseIds.value,
    })
    if (res.data.purchased_course_ids?.length > 0) {
      emailPurchaseError.value = '此 Email 已購買過此課程，無需重複購買。若需存取課程請登入帳號，或聯絡客服。'
    }
  } catch {
    // ignore check failures — backend will catch on submit
  }
}

const formValid = computed(() =>
  name.value.trim().length > 0 &&
  email.value.trim().length > 0 &&
  /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value) &&
  phone.value.trim().length > 0 &&
  agreeTerms.value &&
  courseIds.value.length > 0 &&
  !emailPurchaseError.value
)

const submitting    = ref(false)
const errors        = ref({})
const errorBanner   = ref('')
const showLegalModal = ref(false)

const submitCheckout = async () => {
  if (!formValid.value || submitting.value) return
  submitting.value = true
  errors.value     = {}
  errorBanner.value = ''

  try {
    const res = await window.axios.post('/api/checkout/initiate', {
      buyer: {
        name:  name.value.trim(),
        email: email.value.trim(),
        phone: phone.value.trim(),
      },
      agree_terms: true,
      course_ids:  courseIds.value,
    })

    const { endpoint, fields } = res.data
    const form = document.createElement('form')
    form.method = 'POST'
    form.action = endpoint
    form.style.display = 'none'

    for (const [key, val] of Object.entries(fields)) {
      const input = document.createElement('input')
      input.type  = 'hidden'
      input.name  = key
      input.value = String(val)
      form.appendChild(input)
    }

    document.body.appendChild(form)
    form.submit()
  } catch (err) {
    submitting.value = false
    if (err.response?.status === 422) {
      errors.value = err.response.data.errors ?? {}
    } else if (err.response?.status === 409) {
      errorBanner.value = err.response.data.message ?? '結帳失敗，請重試。'
    } else {
      errorBanner.value = '發生錯誤，請稍後再試。'
    }
  }
}
</script>

<template>
  <AppLayout>
    <Head title="結帳" />

    <div class="max-w-2xl mx-auto px-4 py-10">
      <h1 class="text-2xl font-bold text-brand-navy mb-6">結帳</h1>

      <!-- Empty cart guard -->
      <div v-if="displayItems.length === 0" class="text-center py-16 text-gray-500">
        <p class="mb-4">購物車是空的，請先加入課程。</p>
        <a href="/" class="text-brand-teal hover:underline font-medium">瀏覽課程</a>
      </div>

      <template v-else>
        <!-- Order summary -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 mb-6">
          <h2 class="font-semibold text-brand-navy mb-3">訂單摘要</h2>
          <div class="space-y-3">
            <div v-for="item in displayItems" :key="item.id" class="flex justify-between text-sm">
              <span class="text-gray-700 truncate mr-4">{{ item.course.name }}</span>
              <span class="font-medium shrink-0">NT$ {{ item.course.price?.toLocaleString() }}</span>
            </div>
          </div>
          <div class="mt-4 pt-3 border-t border-gray-100 flex justify-between font-bold">
            <span>合計</span>
            <span class="text-brand-teal">NT$ {{ displayTotal.toLocaleString() }}</span>
          </div>
        </div>

        <!-- Error banner -->
        <div v-if="errorBanner" class="mb-4 rounded-lg bg-red-50 border border-red-200 p-3 text-sm text-red-700">
          {{ errorBanner }}
        </div>

        <!-- Buyer form -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 space-y-4">
          <h2 class="font-semibold text-brand-navy">購買者資料</h2>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">姓名 <span class="text-red-500">*</span></label>
            <input
              v-model="name"
              type="text"
              maxlength="100"
              placeholder="王小明"
              class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-teal"
              :class="errors['buyer.name'] ? 'border-red-400' : 'border-gray-300'"
            />
            <p v-if="errors['buyer.name']" class="mt-1 text-xs text-red-500">{{ errors['buyer.name'][0] }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
            <input
              v-model="email"
              type="email"
              maxlength="255"
              placeholder="example@email.com"
              class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-teal"
              :class="errors['buyer.email'] || emailPurchaseError ? 'border-red-400' : 'border-gray-300'"
              @blur="checkEmailPurchases"
              @input="emailPurchaseError = ''"
            />
            <p v-if="emailPurchaseError" class="mt-1 text-xs text-red-500">{{ emailPurchaseError }}</p>
            <p v-else-if="errors['buyer.email']" class="mt-1 text-xs text-red-500">{{ errors['buyer.email'][0] }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">電話 <span class="text-red-500">*</span></label>
            <input
              v-model="phone"
              type="tel"
              maxlength="20"
              placeholder="0912345678"
              class="w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-teal"
              :class="errors['buyer.phone'] ? 'border-red-400' : 'border-gray-300'"
            />
            <p v-if="errors['buyer.phone']" class="mt-1 text-xs text-red-500">{{ errors['buyer.phone'][0] }}</p>
          </div>

          <!-- Agree terms -->
          <div class="flex items-start gap-2">
            <input
              id="agree_terms"
              v-model="agreeTerms"
              type="checkbox"
              class="mt-0.5 rounded border-gray-300 text-brand-teal focus:ring-brand-teal"
            />
            <label for="agree_terms" class="text-sm text-gray-600">
              我已閱讀並同意
              <button type="button" class="text-brand-teal underline" @click="showLegalModal = true">服務條款與購買須知</button>
            </label>
          </div>
        </div>

        <!-- Submit -->
        <button
          @click="submitCheckout"
          :disabled="!formValid || submitting"
          class="mt-6 w-full py-3 rounded-lg font-semibold transition-all shadow-sm"
          :class="formValid && !submitting
            ? 'bg-brand-gold hover:bg-brand-gold-dark text-brand-navy border border-brand-gold-dark/50 cursor-pointer'
            : 'bg-gray-200 text-gray-400 cursor-not-allowed'"
        >
          {{ submitting ? '處理中…' : '前往付款' }}
        </button>
      </template>
    </div>

    <!-- Legal modal -->
    <Teleport to="body">
      <div
        v-if="showLegalModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
        @click.self="showLegalModal = false"
      >
        <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full p-6 max-h-[80vh] overflow-y-auto">
          <h3 class="text-lg font-bold mb-3">服務條款與購買須知</h3>
          <p class="text-sm text-gray-600 leading-relaxed">課程購買後即可立即存取。如有技術問題請聯絡客服。本課程為數位內容商品，依消費者保護法第 19 條規定，數位內容一經開通即不適用七日鑑賞期。</p>
          <button
            @click="showLegalModal = false"
            class="mt-4 w-full py-2 rounded-lg bg-brand-teal text-white font-medium hover:bg-brand-teal/80 transition-all"
          >
            我已了解
          </button>
        </div>
      </div>
    </Teleport>
  </AppLayout>
</template>
