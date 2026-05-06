<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

defineOptions({ layout: false })

const props = defineProps({
  items: { type: Array, default: () => [] },
  total: { type: Number, default: 0 },
})

const page = usePage()
const isAuthenticated = computed(() => !!page.props.auth?.user)

// Guest cart loaded from localStorage
const guestItems = ref([])
const guestTotal = computed(() => guestItems.value.reduce((sum, i) => sum + (Number(i.price) || 0), 0))

const displayItems = computed(() =>
  isAuthenticated.value ? props.items : guestItems.value.map(i => ({
    id: i.id,
    course: { id: i.id, name: i.name, price: i.price, thumbnail: i.thumbnail ?? null },
  }))
)
const displayTotal = computed(() =>
  isAuthenticated.value ? props.total : guestTotal.value
)

const paymentFailed = computed(() => page.props.payment_failed ?? null)

onMounted(() => {
  if (!isAuthenticated.value) {
    try {
      guestItems.value = JSON.parse(localStorage.getItem('guest_cart') || '[]')
    } catch {
      guestItems.value = []
    }
  }

  // Meta Pixel InitiateCheckout
  if (window.fbq && displayItems.value.length > 0) {
    window.fbq('track', 'InitiateCheckout', {
      num_items: displayItems.value.length,
      value: displayTotal.value,
      currency: 'TWD',
    })
  }
})

const removing = ref(new Set())

const removeItem = async (item) => {
  if (removing.value.has(item.id)) return

  if (isAuthenticated.value) {
    removing.value.add(item.id)
    try {
      await window.axios.delete(`/api/cart/${item.course.id}`)
      router.reload({ only: ['items', 'total'] })
    } catch {
      // ignore
    } finally {
      removing.value.delete(item.id)
    }
  } else {
    try {
      const cart = JSON.parse(localStorage.getItem('guest_cart') || '[]')
      const updated = cart.filter(i => i.id !== item.course.id)
      localStorage.setItem('guest_cart', JSON.stringify(updated))
      guestItems.value = updated
    } catch {}
  }
}
</script>

<template>
  <AppLayout>
    <Head title="購物車" />

    <div class="max-w-3xl mx-auto px-4 py-10">
      <h1 class="text-2xl font-bold text-brand-navy mb-6">購物車</h1>

      <!-- Payment failed banner -->
      <div
        v-if="paymentFailed"
        class="mb-6 rounded-lg bg-red-50 border border-red-200 p-4 text-sm text-red-700"
      >
        {{ paymentFailed }}
      </div>

      <!-- Empty state -->
      <div v-if="displayItems.length === 0" class="text-center py-16 text-gray-500">
        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
        <p class="mb-4">購物車目前是空的</p>
        <Link href="/" class="text-brand-teal hover:underline font-medium">瀏覽課程</Link>
      </div>

      <!-- Item list -->
      <div v-else class="space-y-4">
        <div
          v-for="item in displayItems"
          :key="item.id"
          class="flex items-center gap-4 bg-white rounded-xl border border-gray-100 shadow-sm p-4"
        >
          <img
            v-if="item.course.thumbnail"
            :src="item.course.thumbnail"
            :alt="item.course.name"
            class="w-20 h-14 object-cover rounded-lg shrink-0"
          />
          <div v-else class="w-20 h-14 bg-gray-100 rounded-lg shrink-0" />

          <div class="flex-1 min-w-0">
            <p class="font-semibold text-brand-navy truncate">{{ item.course.name }}</p>
            <p class="text-brand-teal font-bold mt-0.5">NT$ {{ item.course.price?.toLocaleString() }}</p>
          </div>

          <button
            @click="removeItem(item)"
            :disabled="removing.has(item.id)"
            class="text-gray-400 hover:text-red-500 transition-colors disabled:opacity-40 shrink-0 p-1"
            title="移除"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Total + CTA -->
        <div class="mt-6 rounded-xl bg-brand-cream border border-gray-200 p-5">
          <div class="flex justify-between items-center mb-4">
            <span class="text-gray-600 font-medium">合計</span>
            <span class="text-xl font-bold text-brand-navy">NT$ {{ displayTotal.toLocaleString() }}</span>
          </div>
          <Link
            href="/checkout"
            class="block w-full text-center py-3 rounded-lg font-semibold bg-brand-gold hover:bg-brand-gold-dark text-brand-navy border border-brand-gold-dark/50 transition-all shadow-sm"
          >
            前往結帳
          </Link>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
