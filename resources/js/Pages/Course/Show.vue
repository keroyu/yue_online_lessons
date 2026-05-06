<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { marked } from 'marked'
import AppLayout from "@/Components/Layout/AppLayout.vue"
import PriceDisplay from '@/Components/Course/PriceDisplay.vue'
import LegalPolicyModal from '@/Components/Legal/LegalPolicyModal.vue'
import DripSubscribeForm from '@/Components/Course/DripSubscribeForm.vue'
import { useCart } from '@/composables/useCart'

const page = usePage()

// Disable default layout - this page manages its own AppLayout with hideNav prop
defineOptions({
  layout: false
})

const props = defineProps({
  course: {
    type: Object,
    required: true,
  },
  isAdmin: {
    type: Boolean,
    default: false,
  },
  isPreviewMode: {
    type: Boolean,
    default: false,
  },
  isDrip: {
    type: Boolean,
    default: false,
  },
  isHidden: {
    type: Boolean,
    default: false,
  },
  userSubscription: {
    type: String,
    default: null,
  },
  canSubscribe: {
    type: Boolean,
    default: false,
  },
  hasPreviewLessons: {
    type: Boolean,
    default: false,
  },
  hasPurchased: {
    type: Boolean,
    default: false,
  },
  isOwned: {
    type: Boolean,
    default: false,
  },
  isInCart: {
    type: Boolean,
    default: false,
  },
})

// Landing Page mode detection
const isLandingMode = computed(() => {
  return new URLSearchParams(window.location.search).get("lp") === "1"
})

const hideUiElements = computed(() => isLandingMode.value || props.isHidden)

const agreed = ref(false)
const showPreviewAlert = ref(false)

const getTypeLabel = (type) => {
  const labels = {
    lecture: '講座',
    mini: '迷你課',
    full: '完整課程',
    high_ticket: '客製服務',
  }
  return labels[type] || type
}

// Generate Portaly URL from product_id
const portalyUrl = computed(() => {
  if (!props.course.portaly_product_id) {
    return null
  }
  return `https://portaly.cc/kyontw/product/${props.course.portaly_product_id}`
})

// Payment method flags
const usePayuni = computed(() => props.course.use_payuni === true)
const isFree = computed(() => props.course.is_free === true)
const hasBuyAction = computed(() => !!portalyUrl.value || usePayuni.value || isFree.value)

// Cart composable
const { addToCart, buyNow } = useCart()
// Local cart state — initialised from server prop, updated after add-to-cart
const isInCartLocal = ref(props.isInCart)
const cartAddError = ref('')

const handleAddToCart = async () => {
  cartAddError.value = ''
  const result = await addToCart(props.course.id, {
    name: props.course.name,
    price: props.course.price,
    thumbnail: props.course.thumbnail,
  })
  if (result.success) {
    isInCartLocal.value = true
  } else {
    cartAddError.value = result.error || '加入購物車失敗'
  }
}

const handleBuyNow = async () => {
  cartAddError.value = ''
  await buyNow(props.course.id, {
    name: props.course.name,
    price: props.course.price,
    thumbnail: props.course.thumbnail,
  })
}

const openPortaly = () => {
  if (props.isPreviewMode) {
    showPreviewAlert.value = true
    return
  }
  if (portalyUrl.value) {
    window.open(portalyUrl.value, '_blank')
  }
}

// ── Legacy PayUni direct-initiate (preserved for YC-prefix orders only; cart flow uses /checkout) ──

// ── Free enrollment ───────────────────────────────────────────────────────────
const showFreeForm = ref(false)
const freeFormEmail = ref(page.props.auth?.user?.email || '')
const freeFormName = ref(page.props.auth?.user?.real_name || '')
const freeFormPhone = ref(page.props.auth?.user?.phone || '')
const freeSubmitting = ref(false)
const freeSuccess = ref(false)
const freeError = ref('')
const showFreeConfirm = ref(false)

const openFreeForm = () => {
  showFreeForm.value = true
  setTimeout(() => {
    purchaseSectionRef.value?.scrollIntoView({ behavior: 'smooth', block: 'center' })
  }, 50)
}

