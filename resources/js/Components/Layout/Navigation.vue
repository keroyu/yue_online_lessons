<script setup>
import { Link, usePage, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { useCart } from '@/composables/useCart'
import { useNotifications } from '@/composables/useNotifications'

const page = usePage()
const auth = computed(() => page.props.auth)
const user = computed(() => auth.value?.user)
const mobileMenuOpen = ref(false)
const notificationOpen = ref(false)

const { cartCount } = useCart()
const { notificationCount, notifications, markRead } = useNotifications()

const logout = () => {
  router.post('/logout')
}

const goToNotification = (notification) => {
  notificationOpen.value = false
  const dest = `/member/classroom/${notification.course_id}?lesson_id=${notification.lesson_id}`
  if (!notification.is_read) {
    markRead(notification.id, { onSuccess: () => router.visit(dest) })
  } else {
    router.visit(dest)
  }
}

const formatNotificationTime = (d) => {
  if (!d) return ''
  const date = new Date(d)
  return date.toLocaleString('zh-TW', { month: 'numeric', day: 'numeric', hour: '2-digit', minute: '2-digit' })
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

          <!-- Notification bell (all logged-in users) -->
          <div v-if="user" class="relative">
            <button
              class="relative text-white/80 hover:text-brand-teal p-2 transition-colors"
              @click="notificationOpen = !notificationOpen"
            >
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
              </svg>
              <span
                v-if="notificationCount > 0"
                class="absolute -top-0.5 -right-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-white text-[10px] font-bold"
              >
                {{ notificationCount > 9 ? '9+' : notificationCount }}
              </span>
            </button>

            <!-- Dropdown -->
            <div v-show="notificationOpen" class="absolute right-0 top-full mt-1 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50 overflow-hidden">
              <div class="px-4 py-2.5 border-b border-gray-100 flex items-center justify-between">
                <span class="text-sm font-semibold text-gray-700">作業通知</span>
                <button class="text-xs text-gray-400 hover:text-gray-600" @click="notificationOpen = false">關閉</button>
              </div>
              <div v-if="notifications.length === 0" class="px-4 py-6 text-center text-sm text-gray-400">
                目前沒有通知
              </div>
              <ul v-else class="divide-y divide-gray-100 max-h-72 overflow-y-auto">
                <li
                  v-for="n in notifications"
                  :key="n.id"
                  class="px-4 py-3 hover:bg-gray-50 cursor-pointer transition-colors"
                  :class="!n.is_read ? 'bg-indigo-50/60' : ''"
                  @click="goToNotification(n)"
                >
                  <div class="flex items-start gap-2">
                    <span v-if="!n.is_read" class="mt-1.5 flex-shrink-0 h-2 w-2 rounded-full bg-indigo-500" />
                    <span v-else class="mt-1.5 flex-shrink-0 h-2 w-2" />
                    <div class="flex-1 min-w-0">
                      <p class="text-sm text-gray-800 leading-snug">{{ n.message }}</p>
                      <p class="text-xs text-gray-400 mt-0.5">{{ formatNotificationTime(n.created_at) }}</p>
                    </div>
                  </div>
                </li>
              </ul>
            </div>
          </div>

          <!-- Click-outside backdrop -->
          <div v-if="notificationOpen" class="fixed inset-0 z-40" @click="notificationOpen = false" />

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
          <!-- Mobile notifications -->
          <div class="px-3 py-2">
            <div class="flex items-center gap-2 mb-2">
              <span class="text-white/80 text-base font-medium">
                通知
              </span>
              <span v-if="notificationCount > 0" class="flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-white text-[10px] font-bold">
                {{ notificationCount > 9 ? '9+' : notificationCount }}
              </span>
            </div>
            <div v-if="notifications.length === 0" class="text-sm text-white/50 pl-1">
              目前沒有通知
            </div>
            <ul v-else class="space-y-1">
              <li
                v-for="n in notifications"
                :key="n.id"
                class="text-sm text-white/70 hover:text-brand-teal cursor-pointer py-1 pl-1 transition-colors"
                :class="!n.is_read ? 'font-medium text-white/90' : ''"
                @click="goToNotification(n); mobileMenuOpen = false"
              >
                {{ n.message }}
              </li>
            </ul>
          </div>
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
