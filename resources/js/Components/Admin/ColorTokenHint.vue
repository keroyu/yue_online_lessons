<script setup>
import { computed, ref } from 'vue'

// Swatches for the active colour scheme, offered as CSS variables (002 FR-079).
//
// Deliberately NOT Tailwind classes: v4's JIT only compiles classes it finds
// while scanning source, so `bg-brand-navy` typed into a database row is never
// generated — the admin would copy it, see no colour, and get no error.
const props = defineProps({
  scheme: { type: Object, required: true },
})

const copied = ref(null)

function flash(key) {
  copied.value = key
  setTimeout(() => { copied.value = copied.value === key ? null : copied.value }, 1500)
}

async function write(text, key) {
  try {
    await navigator.clipboard.writeText(text)
    flash(key)
  } catch {
    // Clipboard is blocked outside a secure context; the token is on screen
    // either way, so this is not worth an error message.
  }
}

const token = role => `var(--color-brand-${role.replace(/_/g, '-')})`

// Single swatch: the variable alone, for "I want to recolour this one line".
// The hex is shown but not copied here — pasting it freezes the colour, and
// this block would stay behind the next time the palette changes.
const copyToken = role => write(token(role), role)

const ROLE_LABELS = {
  cream: '頁面底色',
  navy: '內文／導覽列',
  teal: '主要動作',
  gold: '強調 CTA',
  gold_dark: 'CTA hover',
  orange: '次要強調',
  red: '促銷／急迫',
}

// ─── Copy the whole palette (002 FR-091) ─────────────────────────────────────

// CJK glyphs occupy two cells in a monospaced font, so `String.padEnd` — which
// counts code points — leaves the columns visibly ragged the moment a label
// mixes 中文 and ASCII. Pad by display width instead.
const WIDE = /[ᄀ-ᅟ⺀-꓏가-힣豈-﫿︰-﹯＀-｠￠-￦]/

const displayWidth = str => [...str].reduce((w, ch) => w + (WIDE.test(ch) ? 2 : 1), 0)

const padTo = (str, width) => str + ' '.repeat(Math.max(0, width - displayWidth(str)))

/**
 * A paste-ready prompt: one instruction line, then 名稱 / 變數 / hex.
 *
 * The hex is included even though a single swatch withholds it — an assistant
 * needs the real values to judge contrast and depth, and given only variable
 * names it guesses. The instruction line is what keeps that safe: without
 * "不要直接寫 hex" spelled out, an assistant will almost always hand back HTML
 * with the values baked in, and that block then stays on this palette forever
 * while the rest of the site follows the switch (000 US14).
 */
const schemeBlob = computed(() => {
  const roles = Object.keys(props.scheme.colors)

  const labelWidth = Math.max(...roles.map(r => displayWidth(ROLE_LABELS[r] ?? r))) + 2
  const tokenWidth = Math.max(...roles.map(r => displayWidth(token(r)))) + 2

  const rows = roles.map(role => (
    padTo(ROLE_LABELS[role] ?? role, labelWidth) +
    padTo(token(role), tokenWidth) +
    props.scheme.colors[role]
  ))

  return [
    `這個網站目前的配色是「${props.scheme.name}」。產生 HTML 時請用下方的 CSS 變數`,
    '（不要直接寫 hex），這樣之後站台換配色時這塊內容會自動跟著變：',
    '',
    ...rows,
  ].join('\n')
})

const copyScheme = () => write(schemeBlob.value, '__scheme__')
</script>

<template>
  <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
    <div class="mb-2 flex items-start justify-between gap-3">
      <p class="text-xs text-gray-500">
        目前配色「{{ scheme.name }}」的色票 — 點一下複製 CSS 變數，貼進 <code class="text-gray-600">style="color: …"</code>。
        用變數寫的顏色會跟著配色方案一起變；直接寫 hex 不會。
      </p>

      <button
        type="button"
        class="shrink-0 cursor-pointer rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-600 transition-colors hover:border-brand-navy hover:text-brand-navy"
        title="複製整組配色（含給 AI 的指示句），可直接貼進對話框請它產生整塊 HTML"
        @click="copyScheme"
      >
        {{ copied === '__scheme__' ? '已複製整組' : '複製整組配色' }}
      </button>
    </div>

    <div class="flex flex-wrap gap-2">
      <button
        v-for="(hex, role) in scheme.colors"
        :key="role"
        type="button"
        class="cursor-pointer flex items-center gap-2 rounded-md border border-gray-200 bg-white px-2 py-1.5 text-left transition-colors hover:border-brand-navy hover:bg-white"
        :title="token(role)"
        @click="copyToken(role)"
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
