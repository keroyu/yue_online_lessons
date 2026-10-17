<script setup>
import { ref } from 'vue'

// datetime-local with a clear button, for optional date fields.
// Chrome's native input has no clear control, and deleting a single segment leaves it
// in a badInput state that blocks native form submission. By then v-model is already '',
// so Vue won't re-patch the DOM — the button resets the element directly.
defineOptions({ inheritAttrs: false })

// Untyped: forms seed these fields with either '' or null.
const model = defineModel({ default: '' })

defineProps({
  wrapperClass: { type: [String, Array], default: '' },
})

const input = ref(null)

const clear = () => {
  model.value = ''
  if (input.value) input.value.value = ''
}
</script>

<template>
  <div :class="['flex items-center gap-2', wrapperClass]">
    <!-- min-w-0: let the native input shrink in narrow columns instead of pushing the button out -->
    <input ref="input" v-model="model" type="datetime-local" class="min-w-0 flex-1" v-bind="$attrs" />
    <button
      type="button"
      @click="clear"
      class="shrink-0 px-2 py-2 rounded-lg border border-gray-300 text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900"
    >
      清除
    </button>
  </div>
</template>
