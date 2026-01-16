<script setup>
import { Link, usePage, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

const page = usePage()
const auth = computed(() => page.props.auth)
const user = computed(() => auth.value?.user)
const mobileMenuOpen = ref(false)

const logout = () => {
  router.post('/logout')
}
</script>

<template>
  <nav class="bg-white shadow-sm border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-16">
        <!-- Logo -->
        <div class="flex items-center">
          <Link href="/" class="text-xl font-bold text-gray-900">
            YUE Lessons
          </Link>
        </div>

        <!-- Desktop Navigation -->
        <div class="hidden sm:flex sm:items-center sm:space-x-4">
          <Link href="/" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
            課程
          </Link>

          <template v-if="user">
            <Link href="/member/learning" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
              我的課程
            </Link>
            <Link href="/member/settings" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
              帳號設定
            </Link>
            <button
              @click="logout"
              class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium"
            >
              登出
            </button>
          </template>
          <template v-else>
            <Link
              href="/login"
              class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-indigo-700"
            >
              登入
            </Link>
          </template>
        </div>

        <!-- Mobile menu button -->
        <div class="sm:hidden flex items-center">
          <button
            @click="mobileMenuOpen = !mobileMenuOpen"
            class="text-gray-600 hover:text-gray-900 p-2"
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
    <div v-show="mobileMenuOpen" class="sm:hidden border-t border-gray-100">
      <div class="px-2 pt-2 pb-3 space-y-1">
        <Link href="/" class="block text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-base font-medium">
          課程
        </Link>

        <template v-if="user">
          <Link href="/member/learning" class="block text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-base font-medium">
            我的課程
          </Link>
          <Link href="/member/settings" class="block text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-base font-medium">
            帳號設定
          </Link>
          <button
            @click="logout"
            class="block w-full text-left text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-base font-medium"
          >
            登出
          </button>
        </template>
        <template v-else>
          <Link
            href="/login"
            class="block bg-indigo-600 text-white px-3 py-2 rounded-md text-base font-medium hover:bg-indigo-700 text-center"
          >
            登入
          </Link>
        </template>
      </div>
    </div>
  </nav>
</template>
