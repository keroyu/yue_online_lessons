<script setup>
import { ref, nextTick } from 'vue'

/**
 * Inserts a rotating-coupon `{alias}` placeholder into a promo HTML textarea.
 * Shared by the lesson modal and the course form so the two editors cannot
 * drift apart — the alias, not the code, is what makes rotation work (006 US5).
 */
const props = defineProps({
  chains: {
    type: Array,
    default: () => [],
  },
  modelValue: {
    type: String,
    default: '',
  },
  // The textarea being edited; when absent the placeholder is appended instead
  textarea: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['update:modelValue'])

const selectedAlias = ref('')

const insert = () => {
  if (!selectedAlias.value) return

  const placeholder = `{${selectedAlias.value}}`
  const text = props.modelValue || ''
  const el = props.textarea

  if (!el) {
    emit('update:modelValue', text + placeholder)
    selectedAlias.value = ''
    return
  }

  const start = el.selectionStart
  const end = el.selectionEnd
  emit('update:modelValue', text.substring(0, start) + placeholder + text.substring(end))

  nextTick(() => {
    el.selectionStart = el.selectionEnd = start + placeholder.length
    el.focus()
  })
  selectedAlias.value = ''
}
</script>

<template>
  <div v-if="chains.length > 0" class="flex items-center gap-2">
    <select
      v-model="selectedAlias"
      class="flex-1 rounded border-gray-300 px-3 py-1.5 text-sm font-mono cursor-pointer"
    >
      <option value="">選擇輪換折扣碼…</option>
      <option v-for="chain in chains" :key="chain.id" :value="chain.alias">
        {{ chain.label }}
      </option>
    </select>
    <button
      type="button"
      :disabled="!selectedAlias"
      class="shrink-0 rounded bg-brand-teal px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-teal/90 disabled:opacity-40 transition-colors"
      @click="insert"
    >
      插入折扣碼
    </button>
  </div>
</template>
