<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'

const page = usePage()
const flash = computed(() => page.props.flash)
const user = computed(() => page.props.auth?.user)

const sidebarOpen = ref(false)

watch(
  () => flash.value,
  (newFlash) => {
    if (newFlash?.success || newFlash?.error) {
      setTimeout(() => {
        page.props.flash = { success: null, error: null }
      }, 5000)
    }
  },
  { immediate: true }
)

const navigation = [
  { name: 'Dashboard', href: '/admin', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
  { name: '首頁設定', href: '/admin/homepage', icon: 'M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25' },
  { name: '課程管理', href: '/admin/courses', icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253' },
  { name: '會員管理', href: '/admin/members', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' },
  { name: '交易紀錄', href: '/admin/transactions', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01' },
  { name: 'Email 模板', href: '/admin/email-templates', icon: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' },
  { name: 'Leads 名單', href: '/admin/high-ticket-leads', icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' },
]

const isActive = (href) => {
  const currentPath = page.url
  if (href === '/admin') {
    return currentPath === '/admin'
  }
  return currentPath.startsWith(href)
}
</script>

<template>
  <div class="min-h-screen bg-gray-100">
    <!-- Mobile sidebar backdrop -->
    <div
      v-if="sidebarOpen"
      class="fixed inset-0 z-40 bg-gray-600 bg-opacity-75 lg:hidden"
      @click="sidebarOpen = false"
    />

    <!-- Mobile sidebar -->
    <div
      class="fixed inset-y-0 left-0 z-50 w-64 bg-indigo-700 transform transition-transform duration-300 ease-in-out lg:hidden"
      :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    >
      <div class="flex items-center justify-between h-16 px-4 bg-indigo-800">
        <span class="text-xl font-semibold text-white">Admin</span>
        <button
          type="button"
          class="text-indigo-200 hover:text-white"
          @click="sidebarOpen = false"
        >
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
      <nav class="mt-5 px-2 space-y-1">
        <Link
          v-for="item in navigation"
          :key="item.name"
          :href="item.href"
          class="group flex items-center px-2 py-2 text-base font-medium rounded-md"
          :class="isActive(item.href)
            ? 'bg-indigo-800 text-white'
            : 'text-indigo-100 hover:bg-indigo-600'"
        >
          <svg class="mr-4 h-6 w-6 text-indigo-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
          </svg>
          {{ item.name }}
        </Link>
      </nav>
    </div>

    <!-- Desktop sidebar -->
    <div class="hidden lg:flex lg:w-64 lg:flex-col lg:fixed lg:inset-y-0">
      <div class="flex flex-col flex-grow bg-indigo-700 pt-5 pb-4 overflow-y-auto">
        <div class="flex items-center flex-shrink-0 px-4">
          <span class="text-xl font-semibold text-white">Admin</span>
        </div>
        <nav class="mt-8 flex-1 px-2 space-y-1">
          <Link
            v-for="item in navigation"
            :key="item.name"
            :href="item.href"
            class="group flex items-center px-2 py-2 text-sm font-medium rounded-md"
            :class="isActive(item.href)
              ? 'bg-indigo-800 text-white'
              : 'text-indigo-100 hover:bg-indigo-600'"
          >
            <svg class="mr-3 h-6 w-6 text-indigo-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
            </svg>
            {{ item.name }}
          </Link>
        </nav>
        <div class="flex-shrink-0 flex border-t border-indigo-800 p-4">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="h-8 w-8 rounded-full bg-indigo-500 flex items-center justify-center">
                <span class="text-sm font-medium text-white">{{ user?.nickname?.[0] || 'A' }}</span>
              </div>
            </div>
            <div class="ml-3">
              <p class="text-sm font-medium text-white">{{ user?.nickname || 'Admin' }}</p>
              <Link href="/" class="text-xs font-medium text-indigo-200 hover:text-white">
                返回前台
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main content -->
    <div class="lg:pl-64 flex flex-col flex-1">
      <!-- Top bar -->
      <div class="sticky top-0 z-10 flex-shrink-0 flex h-16 bg-white shadow">
        <button
          type="button"
          class="px-4 border-r border-gray-200 text-gray-500 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500 lg:hidden"
          @click="sidebarOpen = true"
        >
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
          </svg>
        </button>
        <div class="flex-1 px-4 flex justify-between items-center">
          <div class="flex-1" />
          <div class="ml-4 flex items-center">
            <Link
              href="/logout"
              method="post"
              as="button"
              class="text-gray-500 hover:text-gray-700 text-sm"
            >
              登出
            </Link>
          </div>
        </div>
      </div>

      <!-- Flash Messages -->
      <div v-if="flash?.success" class="fixed top-20 right-4 z-50 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg">
        {{ flash.success }}
      </div>
      <div v-if="flash?.error" class="fixed top-20 right-4 z-50 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg">
        {{ flash.error }}
      </div>

      <main class="flex-1">
        <div class="py-6">
          <slot />
        </div>
      </main>
    </div>
  </div>
</template>
