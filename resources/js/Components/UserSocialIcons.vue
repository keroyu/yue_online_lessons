<script setup>
import { platformIcons, platformLabels } from '@/lib/socialPlatforms'

defineProps({
  // [{ platform, url }] — the member's self-added social links
  links: {
    type: Array,
    default: () => [],
  },
})
</script>

<template>
  <span v-if="links.length > 0" class="inline-flex items-center gap-1">
    <a
      v-for="(link, idx) in links"
      :key="idx"
      :href="link.url"
      target="_blank"
      rel="noopener noreferrer"
      :title="`${platformLabels[link.platform] ?? link.platform}：${link.url}`"
      :aria-label="platformLabels[link.platform] ?? link.platform"
      class="text-gray-400 hover:text-brand-teal transition-colors"
      @click.stop
    >
      <span class="block w-4 h-4" v-html="platformIcons[link.platform] ?? ''"></span>
    </a>
  </span>
</template>