const submitFreeEnrollment = async () => {
  freeError.value = ''
  // If not logged in, show confirmation first
  if (!page.props.auth?.user && !showFreeConfirm.value) {
    showFreeConfirm.value = true
    return
  }
  freeSubmitting.value = true
  try {
    await window.axios.post(`/api/purchase/free/${props.course.id}`, {
      email: freeFormEmail.value,
      name: freeFormName.value,
      phone: freeFormPhone.value,
    })
    freeSuccess.value = true
    showFreeConfirm.value = false
  } catch (e) {
    freeError.value = e.response?.data?.message || '報名失敗，請稍後再試。'
    showFreeConfirm.value = false
  } finally {
    freeSubmitting.value = false
  }
}

// Ref for purchase section (scroll target when not yet agreed)
const purchaseSectionRef = ref(null)

// Floating panel: show when both top info and bottom purchase section are out of view
const topInfoRef = ref(null)
const topInfoVisible = ref(true)
const bottomPurchaseVisible = ref(false)
const showFloatingPanel = computed(() => !topInfoVisible.value && !bottomPurchaseVisible.value)

let observer = null

onMounted(() => {
  observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.target === topInfoRef.value) {
          topInfoVisible.value = entry.isIntersecting
        } else if (entry.target === purchaseSectionRef.value) {
          bottomPurchaseVisible.value = entry.isIntersecting
        }
      })
    },
    { threshold: 0 }
  )
  if (topInfoRef.value) observer.observe(topInfoRef.value)
  // purchaseSectionRef may not be mounted yet (v-else), watch for it
  watch(purchaseSectionRef, (el) => {
    if (el && observer) observer.observe(el)
  }, { immediate: true })
})

onUnmounted(() => {
  if (observer) observer.disconnect()
})

const handleBuyClick = () => {
  if (props.isPreviewMode) {
    showPreviewAlert.value = true
    return
  }
  if (isFree.value) {
    openFreeForm()
    return
  }
  openPortaly()
}

const closePreviewAlert = () => {
  showPreviewAlert.value = false
}

// Legal Policy Modal
const showLegalModal = ref(false)
const legalModalType = ref('terms')

const openLegalModal = (type) => {
  legalModalType.value = type
  showLegalModal.value = true
}

const closeLegalModal = () => {
  showLegalModal.value = false
}

// marked.js v17 passes raw HTML (including <iframe> embeds) through by default.
// Do NOT add DOMPurify here — admin content is trusted and iframes must be preserved.
const renderedDescription = computed(() => marked(props.course.description_md || ''))

// Drip subscription
const subscribing = ref(false)
const subscribeErrors = ref({})
const memberNickname = ref(page.props.auth?.user?.nickname || '')

const memberSubscribe = () => {
  subscribing.value = true
  subscribeErrors.value = {}

  router.post(`/member/drip/subscribe/${props.course.id}`, {
    nickname: memberNickname.value,
  }, {
    onError: (errs) => {
      subscribeErrors.value = errs
      subscribing.value = false
    },
  })
}

const subscriptionStatusLabel = computed(() => {
  const labels = {
    active: '訂閱中',
    converted: '已轉換',
    completed: '已完成',
    unsubscribed: '已退訂',
  }
  return labels[props.userSubscription] || props.userSubscription
})

const ctaLabel = computed(() => props.course.tagline || props.course.name)

const isHighTicket = computed(() => props.course.is_high_ticket === true)
const highTicketHidePrice = computed(() => props.course.high_ticket_hide_price === true)

// High ticket booking form
const bookingName = ref(page.props.auth?.user?.real_name || '')
const bookingEmail = ref(page.props.auth?.user?.email || '')
const bookingSubmitting = ref(false)
const bookingSuccess = ref(false)
const bookingErrors = ref({})

