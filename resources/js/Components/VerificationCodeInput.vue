<script setup>
import { ref, watch, onMounted } from 'vue'

const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue', 'complete'])

const inputs = ref([])
const digits = ref(['', '', '', '', '', ''])

onMounted(() => {
  if (inputs.value[0]) {
    inputs.value[0].focus()
  }
})

watch(
  () => props.modelValue,
  (newValue) => {
    if (newValue) {
      digits.value = newValue.split('').slice(0, 6)
      while (digits.value.length < 6) {
        digits.value.push('')
      }
    } else {
      digits.value = ['', '', '', '', '', '']
    }
  },
  { immediate: true }
)

const handleInput = (index, event) => {
  const value = event.target.value

  // Only allow digits
  if (!/^\d*$/.test(value)) {
    event.target.value = digits.value[index]
    return
  }

  // Handle paste
  if (value.length > 1) {
    const pastedDigits = value.slice(0, 6 - index).split('')
    pastedDigits.forEach((digit, i) => {
      if (index + i < 6) {
        digits.value[index + i] = digit
      }
    })

    const nextIndex = Math.min(index + pastedDigits.length, 5)
    inputs.value[nextIndex]?.focus()
  } else {
    digits.value[index] = value

    // Move to next input
    if (value && index < 5) {
      inputs.value[index + 1]?.focus()
    }
  }

  const code = digits.value.join('')
  emit('update:modelValue', code)

  if (code.length === 6) {
    emit('complete', code)
  }
}

const handleKeydown = (index, event) => {
  if (event.key === 'Backspace' && !digits.value[index] && index > 0) {
    inputs.value[index - 1]?.focus()
  }
}

const handleFocus = (event) => {
  event.target.select()
}
</script>

<template>
  <div class="flex gap-2 sm:gap-3 justify-center">
    <input
      v-for="(digit, index) in digits"
      :key="index"
      :ref="el => inputs[index] = el"
      type="text"
      inputmode="numeric"
      maxlength="6"
      :value="digit"
      :disabled="disabled"
      @input="handleInput(index, $event)"
      @keydown="handleKeydown(index, $event)"
      @focus="handleFocus"
      class="w-10 h-12 sm:w-12 sm:h-14 text-center text-xl font-semibold border border-gray-300 rounded-lg focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 disabled:bg-gray-100 disabled:cursor-not-allowed"
    />
  </div>
</template>
