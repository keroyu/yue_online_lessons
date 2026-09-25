<script setup>
import { ref } from 'vue'

// Swatches for the active colour scheme, offered as CSS variables (002 FR-079).
//
// Deliberately NOT Tailwind classes: v4's JIT only compiles classes it finds
// while scanning source, so `bg-brand-navy` typed into a database row is never
// generated — the admin would copy it, see no colour, and get no error.
// The hex is shown for orientation but is not copyable either: it freezes the
// colour, and this block would stay behind the next time the site's palette
// changes, which is the one thing 000 US14 exists to make easy.
defineProps({
  scheme: { type: Object, required: true },
})

const copied = ref(null)

async function copy(role) {
  const token = `var(--color-brand-${role.replace(/_/g, '-')})`

  try {
    await navigator.clipboard.writeText(token)
    copied.value = role
    setTimeout(() => { copied.value = copied.value === role ? null : copied.value }, 1500)
  } catch {
    // Clipboard is blocked outside a secure context; the token is on screen
    // either way, so this is not worth an error message.
  }
}

const ROLE_LABELS = {
  cream: '頁面底色',
  navy: '內文／導覽列',
  teal: '主要動作',
  gold: '強調 CTA',
  gold_dark: 'CTA hover',
  orange: '次要強調',
  red: '促銷／急迫',
}
</script>

<template>
  <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
    <p class="text-xs text-gray-500 mb-2">
      目前配色「{{ scheme.name }}」的色票 — 點一下複製 CSS 變數，貼進 <code class="text-gray-600">style="color: …"</code>。
      用變數寫的顏色會跟著配色方案一起變；直接寫 hex 不會。
    </p>

    <div class="flex flex-wrap gap-2">
      <button
        v-for="(hex, role) in scheme.colors"
        :key="role"
        type="button"
        class="cursor-pointer flex items-center gap-2 rounded-md border border-gray-200 bg-white px-2 py-1.5 text-left transition-colors hover:border-brand-navy hover:bg-white"
        :title="`var(--color-brand-${role.replace(/_/g, '-')})`"
        @click="copy(role)"
      >
        <span class="h-5 w-5 shrink-0 rounded border border-gray-300" :style="{ backgroundColor: hex }"></span>
        <span class="leading-tight">
          <span class="block text-xs font-medium text-gray-700">{{ ROLE_LABELS[role] ?? role }}</span>
          <span class="block text-[10px] text-gray-400">{{ copied === role ? '已複製' : hex }}</span>
        </span>
      </button>
    </div>
  </div>
</template>
