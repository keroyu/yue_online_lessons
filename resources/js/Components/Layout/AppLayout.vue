<script setup>
import Navigation from './Navigation.vue'
import Footer from './Footer.vue'
import { useFlash } from '@/composables/useFlash'

defineProps({
  hideNav: { type: Boolean, default: false },
  hideBreadcrumb: { type: Boolean, default: false }
})

const { flash } = useFlash()
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <Navigation v-if="!hideNav" />

    <!-- Flash Messages -->
    <div v-if="flash?.success" class="fixed top-4 right-4 z-50 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg">
      {{ flash.success }}
    </div>
    <div v-if="flash?.error" class="fixed top-4 right-4 z-50 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg">
      {{ flash.error }}
    </div>

    <main class="flex-1">
      <slot />
    </main>

    <Footer />
  </div>
</template>
