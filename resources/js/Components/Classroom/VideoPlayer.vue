<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  embedUrl: {
    type: String,
    default: null,
  },
  platform: {
    type: String,
    default: null,
  },
  title: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['ended'])
const iframeRef = ref(null)
let vimeoListening = false

const iframeSrc = computed(() => {
  if (!props.embedUrl) return null

  const url = new URL(props.embedUrl)

  if (props.platform === 'vimeo') {
    url.searchParams.set('autoplay', '1')
    url.searchParams.set('quality', 'auto')
    url.searchParams.set('texttrack', 'zh-TW')
    url.searchParams.set('api', '1')
  } else if (props.platform === 'youtube') {
    url.searchParams.set('autoplay', '1')
    url.searchParams.set('rel', '0')
    url.searchParams.set('modestbranding', '1')
    url.searchParams.set('enablejsapi', '1')
  }

  return url.toString()
})

const handleMessage = (event) => {
  const iframe = iframeRef.value
  if (!iframe || event.source !== iframe.contentWindow) return

  let data
  try {
    data = typeof event.data === 'string' ? JSON.parse(event.data) : event.data
  } catch { return }

  if (props.platform === 'vimeo') {
    if (data.event === 'ready' && !vimeoListening) {
      vimeoListening = true
      iframe.contentWindow.postMessage(
        JSON.stringify({ method: 'addEventListener', value: 'finish' }),
        'https://player.vimeo.com'
      )
    } else if (data.event === 'finish') {
      emit('ended')
    }
  } else if (props.platform === 'youtube') {
    if (data.event === 'onStateChange' && data.info === 0) {
      emit('ended')
    }
  }
}

// Reset Vimeo listener flag when lesson changes so we re-subscribe on new iframe load
watch(() => props.embedUrl, () => {
  vimeoListening = false
})

onMounted(() => {
  window.addEventListener('message', handleMessage)
})

onUnmounted(() => {
  window.removeEventListener('message', handleMessage)
})
</script>

<template>
  <div class="relative w-full bg-black rounded-lg overflow-hidden" style="padding-top: 56.25%;">
    <iframe
      v-if="iframeSrc"
      ref="iframeRef"
      :src="iframeSrc"
      :title="title"
      class="absolute inset-0 w-full h-full"
      frameborder="0"
      allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
      allowfullscreen
    />
    <div
      v-else
      class="absolute inset-0 flex items-center justify-center text-white"
    >
      <div class="text-center">
        <svg class="mx-auto w-16 h-16 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
        </svg>
        <p class="mt-2 text-gray-400">無影片內容</p>
      </div>
    </div>
  </div>
</template>
