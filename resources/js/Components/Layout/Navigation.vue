<script setup>
import { Link, usePage, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const page = usePage()
const auth = computed(() => page.props.auth)
const user = computed(() => auth.value?.user)
const mobileMenuOpen = ref(false)

const cartCount = computed(() => {
  if (user.value) {
    return page.props.cartCount ?? 0
  }
  try {
    return JSON.parse(localStorage.getItem('guest_cart') || '[]').length
  } catch {
    return 0
  }
})

const logout = () => {
  router.post('/logout')
}
</script>

<template>
  <nav class="bg-brand-navy shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-16">
        <!-- Logo -->
        <div class="flex items-center">
          <Link href="/" class="text-xl font-bold text-white">
            Your Time Bank
          </Link>
        </div>

        <!-- Desktop Navigation -->
        <div class="hidden sm:flex sm:items-center sm:space-x-4">
          <Link href="/" class="text-white/80 hover:text-brand-teal px-3 py-2 rounded-md text-sm font-medium transition-colors">
            首頁
          </Link>

          <!-- Cart icon with badge -->
          <Link href="/cart" class="relative text-white/80 hover:text-brand-teal p-2 transition-colors">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <span
              v-if="cartCount > 0"
              class="absolute -top-0.5 -right-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-brand-gold text-brand-navy text-[10px] font-bold"
            >
              {{ cartCount > 9 ? '9+' : cartCount }}
            </span>
          </Link>

          <template v-if="user">
            <Link href="/member/learning" class="text-white/80 hover:text-brand-teal px-3 py-2 rounded-md text-sm font-medium transition-colors">
              我的課程
            </Link>
            <Link href="/member/settings" class="text-white/80 hover:text-brand-teal px-3 py-2 rounded-md text-sm font-medium transition-colors">
              帳號設定
            </Link>
            <button
              @click="logout"
              class="text-white/80 hover:text-brand-teal px-3 py-2 rounded-md text-sm font-medium transition-colors"
            >
              登出
            </button>
          </template>
          <template v-else>
            <Link
              href="/login"
              class="bg-brand-teal text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-brand-teal/80 transition-colors"
            >
              登入
            </Link>
          </template>
        </div>

        <!-- Mobile menu button -->
        <div class="sm:hidden flex items-center">
          <button
            @click="mobileMenuOpen = !mobileMenuOpen"
            class="text-white/80 hover:text-brand-teal p-2 transition-colors"
          >
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path v-if="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
              <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Mobile menu -->
    <div v-show="mobileMenuOpen" class="sm:hidden border-t border-white/10">
      <div class="px-2 pt-2 pb-3 space-y-1">
        <Link href="/" class="block text-white/80 hover:text-brand-teal px-3 py-2 rounded-md text-base font-medium transition-colors">
          首頁
        </Link>
        <Link href="/cart" class="flex items-center gap-2 text-white/80 hover:text-brand-teal px-3 py-2 rounded-md text-base font-medium transition-colors">
          購物車
          <span
            v-if="cartCount > 0"
            class="flex h-5 w-5 items-center justify-center rounded-full bg-brand-gold text-brand-navy text-[10px] font-bold"
          >
            {{ cartCount > 9 ? '9+' : cartCount }}
          </span>
        </Link>

        <template v-if="user">
          <Link href="/member/learning" class="block text-white/80 hover:text-brand-teal px-3 py-2 rounded-md text-base font-medium transition-colors">
            我的課程
          </Link>
          <Link href="/member/settings" class="block text-white/80 hover:text-brand-teal px-3 py-2 rounded-md text-base font-medium transition-colors">
            帳號設定
          </Link>
          <button
            @click="logout"
            class="block w-full text-left text-white/80 hover:text-brand-teal px-3 py-2 rounded-md text-base font-medium transition-colors"
          >
            登出
          </button>
        </template>
        <template v-else>
          <Link
            href="/login"
            class="block bg-brand-teal text-white px-3 py-2 rounded-md text-base font-medium hover:bg-brand-teal/80 text-center transition-colors"
          >
            登入
          </Link>
        </template>
      </div>
    </div>
  </nav>
</template>