const submitBooking = async () => {
  bookingErrors.value = {}
  bookingSubmitting.value = true
  try {
    await window.axios.post(`/course/${props.course.id}/book`, {
      name: bookingName.value,
      email: bookingEmail.value,
    })
    bookingSuccess.value = true
  } catch (e) {
    const errors = e.response?.data?.errors
    if (errors) {
      bookingErrors.value = errors
    } else {
      bookingErrors.value = { booking: e.response?.data?.message || '預約失敗，請稍後再試。' }
    }
  } finally {
    bookingSubmitting.value = false
  }
}
</script>

<template>
  <AppLayout :hide-nav="hideUiElements" :hide-breadcrumb="hideUiElements">
    <Head :title="course.name" />

    <!-- Preview Mode Banner -->
    <div
      v-if="isPreviewMode"
      class="bg-blue-600 text-white text-center py-3 px-4"
    >
      <div class="flex items-center justify-center gap-2">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
        </svg>
        <span class="font-medium">預覽模式 - 此課程尚未上架，僅管理員可見</span>
      </div>
    </div>

    <!-- Back link -->
    <div v-if="!hideUiElements" class="py-3 px-4 sm:px-6">
      <Link href="/" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        返回課程列表
      </Link>
    </div>

    <!-- ============================================================ -->
    <!-- 1. Title (above video, navy bg)                              -->
    <!-- ============================================================ -->
    <div class="bg-[#373557] px-6 pt-8 pb-6 text-center">
      <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-white max-w-3xl mx-auto leading-tight">
        {{ course.name }}
      </h1>
      <p v-if="course.duration_formatted" class="mt-3 text-blue-200 text-sm">
        {{ course.duration_formatted }}
      </p>
      <div v-if="course.status === 'preorder'" class="mt-3">
        <span class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-medium bg-yellow-100 text-yellow-800">
          預購中
        </span>
      </div>
    </div>

    <!-- ============================================================ -->
    <!-- 2. Thumbnail — full-width hero with bottom fade              -->
    <!-- ============================================================ -->
    <div class="relative w-full">
      <!-- Image: full bleed, fixed height -->
      <div class="relative w-full h-56 sm:h-72 md:h-96 lg:h-[480px] bg-gray-900 overflow-hidden">
        <img
          v-if="course.thumbnail"
          :src="course.thumbnail"
          :alt="course.name"
          class="w-full h-full object-cover"
        />
        <div
          v-else
          class="w-full h-full flex items-center justify-center text-gray-400"
        >
          <svg class="w-16 h-16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
          </svg>
        </div>
        <!-- Bottom gradient: image fades into cream background -->
        <div class="absolute bottom-0 left-0 right-0 h-24 bg-gradient-to-t from-[#F6F1E9] to-transparent pointer-events-none"></div>
      </div>
    </div>

    <!-- ============================================================ -->
    <!-- Drip subscription success notification                       -->
    <!-- ============================================================ -->
    <div v-if="isDrip && $page.props.flash?.drip_subscribed" class="bg-brand-cream px-4 pt-6 pb-2">
      <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-xl border border-green-100 shadow-sm px-6 py-6 text-center">
          <div class="mx-auto w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
            <svg class="w-6 h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-gray-900 mb-2">訂閱成功</h3>
          <p class="text-gray-600">
            課程已寄送到您的信箱 <strong class="text-gray-800">{{ $page.props.auth?.user?.email }}</strong>，請去收取歡迎信！<br>
            如找不到，有可能在「促銷」或「廣告」信箱，記得加入白名單避免漏信。
          </p>
        </div>
      </div>
    </div>

    <!-- ============================================================ -->
    <!-- 3. Info row + quick buy (directly below video, no gap)       -->
    <!-- ============================================================ -->
    <div ref="topInfoRef" class="bg-white px-4 sm:px-6 py-5 border-b border-gray-100">
      <div class="max-w-3xl mx-auto flex flex-col sm:flex-row sm:items-start gap-6">

        <!-- Left: Course info -->
        <div class="flex-1">
          <h3 class="text-sm font-semibold text-gray-700 border-l-4 border-brand-teal pl-2 mb-4">課程資訊</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-3 text-sm text-gray-600">
            <!-- Type -->
            <div class="flex items-center gap-2">
              <span class="w-7 h-7 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-brand-teal" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-5 5a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 10V5a2 2 0 012-2z" />
                </svg>
              </span>
              <span>課程類型　<strong class="text-gray-800">{{ getTypeLabel(course.product_type) }}</strong></span>
            </div>
            <!-- Duration -->
            <div v-if="course.duration_formatted" class="flex items-center gap-2">
              <span class="w-7 h-7 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-brand-teal" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </span>
              <span>預計時長　<strong class="text-gray-800">{{ course.duration_formatted }}</strong></span>
            </div>
            <!-- Instructor -->
            <div class="flex items-center gap-2">
              <span class="w-7 h-7 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-brand-teal" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
              </span>
              <span>授課講師　<strong class="text-gray-800">{{ course.instructor_name }}</strong></span>
            </div>
            <!-- Access limit (static) -->
            <div class="flex items-center gap-2">
              <span class="w-7 h-7 rounded-full bg-blue-50 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-brand-teal" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
              </span>
              <span>觀看限制　<strong class="text-gray-800">不限時間、次數</strong></span>
            </div>
            <!-- Status: preorder -->
            <div v-if="course.status === 'preorder'" class="flex items-center gap-2">
              <span class="w-7 h-7 rounded-lg bg-yellow-50 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
              </span>
              <span>目前狀態　<strong class="text-yellow-700">預購中</strong></span>
            </div>
          </div>
        </div>

        <!-- Right: Quick scroll-to-purchase button -->
        <div class="flex flex-col items-center sm:items-end gap-3 shrink-0 sm:pt-1">
          <PriceDisplay
            v-if="!isHighTicket || !highTicketHidePrice"
            :price="course.price"
            :original-price="course.original_price"
            :promo-ends-at="course.promo_ends_at"
          />
          <div class="flex flex-row items-center gap-2 w-full sm:w-auto">
            <a
              v-if="hasPreviewLessons && !isDrip && !isPreviewMode"
              :href="`/course/${course.id}/preview`"
              target="_blank"
              rel="noopener noreferrer"
              class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1.5 px-5 py-3 rounded-lg font-semibold border border-brand-teal text-brand-teal hover:bg-brand-teal/10 transition-all"
            >
              <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
              </svg>
              免費試閱
            </a>
            <a
              v-if="hasPurchased && !isDrip"
              href="/member/learning"
              class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1.5 px-8 py-3 rounded-lg font-semibold bg-brand-teal hover:bg-brand-teal/80 text-white transition-all shadow-sm"
            >
              <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              前往學習
            </a>
            <button
              v-else
              @click="isFree ? openFreeForm() : purchaseSectionRef?.scrollIntoView({ behavior: 'smooth', block: 'center' })"
              class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1.5 px-8 py-3 rounded-lg font-semibold bg-brand-gold hover:bg-brand-gold-dark text-brand-navy border border-brand-gold-dark/50 transition-all shadow-sm cursor-pointer"
            >
              <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
              {{ isDrip ? '免費訂閱' : (isHighTicket && highTicketHidePrice ? '立即預約' : (isFree ? '免費報名' : '立即購買')) }}
            </button>
          </div>
        </div>

      </div>
    </div>

    <!-- ============================================================ -->
    <!-- 4. Course description (h2 headings break out to full width)  -->
    <!-- ============================================================ -->
    <div class="bg-white pb-10 overflow-x-hidden">
      <div class="max-w-4xl mx-auto px-4 sm:px-6">
        <div
          v-if="course.description_md"
          class="course-content"
          v-html="renderedDescription"
        />
        <div v-else class="course-content">
          <p class="whitespace-pre-line">{{ course.description }}</p>
        </div>
      </div>
    </div>

    <!-- ============================================================ -->
    <!-- 6a. Drip subscription section                                -->
    <!-- ============================================================ -->
    <div v-if="isDrip" class="bg-brand-cream py-8 px-4 border-t border-gray-200">
      <div class="max-w-2xl mx-auto">
        <!-- Already subscribed -->
        <div v-if="userSubscription" class="text-center py-6">
          <div class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium"
            :class="{
              'bg-green-100 text-green-800': userSubscription === 'active',
              'bg-blue-100 text-blue-800': userSubscription === 'converted',
              'bg-gray-100 text-gray-800': userSubscription === 'completed',
              'bg-red-100 text-red-800': userSubscription === 'unsubscribed',
            }"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            {{ subscriptionStatusLabel }}
          </div>
          <p v-if="userSubscription === 'active'" class="mt-2 text-sm text-gray-600">
            <Link :href="`/member/classroom/${course.id}`" class="text-indigo-600 hover:underline">
              前往教室
            </Link>
          </p>
        </div>

        <!-- Can subscribe: logged-in member -->
        <div v-else-if="canSubscribe && $page.props.auth.user" class="text-center py-6">
          <p v-if="subscribeErrors.subscribe" class="mb-3 text-sm text-red-600">{{ subscribeErrors.subscribe }}</p>
          <div class="mb-4 max-w-xs mx-auto text-left">
            <label class="block text-sm font-medium text-gray-700 mb-1">暱稱（必填）</label>
            <input
              v-model="memberNickname"
              type="text"
              placeholder="請輸入您的暱稱"
              maxlength="50"
              class="block w-full rounded-lg border-gray-300 px-4 py-3 text-base shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              :class="{ 'border-red-300': subscribeErrors.nickname }"
            />
            <p v-if="subscribeErrors.nickname" class="mt-1 text-sm text-red-600">{{ subscribeErrors.nickname }}</p>
          </div>
          <button
            @click="memberSubscribe"
            :disabled="subscribing || !memberNickname.trim()"
            class="px-10 py-3 bg-brand-gold hover:bg-brand-gold-dark text-brand-navy border border-brand-gold-dark/50 rounded-lg font-semibold transition-all hover:shadow-md active:scale-[0.98] cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ subscribing ? '訂閱中...' : (Number(course.price) > 0 ? '立即購買' : '免費訂閱') }}
          </button>
        </div>

        <!-- Can subscribe: guest -->
        <div v-else-if="canSubscribe">
          <DripSubscribeForm :course-id="course.id" />
        </div>
      </div>
    </div>

    <!-- ============================================================ -->
    <!-- 6b. Standard purchase section                                -->
    <!-- ============================================================ -->
    <div v-else ref="purchaseSectionRef" class="bg-brand-cream py-8 px-4 border-t border-gray-200">
      <div class="max-w-4xl mx-auto">

        <!-- ── Free enrollment success ── -->
        <div v-if="freeSuccess" class="bg-white rounded-xl border border-green-100 shadow-sm px-6 py-6 text-center max-w-lg mx-auto">
          <div class="mx-auto w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
            <svg class="w-6 h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-gray-900 mb-2">報名成功！</h3>
          <p class="text-gray-600 mb-5">已成功取得課程，前往「我的課程」開始學習。</p>
          <a href="/member/learning" class="inline-flex items-center justify-center px-6 py-3 rounded-lg font-semibold bg-brand-gold hover:bg-brand-gold-dark text-brand-navy border border-brand-gold-dark/50 transition-all shadow-sm">
            前往我的課程
          </a>
        </div>

        <!-- ── Already purchased ── -->
        <div v-else-if="hasPurchased" class="text-center py-6">
          <div class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium bg-green-100 text-green-800 mb-4">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            您已購買此課程
          </div>
          <div>
            <a
              href="/member/learning"
              class="inline-flex items-center justify-center gap-1.5 px-8 py-3 rounded-lg font-semibold bg-brand-teal hover:bg-brand-teal/80 text-white transition-all shadow-sm"
            >
              <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              前往學習
            </a>
          </div>
        </div>

        <!-- ── Normal purchase UI ── -->
        <div v-else class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-6">
          <!-- Left: High Ticket Info callout -->
          <div v-if="isHighTicket && highTicketHidePrice" class="flex-1">
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-5">
              <div class="flex items-center gap-2 mb-3">
                <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-sm font-bold text-amber-800">預約須知</span>
              </div>
              <p class="text-sm text-amber-900 leading-relaxed">
                此為客製服務，請預約 1v1 面談了解。
              </p>
              <p class="mt-2 text-sm font-semibold text-amber-900 leading-relaxed">
                預約後，請立即收取 Email 並按照指示完成後續任務，直到收到確認信，才是正式完成預約。
              </p>
            </div>
          </div>
          <div v-else class="py-1">
            <PriceDisplay
              :price="course.price"
              :original-price="course.original_price"
              :promo-ends-at="course.promo_ends_at"
            />
          </div>

          <!-- Right: High Ticket booking form (replaces Consent & Purchase Button) -->
          <div v-if="isHighTicket && highTicketHidePrice" class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm w-full sm:w-80 shrink-0">
            <!-- Success state -->
            <div v-if="bookingSuccess" class="text-center py-2">
              <div class="mx-auto w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mb-3">
                <svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
              </div>
              <p class="text-sm font-semibold text-gray-900 mb-1">預約申請已送出！</p>
              <p class="text-sm text-gray-600">通知信已寄出，請立即前往收件匣查收並按照信中指示完成後續步驟。</p>
            </div>
            <!-- Form state -->
            <template v-else>
              <h3 class="text-base font-semibold text-gray-900 mb-4">預約 1v1 面談</h3>
              <div class="space-y-4">
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-1">姓名 <span class="text-red-500">*</span></label>
                  <input
                    v-model="bookingName"
                    type="text"
                    placeholder="請輸入姓名"
                    class="block w-full rounded-lg border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-brand-teal focus:ring-brand-teal"
                    :class="{ 'border-red-300': bookingErrors.name }"
                  />
                  <p v-if="bookingErrors.name" class="mt-1 text-sm text-red-600">{{ bookingErrors.name[0] ?? bookingErrors.name }}</p>
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-1">Email <span class="text-red-500">*</span></label>
                  <input
                    v-model="bookingEmail"
                    type="email"
                    placeholder="your@email.com"
                    class="block w-full rounded-lg border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-brand-teal focus:ring-brand-teal"
                    :class="{ 'border-red-300': bookingErrors.email }"
                  />
                  <p v-if="bookingErrors.email" class="mt-1 text-sm text-red-600">{{ bookingErrors.email[0] ?? bookingErrors.email }}</p>
                </div>
                <p v-if="bookingErrors.booking" class="text-sm text-red-600">{{ bookingErrors.booking }}</p>
                <button
                  @click="submitBooking"
                  :disabled="bookingSubmitting || !bookingName || !bookingEmail"
                  class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 rounded-lg font-semibold bg-brand-gold hover:bg-brand-gold-dark text-brand-navy border border-brand-gold-dark/50 transition-all shadow-sm cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <svg v-if="bookingSubmitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                  </svg>
                  {{ bookingSubmitting ? '送出中...' : '送出預約' }}
                </button>
              </div>
            </template>
          </div>

          <!-- Cart / Purchase Buttons -->
          <div v-else class="flex flex-col gap-3 sm:items-end w-full sm:w-auto">
            <div v-if="isPreviewMode" class="text-sm text-gray-500 bg-white px-3 py-2 rounded border border-gray-200">
              草稿課程，僅供預覽
            </div>

            <!-- Free form: inline enrollment -->
            <div v-if="isFree && showFreeForm && !isPreviewMode" class="w-full sm:w-80 bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
              <h4 class="text-sm font-semibold text-gray-800 mb-3">填寫資料完成免費報名</h4>
              <div class="space-y-3">
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-1">Email <span class="text-red-500">*</span></label>
                  <input
                    v-model="freeFormEmail"
                    type="email"
                    placeholder="your@email.com"
                    :readonly="!!$page.props.auth?.user"
                    class="block w-full rounded-lg border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-brand-teal focus:ring-brand-teal"
                    :class="{ 'bg-gray-50': !!$page.props.auth?.user }"
                  />
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-1">姓名 <span class="text-red-500">*</span></label>
                  <input
                    v-model="freeFormName"
                    type="text"
                    placeholder="請輸入姓名"
                    class="block w-full rounded-lg border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-brand-teal focus:ring-brand-teal"
                  />
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-1">電話 <span class="text-red-500">*</span></label>
                  <input
                    v-model="freeFormPhone"
                    type="tel"
                    placeholder="0912345678"
                    class="block w-full rounded-lg border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-brand-teal focus:ring-brand-teal"
                  />
                </div>
                <!-- Confirmation notice for guests -->
                <div v-if="showFreeConfirm" class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg p-3">
                  請確認資料正確——Email 將作為登入帳號，確認後點「送出報名」完成報名。
                </div>
                <p v-if="freeError" class="text-sm text-red-600">{{ freeError }}</p>
                <button
                  @click="submitFreeEnrollment"
                  :disabled="freeSubmitting || !freeFormEmail || !freeFormName || !freeFormPhone"
                  class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg font-semibold bg-brand-gold hover:bg-brand-gold-dark text-brand-navy border border-brand-gold-dark/50 transition-all shadow-sm cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed text-sm"
                >
                  <svg v-if="freeSubmitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                  </svg>
                  {{ freeSubmitting ? '報名中...' : (showFreeConfirm ? '送出報名' : '確認並報名') }}
                </button>
              </div>
            </div>

            <p v-if="cartAddError" class="text-sm text-red-600">{{ cartAddError }}</p>

            <div class="flex flex-row items-center gap-2 w-full sm:w-auto">
              <a
                v-if="hasPreviewLessons && !isDrip && !isPreviewMode"
                :href="`/course/${course.id}/preview`"
                target="_blank"
                rel="noopener noreferrer"
                class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1.5 px-7 py-3 rounded-lg font-semibold border border-brand-teal text-brand-teal hover:bg-brand-teal/10 transition-all"
              >
                <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
                </svg>
                免費試閱
              </a>

              <!-- Portaly course: external buy link (US2) -->
              <a
                v-if="course.portaly_product_id && !isPreviewMode"
                :href="portalyUrl"
                target="_blank"
                rel="noopener noreferrer"
                class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1.5 px-10 py-3 rounded-lg font-semibold bg-brand-gold hover:bg-brand-gold-dark text-brand-navy border border-brand-gold-dark/50 hover:shadow-md active:scale-[0.98] cursor-pointer transition-all shadow-sm"
              >
                立即購買
              </a>

              <!-- Free course: enroll button -->
              <button
                v-else-if="isFree"
                @click="openFreeForm"
                :disabled="isPreviewMode"
                class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1.5 px-10 py-3 rounded-lg font-semibold bg-brand-gold hover:bg-brand-gold-dark text-brand-navy border border-brand-gold-dark/50 hover:shadow-md active:scale-[0.98] cursor-pointer transition-all shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
              >
                免費報名
              </button>

              <!-- Paid cart-flow course: owned / in-cart / add -->
              <template v-else-if="!isPreviewMode">
                <Link
                  v-if="isOwned"
                  href="/member/learning"
                  class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1.5 px-10 py-3 rounded-lg font-semibold bg-brand-teal text-white hover:bg-brand-teal/80 transition-all shadow-sm"
                >
                  進入課程
                </Link>
                <Link
                  v-else-if="isInCartLocal"
                  href="/cart"
                  class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1.5 px-10 py-3 rounded-lg font-semibold bg-brand-gold hover:bg-brand-gold-dark text-brand-navy border border-brand-gold-dark/50 hover:shadow-md active:scale-[0.98] cursor-pointer transition-all shadow-sm"
                >
                  前往購物車
                </Link>
                <template v-else>
                  <button
                    @click="handleAddToCart"
                    class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1.5 px-7 py-3 rounded-lg font-semibold bg-brand-gold hover:bg-brand-gold-dark text-brand-navy border border-brand-gold-dark/50 hover:shadow-md active:scale-[0.98] cursor-pointer transition-all shadow-sm"
                  >
                    加入購物車
                  </button>
                  <button
                    @click="handleBuyNow"
                    class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1.5 px-7 py-3 rounded-lg font-semibold bg-brand-gold hover:bg-brand-gold-dark text-brand-navy border border-brand-gold-dark/50 hover:shadow-md active:scale-[0.98] cursor-pointer transition-all shadow-sm"
                  >
                    直接購買
                  </button>
                </template>
              </template>

              <!-- Preview mode placeholder -->
              <button
                v-else
                disabled
                class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1.5 px-10 py-3 rounded-lg font-semibold bg-gray-300 text-gray-500 cursor-not-allowed transition-all"
              >
                預覽購買按鈕
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>


    <!-- Floating buy panel (visible when info section scrolls out of view) -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition-all duration-300"
        enter-from-class="translate-x-full opacity-0"
        enter-to-class="translate-x-0 opacity-100"
        leave-active-class="transition-all duration-300"
        leave-from-class="translate-x-0 opacity-100"
        leave-to-class="translate-x-full opacity-0"
      >
        <div
          v-if="showFloatingPanel && !isDrip && !isPreviewMode && (hasBuyAction || (isHighTicket && highTicketHidePrice)) && !hasPurchased"
          class="fixed right-4 bottom-6 z-40 w-60 bg-white rounded-2xl shadow-2xl border border-gray-100 p-4"
        >
          <PriceDisplay
            v-if="!isHighTicket || !highTicketHidePrice"
            :price="course.price"
            :original-price="course.original_price"
            :promo-ends-at="course.promo_ends_at"
          />
          <div class="mt-3 flex flex-col gap-2">
            <a
              v-if="hasPreviewLessons"
              :href="`/course/${course.id}/preview`"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-lg font-semibold border border-brand-teal text-brand-teal hover:bg-brand-teal/10 transition-all text-sm"
            >
              <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
              </svg>
              免費試閱
            </a>
            <a
              v-if="course.portaly_product_id"
              :href="portalyUrl"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-lg font-semibold bg-brand-gold hover:bg-brand-gold-dark text-brand-navy border border-brand-gold-dark/50 transition-all shadow-sm cursor-pointer text-sm"
            >
              立即購買
            </a>
            <Link
              v-else-if="isOwned"
              href="/member/learning"
              class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-lg font-semibold bg-brand-teal text-white hover:bg-brand-teal/80 transition-all shadow-sm text-sm"
            >
              進入課程
            </Link>
            <Link
              v-else-if="isInCartLocal"
              href="/cart"
              class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-lg font-semibold bg-brand-gold hover:bg-brand-gold-dark text-brand-navy border border-brand-gold-dark/50 transition-all shadow-sm cursor-pointer text-sm"
            >
              前往購物車
            </Link>
            <button
              v-else
              @click="isFree ? openFreeForm() : purchaseSectionRef?.scrollIntoView({ behavior: 'smooth', block: 'center' })"
              class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 rounded-lg font-semibold bg-brand-gold hover:bg-brand-gold-dark text-brand-navy border border-brand-gold-dark/50 transition-all shadow-sm cursor-pointer text-sm"
            >
              {{ isFree ? '免費報名' : '立即購買' }}
            </button>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Legal Policy Modal -->
    <LegalPolicyModal
      :show="showLegalModal"
      :type="legalModalType"
      @close="closeLegalModal"
    />

    <!-- Preview Alert Modal -->
    <Teleport to="body">
      <div
        v-if="showPreviewAlert"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
      >
        <div class="absolute inset-0 bg-black/50" @click="closePreviewAlert" />
        <div class="relative bg-white rounded-lg shadow-xl max-w-sm w-full p-6">
          <div class="text-center">
            <div class="mx-auto w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center mb-4">
              <svg class="w-6 h-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">草稿課程</h3>
            <p class="text-gray-600 mb-6">此為草稿課程，僅供預覽。正式上架後才能進行購買。</p>
            <button
              @click="closePreviewAlert"
              class="w-full px-4 py-2 bg-brand-teal text-white rounded-lg hover:bg-brand-teal/80 transition-colors font-medium"
            >
              我知道了
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </AppLayout>
</template>
