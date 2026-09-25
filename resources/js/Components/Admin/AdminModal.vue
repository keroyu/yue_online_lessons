<script setup>
import { onBeforeUnmount, watch } from 'vue'

// Shared admin modal shell (002 US24 / FR-088).
//
// The codebase already has sixteen hand-rolled `fixed inset-0` modals, and
// they have drifted into two different backdrop treatments. This exists so the
// homepage-settings modals do not add a third. It deliberately does NOT
// retrofit the existing sixteen — that is a separate change, and folding it in
// here would make this one impossible to verify on its own (002 D75).
//
// ONE `<Transition>` over ONE `v-if`, with the panel's scale done in scoped
// CSS, rather than the nested Transition + duplicated `v-if` the older modals
// here use. Two transitions driven by two v-ifs that flip in the same tick is
// more machinery than a fade needs, and it puts the overlay's removal behind
// a second animation completing.
const props = defineProps({
  open: { type: Boolean, default: false },
  title: { type: String, required: true },
})

const emit = defineEmits(['close'])

function onKeydown(event) {
  if (event.key === 'Escape') emit('close')
}

// Lock the page behind the panel: without this the admin scrolls the settings
// page under an open modal and loses their place on close.
//
// Tracked per instance, because all three settings modals are mounted at once:
// whichever one closes last must not clear a lock another one just took.
let holdsScrollLock = false

watch(() => props.open, isOpen => {
  if (isOpen) {
    holdsScrollLock = true
    document.body.style.overflow = 'hidden'
    window.addEventListener('keydown', onKeydown)
  } else {
    if (holdsScrollLock) {
      holdsScrollLock = false
      document.body.style.overflow = ''
    }
    window.removeEventListener('keydown', onKeydown)
  }
}, { immediate: true })

onBeforeUnmount(() => {
  if (holdsScrollLock) document.body.style.overflow = ''
  window.removeEventListener('keydown', onKeydown)
})
</script>

<template>
  <Teleport to="body">
    <Transition name="admin-modal">
      <div
        v-if="open"
        class="fixed inset-0 z-50 overflow-y-auto"
        role="dialog"
        aria-modal="true"
      >
        <div class="fixed inset-0 bg-black/50" aria-hidden="true" />

        <!-- This centring layer is painted over the backdrop and spans the
             whole viewport, so a click beside the panel targets IT — a handler
             on the backdrop alone would never fire. -->
        <div class="flex min-h-full items-center justify-center p-4" @click.self="emit('close')">
          <div class="admin-modal-panel relative w-full max-w-2xl rounded-xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
              <h2 class="text-lg font-semibold text-gray-900">{{ title }}</h2>
              <button
                type="button"
                class="cursor-pointer rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
                title="關閉"
                @click="emit('close')"
              >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- The content scrolls, not the panel: a dozen featured courses
                 must not push the save button off a phone screen (FR-090). -->
            <div class="max-h-[70vh] overflow-y-auto px-6 py-5">
              <slot />
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.admin-modal-enter-active,
.admin-modal-leave-active {
  transition: opacity 200ms ease;
}

.admin-modal-enter-from,
.admin-modal-leave-to {
  opacity: 0;
}

.admin-modal-enter-active .admin-modal-panel,
.admin-modal-leave-active .admin-modal-panel {
  transition: transform 200ms ease;
}

.admin-modal-enter-from .admin-modal-panel,
.admin-modal-leave-to .admin-modal-panel {
  transform: scale(0.95);
}
</style>
