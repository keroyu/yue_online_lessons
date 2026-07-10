<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  url: {
    type: String,
    required: true,
  },
  title: {
    type: String,
    default: '',
  },
})

const copied = ref(false)

const encodedUrl = computed(() => encodeURIComponent(props.url))
const encodedTitle = computed(() => encodeURIComponent(props.title))

const canWebShare = computed(() => typeof navigator !== 'undefined' && !!navigator.share)

const shareLinks = computed(() => ({
  x: `https://twitter.com/intent/tweet?url=${encodedUrl.value}&text=${encodedTitle.value}`,
  threads: `https://www.threads.net/intent/post?text=${encodedTitle.value}%20${encodedUrl.value}`,
  facebook: `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl.value}`,
  line: `https://social-plugins.line.me/lineit/share?url=${encodedUrl.value}`,
}))

const nativeShare = async () => {
  try {
    await navigator.share({ title: props.title, url: props.url })
  } catch (e) {
    // user cancelled or unsupported — no-op
  }
}

const copyLink = async () => {
  try {
    await navigator.clipboard.writeText(props.url)
    copied.value = true
    setTimeout(() => (copied.value = false), 2000)
  } catch (e) {
    // clipboard unavailable — no-op
  }
}
</script>

<template>
  <div class="flex flex-wrap items-center gap-2">
    <span class="text-sm text-gray-500 mr-1">分享：</span>

    <button
      v-if="canWebShare"
      type="button"
      class="inline-flex items-center justify-center w-10 h-10 bg-brand-navy text-white hover:bg-brand-teal cursor-pointer transition-colors"
      title="分享"
      @click="nativeShare"
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
    </button>

    <a :href="shareLinks.x" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center w-10 h-10 bg-gray-800 text-white font-semibold hover:bg-black transition-colors" title="分享到 X">𝕏</a>
    <a :href="shareLinks.threads" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center w-10 h-10 bg-gray-900 text-white font-semibold hover:bg-black transition-colors" title="分享到 Threads">@</a>
    <a :href="shareLinks.facebook" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center w-10 h-10 bg-[#1877f2] text-white font-bold hover:brightness-110 transition-all" title="分享到 Facebook">f</a>
    <a :href="shareLinks.line" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center w-10 h-10 bg-[#06c755] text-white font-bold hover:brightness-110 transition-all" title="分享到 LINE">L</a>

    <button
      type="button"
      class="inline-flex items-center gap-1 h-10 px-4 bg-gray-100 border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-200 cursor-pointer transition-colors"
      @click="copyLink"
    >
      {{ copied ? '✓ 已複製' : '複製連結' }}
    </button>
  </div>
</template>
