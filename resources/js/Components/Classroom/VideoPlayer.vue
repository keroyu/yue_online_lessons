<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'

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

const iframeRef = ref(null)   // Vimeo
const ytContainer = ref(null) // YouTube
const cfIframeRef = ref(null) // Cloudflare Stream

let vimeoListening = false
let ytPlayer = null
let cfPlayer = null

// ─── Vimeo ───────────────────────────────────────────────────────────────────

const vimeoSrc = computed(() => {
  if (props.platform !== 'vimeo' || !props.embedUrl) return null
  const url = new URL(props.embedUrl)
  url.searchParams.set('autoplay', '1')
  url.searchParams.set('quality', 'auto')
  url.searchParams.set('texttrack', 'zh-TW')
  url.searchParams.set('api', '1')
  return url.toString()
})

const handleVimeoMessage = (event) => {
  // Check origin instead of event.source: Vimeo uses nested iframes internally,
  // so the ready event may arrive from a child frame, not iframeRef.contentWindow
  if (!event.origin.includes('vimeo.com')) return
  let data
  try {
    data = typeof event.data === 'string' ? JSON.parse(event.data) : event.data
  } catch { return }
  if (data.event === 'ready' && !vimeoListening) {
    vimeoListening = true
    const player = iframeRef.value?.contentWindow
    if (!player) return
    player.postMessage(
      JSON.stringify({ method: 'addEventListener', value: 'finish' }),
      'https://player.vimeo.com'
    )
    player.postMessage(
      JSON.stringify({ method: 'enableTextTrack', value: { language: 'zh-TW', kind: 'subtitles' } }),
      'https://player.vimeo.com'
    )
  } else if (data.event === 'finish') {
    emit('ended')
  }
}

// ─── YouTube ─────────────────────────────────────────────────────────────────

const ytVideoId = computed(() => {
  if (props.platform !== 'youtube' || !props.embedUrl) return null
  const match = props.embedUrl.match(/\/embed\/([^?&/]+)/)
  return match ? match[1] : null
})

// Module-level promise so multiple instances share one API load
let ytApiPromise = null

const loadYouTubeApi = () => {
  if (!ytApiPromise) {
    ytApiPromise = new Promise((resolve) => {
      if (window.YT?.Player) { resolve(window.YT); return }
      const prev = window.onYouTubeIframeAPIReady
      window.onYouTubeIframeAPIReady = () => {
        if (prev) prev()
        resolve(window.YT)
      }
      if (!document.querySelector('script[src*="youtube.com/iframe_api"]')) {
        const s = document.createElement('script')
        s.src = 'https://www.youtube.com/iframe_api'
        document.head.appendChild(s)
      }
    })
  }
  return ytApiPromise
}

const initYouTubePlayer = async () => {
  if (!ytVideoId.value) return
  // If player already exists, just swap the video — don't destroy/recreate.
  // YT.Player() replaces the container div with an iframe, so after the first
  // init ytContainer.value is detached; calling destroy() + new YT.Player()
  // would mount the new player on a detached element (invisible).
  if (ytPlayer) {
    ytPlayer.loadVideoById(ytVideoId.value)
    return
  }
  const YT = await loadYouTubeApi()
  if (!ytContainer.value) return // unmounted while awaiting
  ytPlayer = new YT.Player(ytContainer.value, {
    videoId: ytVideoId.value,
    width: '100%',
    height: '100%',
    playerVars: { autoplay: 1, rel: 0, modestbranding: 1 },
    events: {
      onStateChange: (e) => {
        if (e.data === YT.PlayerState.ENDED) emit('ended')
      },
    },
  })
}

// ─── Cloudflare Stream ───────────────────────────────────────────────────────

const cfSrc = computed(() => {
  if (props.platform !== 'cloudflare' || !props.embedUrl) return null
  const url = new URL(props.embedUrl)
  url.searchParams.set('autoplay', 'true')
  return url.toString()
})

// Module-level promise so multiple instances share one SDK load (same pattern as YT API)
let cfSdkPromise = null

const loadCloudflareSdk = () => {
  if (!cfSdkPromise) {
    cfSdkPromise = new Promise((resolve) => {
      if (window.Stream) { resolve(window.Stream); return }
      const s = document.createElement('script')
      s.src = 'https://embed.cloudflarestream.com/embed/sdk.latest.js'
      s.onload = () => resolve(window.Stream)
      document.head.appendChild(s)
    })
  }
  return cfSdkPromise
}

const initCloudflarePlayer = async () => {
  const Stream = await loadCloudflareSdk()
  if (!cfIframeRef.value) return // unmounted while awaiting
  cfPlayer = Stream(cfIframeRef.value)
  cfPlayer.addEventListener('ended', () => emit('ended'))
}

// ─── Lifecycle ────────────────────────────────────────────────────────────────

watch(() => props.embedUrl, async () => {
  vimeoListening = false
  if (props.platform !== 'cloudflare') {
    cfPlayer = null // cf iframe unmounts when switching platform; re-init if we come back
  }
  if (props.platform === 'youtube') {
    await nextTick()
    initYouTubePlayer()
  } else if (props.platform === 'cloudflare' && !cfPlayer) {
    await nextTick()
    initCloudflarePlayer()
  }
  // cloudflare → cloudflare: same iframe element, src swap; SDK instance persists
})

onMounted(() => {
  // Vimeo listener registered unconditionally: lesson switches can change the
  // platform after mount, and the handler already guards by event origin
  window.addEventListener('message', handleVimeoMessage)
  if (props.platform === 'youtube') {
    initYouTubePlayer()
  } else if (props.platform === 'cloudflare') {
    initCloudflarePlayer()
  }
})

onUnmounted(() => {
  window.removeEventListener('message', handleVimeoMessage)
  if (ytPlayer) {
    ytPlayer.destroy()
    ytPlayer = null
  }
  cfPlayer = null
})
</script>

<template>
  <div class="relative w-full bg-black rounded-lg overflow-hidden" style="padding-top: 56.25%;">
    <!-- Vimeo: standard iframe with postMessage API -->
    <iframe
      v-if="vimeoSrc"
      ref="iframeRef"
      :src="vimeoSrc"
      :title="title"
      class="absolute inset-0 w-full h-full"
      frameborder="0"
      allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
      allowfullscreen
    />

    <!-- YouTube: YT.Player injects its own iframe into this container -->
    <div
      v-else-if="ytVideoId"
      ref="ytContainer"
      class="absolute inset-0 w-full h-full"
    />

    <!-- Cloudflare Stream: iframe + Stream SDK for the ended event -->
    <iframe
      v-else-if="cfSrc"
      ref="cfIframeRef"
      :src="cfSrc"
      :title="title"
      class="absolute inset-0 w-full h-full"
      frameborder="0"
      allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
      allowfullscreen
    />

    <!-- No video -->
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
