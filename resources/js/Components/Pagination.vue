<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

/**
 * The one pagination control for the whole site (000 FR-030).
 *
 * Two navigation modes (FR-031): without `href` it renders buttons and emits
 * `change`, leaving the page-turn to the caller — the callers do it in five
 * different ways (router.get with filters, preserveState, preserveScroll), and
 * folding that in here would force a props interface that fits none of them.
 * With `href` it renders real Inertia <Link>s, which is what the public blog
 * needs: a crawler has to be able to follow page 2.
 */
const props = defineProps({
  currentPage: { type: Number, required: true },
  lastPage: { type: Number, required: true },
  // Maximum number of page slots rendered, ellipses excluded.
  maxVisible: { type: Number, default: 10 },
  // (page) => string. Given, each page becomes a real link.
  href: { type: Function, default: null },
})

const emit = defineEmits(['change'])

const ELLIPSIS = '…'

/**
 * Page slots with the current page centred. When the window hits either end it
 * refills toward the other side, so page 1 and the last page still show a full
 * row rather than half of one. First and last page are always reachable; the
 * gaps they leave behind collapse into a non-clickable ellipsis.
 */
const pages = computed(() => {
  const last = props.lastPage
  const max = props.maxVisible

  if (last <= max) {
    return Array.from({ length: last }, (_, i) => i + 1)
  }

  // Reserve the two anchors, then centre the rest on the current page.
  const inner = max - 2
  let start = props.currentPage - Math.floor((inner - 1) / 2)
  let end = start + inner - 1

  if (start < 2) {
    start = 2
    end = start + inner - 1
  }
  if (end > last - 1) {
    end = last - 1
    start = end - inner + 1
  }

  const slots = [1]
  if (start > 2) slots.push(ELLIPSIS)
  for (let p = start; p <= end; p++) slots.push(p)
  if (end < last - 1) slots.push(ELLIPSIS)
  slots.push(last)

  return slots
})

const isEllipsis = (slot) => slot === ELLIPSIS

const slotClass = (slot) =>
  slot === props.currentPage
    ? 'bg-brand-navy text-white border-brand-navy'
    : 'border-gray-300 text-gray-700 hover:bg-gray-50'

const go = (page) => {
  if (page !== props.currentPage) emit('change', page)
}
</script>

<template>
  <nav v-if="lastPage > 1" class="flex flex-wrap justify-center gap-1">
    <template v-for="(slot, i) in pages" :key="`${slot}-${i}`">
      <span
        v-if="isEllipsis(slot)"
        class="px-3 py-1.5 text-sm text-gray-400 select-none"
      >{{ slot }}</span>

      <Link
        v-else-if="href"
        :href="href(slot)"
        class="px-3 py-1.5 border rounded-md text-sm tabular-nums transition-colors cursor-pointer"
        :class="slotClass(slot)"
        :aria-current="slot === currentPage ? 'page' : undefined"
        preserve-scroll
      >{{ slot }}</Link>

      <button
        v-else
        type="button"
        class="px-3 py-1.5 border rounded-md text-sm tabular-nums transition-colors cursor-pointer"
        :class="slotClass(slot)"
        :aria-current="slot === currentPage ? 'page' : undefined"
        @click="go(slot)"
      >{{ slot }}</button>
    </template>
  </nav>
</template>
