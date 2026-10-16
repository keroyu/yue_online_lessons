<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'

// 011 US40 / D164 — native horizontal scroll with snap points: the browser
// gives touch swiping for free, the arrows only scroll by one card. No
// autoplay, so nothing moves while someone is reading.
const props = defineProps({
  testimonials: { type: Array, required: true },
})

const track = ref(null)
const atStart = ref(true)
const atEnd = ref(false)

// Identity replaces the consultation date on the card (011 FR-248). Students
// get the brand colour: a buyer vouching is the stronger signal.
const identityClass = (label) =>
  label === '課程學員' ? 'bg-brand-teal/10 text-brand-teal' : 'bg-brand-gold/15 text-brand-navy'

const updateEdges = () => {
  const el = track.value
  if (!el) return
  atStart.value = el.scrollLeft <= 1
  atEnd.value = el.scrollLeft + el.clientWidth >= el.scrollWidth - 1
}

// Long testimonials clamp to 6 lines; 「看全文」 opens the whole text in a
// modal so the row never changes height (011 FR-250). The link only appears
// where the text actually overflows, which depends on card width, so it is
// measured rather than guessed from length.
const contentEls = {}
const overflowing = ref({})
const active = ref(null)

const measureOverflow = () => {
  const next = {}
  for (const [id, el] of Object.entries(contentEls)) {
    next[id] = !!el && el.scrollHeight > el.clientHeight + 1
  }
  overflowing.value = next
}

const openFull = (t) => { active.value = t }
const closeFull = () => { active.value = null }
const onKeydown = (e) => { if (e.key === 'Escape') closeFull() }

// Lock page scroll behind the modal, so a swipe inside it does not scroll the page.
watch(active, (t) => {
  document.body.style.overflow = t ? 'hidden' : ''
})

const onResize = () => {
  updateEdges()
  measureOverflow()
}

const scrollByCard = (direction) => {
  const el = track.value
  const card = el?.querySelector('[data-card]')
  if (!el || !card) return
  const gap = parseFloat(getComputedStyle(el).columnGap) || 0
  el.scrollBy({ left: direction * (card.offsetWidth + gap), behavior: 'smooth' })
}

onMounted(async () => {
  updateEdges()
  await nextTick()
  measureOverflow()
  window.addEventListener('resize', onResize)
  window.addEventListener('keydown', onKeydown)
})
onBeforeUnmount(() => {
  window.removeEventListener('resize', onResize)
  window.removeEventListener('keydown', onKeydown)
  document.body.style.overflow = ''
})
</script>

<template>
  <section aria-label="客戶評語">
    <div class="flex items-end justify-between gap-4 mb-4">
      <h2 class="text-xl sm:text-2xl font-bold text-brand-navy">客戶評語</h2>
      <div v-if="props.testimonials.length > 1" class="hidden sm:flex gap-2">
        <button
          type="button"
          aria-label="上一則"
          :disabled="atStart"
          class="w-9 h-9 rounded-full border border-gray-300 bg-white text-gray-600 flex items-center justify-center hover:bg-gray-100 hover:text-gray-900 transition disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-white"
          @click="scrollByCard(-1)"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </button>
        <button
          type="button"
          aria-label="下一則"
          :disabled="atEnd"
          class="w-9 h-9 rounded-full border border-gray-300 bg-white text-gray-600 flex items-center justify-center hover:bg-gray-100 hover:text-gray-900 transition disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-white"
          @click="scrollByCard(1)"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </button>
      </div>
    </div>

    <div
      ref="track"
      class="flex gap-4 overflow-x-auto snap-x snap-mandatory scroll-smooth pb-3 -mx-4 px-4 scroll-px-4 sm:mx-0 sm:px-0 sm:scroll-px-0 [scrollbar-width:thin]"
      @scroll.passive="updateEdges"
    >
      <article
        v-for="t in props.testimonials"
        :key="t.id"
        data-card
        class="snap-start shrink-0 basis-[85%] sm:basis-[calc((100%-1rem)/2)] lg:basis-[calc((100%-2rem)/3)] bg-white rounded-xl border border-gray-200 shadow-sm p-5 flex flex-col"
      >
        <svg class="w-6 h-6 text-brand-gold shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path d="M9.983 3v7.391c0 5.704-3.731 9.57-8.983 10.609l-.995-2.151c2.432-.917 3.995-3.638 3.995-5.849h-4v-10h9.983zm14.017 0v7.391c0 5.704-3.748 9.571-9 10.609l-.996-2.151c2.433-.917 3.996-3.638 3.996-5.849h-3.983v-10h9.983z" />
        </svg>
        <!-- Plain text interpolation only — never v-html for visitor-written content. -->
        <div class="mt-3 flex-1">
          <p
            :ref="(el) => { contentEls[t.id] = el }"
            class="text-sm text-gray-700 leading-relaxed whitespace-pre-line break-words line-clamp-6"
          >{{ t.content }}</p>
          <button
            v-if="overflowing[t.id]"
            type="button"
            class="mt-1 text-sm font-medium text-brand-teal hover:text-brand-teal/70 hover:underline"
            aria-haspopup="dialog"
            @click="openFull(t)"
          >
            看全文
          </button>
        </div>
        <div class="mt-4 pt-4 border-t border-gray-100">
          <div class="flex items-center justify-between gap-2">
            <p class="font-semibold text-gray-900 truncate">{{ t.nickname }}</p>
            <span
              class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium"
              :class="identityClass(t.identity_label)"
            >{{ t.identity_label }}</span>
          </div>
          <p v-if="t.title" class="text-xs text-gray-500 mt-0.5">{{ t.title }}</p>
        </div>
      </article>
    </div>

    <!-- Full text (FR-250). Teleported so no ancestor's overflow or stacking clips it. -->
    <Teleport to="body">
      <div
        v-if="active"
        class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4"
        role="dialog"
        aria-modal="true"
        :aria-label="`${active.nickname} 的評語`"
      >
        <div class="absolute inset-0 bg-black/50" @click="closeFull" />
        <div class="relative w-full sm:max-w-lg max-h-[85vh] flex flex-col bg-white rounded-t-2xl sm:rounded-2xl shadow-xl">
          <div class="flex items-start justify-between gap-3 px-5 pt-5 pb-3 border-b border-gray-100">
            <div class="min-w-0">
              <div class="flex items-center gap-2">
                <p class="font-semibold text-gray-900 truncate">{{ active.nickname }}</p>
                <span
                  class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium"
                  :class="identityClass(active.identity_label)"
                >{{ active.identity_label }}</span>
              </div>
              <p v-if="active.title" class="text-xs text-gray-500 mt-0.5">{{ active.title }}</p>
            </div>
            <button
              type="button"
              aria-label="關閉"
              class="shrink-0 w-8 h-8 -mr-1 rounded-full flex items-center justify-center text-gray-400 hover:bg-gray-100 hover:text-gray-700 transition"
              @click="closeFull"
            >
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <p class="px-5 py-4 overflow-y-auto overscroll-contain text-[15px] text-gray-700 leading-relaxed whitespace-pre-line break-words">{{ active.content }}</p>
        </div>
      </div>
    </Teleport>
  </section>
</template>
