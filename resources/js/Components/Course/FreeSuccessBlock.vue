<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue'
import { marked } from 'marked'

const props = defineProps({
  content: { type: String, required: true },
  email: { type: String, default: '' },
  name: { type: String, default: '' },
  courseName: { type: String, default: '' },
})

// Substitution happens on the raw Markdown, before it becomes HTML, so a value
// can never break the generated markup.
const rendered = computed(() => {
  const filled = (props.content || '')
    .replaceAll('{{email}}', props.email || '')
    .replaceAll('{{name}}', props.name || '')
    .replaceAll('{{course_name}}', props.courseName || '')

  // marked.js v17 passes raw HTML (including <iframe> embeds) through by default.
  return marked(filled)
})

const contentRef = ref(null)
const soundOff = ref(false)

// Only Vimeo/YouTube embeds can have their autoplay/mute flags rewritten safely.
const isControllable = (src) =>
  /player\.vimeo\.com|youtube\.com\/embed|youtube-nocookie\.com\/embed/.test(src || '')

const withParams = (src, params) => {
  try {
    const url = new URL(src, window.location.origin)
    Object.entries(params).forEach(([k, v]) => url.searchParams.set(k, v))
    return url.toString()
  } catch {
    return src
  }
}

const muteParamFor = (src) => (src.includes('vimeo') ? 'muted' : 'mute')

const controllableIframes = () =>
  Array.from(contentRef.value?.querySelectorAll('iframe') || [])
    .filter((f) => isControllable(f.getAttribute('src')))

// Browsers block autoplay with sound outright, so the video starts muted and the
// viewer opts into sound with a click (which counts as a user gesture).
const prepareVideos = () => {
  const frames = controllableIframes()

  frames.forEach((frame) => {
    const src = frame.getAttribute('src')
    frame.setAttribute('src', withParams(src, { autoplay: '1', [muteParamFor(src)]: '1' }))
    frame.setAttribute('allow', 'autoplay; fullscreen; picture-in-picture')

    // Responsive 16:9 wrapper so embeds never overflow on mobile.
    if (!frame.parentElement?.classList.contains('aspect-video')) {
      const wrapper = document.createElement('div')
      wrapper.className = 'aspect-video w-full my-4'
      frame.parentElement.insertBefore(wrapper, frame)
      wrapper.appendChild(frame)
    }
    frame.classList.add('w-full', 'h-full')
    frame.removeAttribute('width')
    frame.removeAttribute('height')
  })

  soundOff.value = frames.length > 0
}

const enableSound = () => {
  controllableIframes().forEach((frame) => {
    const src = frame.getAttribute('src')
    // Reloading with sound restarts the video — intended, so nothing is missed.
    frame.setAttribute('src', withParams(src, { autoplay: '1', [muteParamFor(src)]: '0' }))
  })
  soundOff.value = false
}

onMounted(prepareVideos)
watch(rendered, () => nextTick(prepareVideos))
</script>

<template>
  <div class="bg-brand-cream px-4 py-8">
    <div class="max-w-3xl mx-auto">
      <div class="bg-white rounded-xl border border-green-100 shadow-sm px-5 sm:px-8 py-7">
        <div class="flex items-center gap-2 mb-4">
          <span class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center shrink-0">
            <svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </span>
          <span class="text-sm font-medium text-green-700">領取成功</span>
        </div>

        <button
          v-if="soundOff"
          type="button"
          @click="enableSound"
          class="w-full mb-4 inline-flex items-center justify-center gap-2 px-5 py-3 rounded-lg font-semibold bg-brand-gold hover:bg-brand-gold-dark text-brand-navy border border-brand-gold-dark/50 transition-all shadow-sm cursor-pointer"
        >
          🔊 點此開啟聲音
        </button>

        <div ref="contentRef" class="prose prose-sm sm:prose-base max-w-none" v-html="rendered" />
      </div>
    </div>
  </div>
</template>
