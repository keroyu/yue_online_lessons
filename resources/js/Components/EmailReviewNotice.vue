<script setup>
// The amber block shown between the two presses (011 FR-059 / 010 D18).
// The address is the only thing that matters here, so it is the only thing set
// large. What goes wrong if it is mistyped differs per form — a released slot,
// an ebook that never arrives — so that sentence comes in as a slot.
defineProps({
  email: { type: String, required: true },
})

defineEmits(['edit'])
</script>

<template>
  <div class="rounded-xl border-2 border-amber-400 bg-amber-50 p-4 text-center">
    <p class="text-sm font-bold text-amber-900">
      請再次確認 Email 必須填寫正確！否則收不到信
    </p>
    <p class="mt-3 break-all font-mono text-lg font-bold text-gray-900">
      {{ email }}
    </p>
    <p class="mt-2 text-xs leading-relaxed text-amber-800">
      <slot />
    </p>
    <button
      type="button"
      class="mt-3 text-xs font-semibold text-brand-teal underline cursor-pointer hover:opacity-70 transition"
      @click="$emit('edit')"
    >
      這個 Email 不對，回去修改
    </button>
  </div>
</template>
