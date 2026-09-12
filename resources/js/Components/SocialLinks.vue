<script setup>
import SectionHeader from '@/Components/SectionHeader.vue'
import { platformIcons, platformLabels } from '@/lib/socialPlatforms'

const props = defineProps({
  links: {
    type: Array,
    default: () => [],
  },
  // { intro } — owner intro shown above the links. Null hides it. The avatar
  // retired with 002 US20: the owner's picture lives in the hero now (FR-061).
  profile: {
    type: Object,
    default: null,
  },
})

</script>

<template>
  <div v-if="links.length > 0 || profile?.intro" class="bg-white border border-gray-200 p-4">
    <SectionHeader title="追蹤站長" />

    <!-- 站長介紹（有才顯示） -->
    <p v-if="profile?.intro" class="mb-4 text-sm text-gray-600 leading-relaxed whitespace-pre-line text-center">{{ profile.intro }}</p>

    <div v-if="links.length > 0" class="grid grid-cols-2 gap-2">
      <a
        v-for="link in links"
        :key="`${link.platform}-${link.url}`"
        :href="link.url"
        target="_blank"
        rel="noopener noreferrer"
        :aria-label="platformLabels[link.platform] ?? link.platform"
        class="flex items-center justify-center gap-2 px-3 py-2 border border-gray-200 text-gray-700 hover:border-brand-navy hover:bg-brand-navy hover:text-white text-sm font-medium transition-colors"
      >
        <span class="w-5 h-5" v-html="platformIcons[link.platform] ?? ''"></span>
        <span>{{ platformLabels[link.platform] ?? link.platform }}</span>
      </a>
    </div>
  </div>
</template>
