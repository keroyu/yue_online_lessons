<script setup>
import SectionHeader from '@/Components/SectionHeader.vue'
import { platformIcons, platformLabels } from '@/lib/socialPlatforms'

const props = defineProps({
  links: {
    type: Array,
    default: () => [],
  },
  // { image_url, intro } — avatar + intro shown above the links. Null hides them.
  profile: {
    type: Object,
    default: null,
  },
})

</script>

<template>
  <div v-if="links.length > 0 || profile?.image_url || profile?.intro" class="bg-white border border-gray-200 p-4">
    <SectionHeader title="追蹤站長" />

    <!-- 站長形象＋介紹（各自有才顯示） -->
    <div v-if="profile?.image_url || profile?.intro" class="mb-4">
      <img
        v-if="profile.image_url"
        :src="profile.image_url"
        alt="站長形象"
        class="w-full h-auto rounded-xl object-cover"
      />
      <p v-if="profile.intro" class="mt-3 text-sm text-gray-600 leading-relaxed whitespace-pre-line text-center">{{ profile.intro }}</p>
    </div>

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
