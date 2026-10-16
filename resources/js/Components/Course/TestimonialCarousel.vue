<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue'

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

// Long testimonials clamp to 6 lines with a per-card 「看全文」 toggle (011
// FR-250). The toggle only appears where the text actually overflows, which
// depends on card width, so it is measured rather than guessed from length.
const contentEls = {}
const overflowing = ref({})
const expanded = ref({})

const measureOverflow = () => {
  const next = {}
  for (const [id, el] of Object.entries(contentEls)) {
    if (el && !expanded.value[id]) next[id] = el.scrollHeight > el.clientHeight + 1
    else next[id] = overflowing.value[id] ?? false
  }
  overflowing.value = next
}

// While a card is open the row stops equalising heights, or every neighbour
// would stretch to the long one and show a screen of blank card.
const anyExpanded = () => Object.values(expanded.value).some(Boolean)

const toggleExpanded = (id) => {
  expanded.value = { ...expanded.value, [id]: !expanded.value[id] }
}

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
})
onBeforeUnmount(() => window.removeEventListener('resize', onResize))
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
      :class="{ 'items-start': anyExpanded() }"
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
            :id="`testimonial-${t.id}`"
            class="text-sm text-gray-700 leading-relaxed whitespace-pre-line break-words"
            :class="{ 'line-clamp-6': !expanded[t.id] }"
          >{{ t.content }}</p>
          <button
            v-if="overflowing[t.id]"
            type="button"
            class="mt-1 text-sm font-medium text-brand-teal hover:text-brand-teal/70 hover:underline"
            :aria-expanded="!!expanded[t.id]"
            :aria-controls="`testimonial-${t.id}`"
            @click="toggleExpanded(t.id)"
          >
            {{ expanded[t.id] ? '收起' : '看全文' }}
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
  </section>
</template>
