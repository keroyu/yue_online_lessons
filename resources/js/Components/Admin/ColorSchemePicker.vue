<script setup>
import { ref, computed, onBeforeUnmount } from 'vue'
import { router } from '@inertiajs/vue3'

/**
 * Colour scheme picker for the homepage settings page (000 US14).
 *
 * Lives in its own component rather than inline in `Edit.vue`: that page is
 * already ~880 lines across six cards, and eight palette cards would push it
 * past a thousand. Ownership follows the same split — the palette belongs to
 * 000, the page it hangs on belongs to 002 (D47).
 *
 * Previewing works because the admin chrome is drawn with the same seven
 * tokens as the front end, so writing them onto <html> recolours the sidebar
 * and buttons around you immediately. Nothing is persisted until 儲存.
 */
const props = defineProps({
  schemes: { type: Array, required: true },
  active: { type: String, required: true },
})

const ROLE_LABELS = {
  cream: '頁面底色',
  navy: '墨色／側欄',
  teal: '主要行動',
  gold: '強調 CTA',
  gold_dark: 'CTA hover',
  orange: '次要強調',
  red: '促銷急迫',
}

const selected = ref(props.active)
const saving = ref(false)

const dirty = computed(() => selected.value !== props.active)

/** `gold_dark` is the CSS custom property `--color-brand-gold-dark`. */
const cssVar = (role) => `--color-brand-${role.replace('_', '-')}`

const applyPreview = (scheme) => {
  Object.entries(scheme.colors).forEach(([role, hex]) => {
    document.documentElement.style.setProperty(cssVar(role), hex)
  })
}

const clearPreview = () => {
  Object.keys(ROLE_LABELS).forEach((role) => {
    document.documentElement.style.removeProperty(cssVar(role))
  })
}

const choose = (scheme) => {
  selected.value = scheme.key
  applyPreview(scheme)
}

const cancel = () => {
  selected.value = props.active
  clearPreview()
}

const save = () => {
  if (!dirty.value || saving.value) return
  saving.value = true

  router.post(
    '/admin/homepage/color-scheme',
    { color_scheme: selected.value },
    {
      preserveScroll: true,
      // The saved palette now comes from the server on the next render, so the
      // inline preview has to come off or it would mask a later change.
      onSuccess: () => clearPreview(),
      onFinish: () => { saving.value = false },
    }
  )
}

// Leaving the page mid-preview must not strand the admin on colours that were
// never saved.
onBeforeUnmount(clearPreview)
</script>

<template>
  <section class="bg-white rounded-xl border border-gray-200 p-6 space-y-6">
    <div>
      <h2 class="text-lg font-semibold text-gray-800">配色方案</h2>
      <p class="mt-1 text-sm text-gray-500">
        套用到整個網站與後台。點選即可預覽，滿意再儲存。每一組都通過無障礙對比檢查。
      </p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
      <div
        v-for="scheme in schemes"
        :key="scheme.key"
        class="cursor-pointer rounded-lg border-2 overflow-hidden transition-colors hover:border-brand-teal"
        :class="selected === scheme.key ? 'border-brand-teal' : 'border-gray-200'"
        role="button"
        tabindex="0"
        @click="choose(scheme)"
        @keydown.enter.prevent="choose(scheme)"
        @keydown.space.prevent="choose(scheme)"
      >
        <!-- Miniature of the real layout: navbar, body copy, both buttons. -->
        <div class="p-3" :style="{ backgroundColor: scheme.colors.cream }">
          <div
            class="h-5 rounded-sm mb-2"
            :style="{ backgroundColor: scheme.colors.navy }"
          />
          <div class="space-y-1 mb-2">
            <div class="h-1.5 w-full rounded-full opacity-70" :style="{ backgroundColor: scheme.colors.navy }" />
            <div class="h-1.5 w-2/3 rounded-full opacity-40" :style="{ backgroundColor: scheme.colors.navy }" />
          </div>
          <div class="flex items-center gap-1.5">
            <div
              class="h-4 w-12 rounded-sm"
              :style="{ backgroundColor: scheme.colors.teal }"
            />
            <div
              class="h-4 w-10 rounded-full"
              :style="{ backgroundColor: scheme.colors.gold, border: `1px solid ${scheme.colors.gold_dark}` }"
            />
            <div
              class="h-1.5 w-6 rounded-full ml-auto"
              :style="{ backgroundColor: scheme.colors.red }"
            />
          </div>
        </div>

        <div class="px-3 py-2.5 bg-white border-t border-gray-100">
          <div class="flex items-center justify-between gap-2">
            <span class="text-sm font-semibold text-gray-800">{{ scheme.name }}</span>
            <span
              v-if="scheme.key === active"
              class="shrink-0 text-[10px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-500"
            >使用中</span>
          </div>
          <p class="mt-0.5 text-xs text-gray-500 leading-snug">{{ scheme.industry }}</p>

          <div class="mt-2 flex gap-1">
            <span
              v-for="(hex, role) in scheme.colors"
              :key="role"
              class="h-4 flex-1 rounded-sm border border-black/5"
              :style="{ backgroundColor: hex }"
              :title="`${ROLE_LABELS[role]} ${hex}`"
            />
          </div>
        </div>
      </div>
    </div>

    <div class="flex items-center gap-3 pt-2">
      <button
        type="button"
        :disabled="!dirty || saving"
        class="px-5 py-2 bg-brand-navy text-white text-sm font-semibold rounded-lg hover:bg-opacity-90 disabled:opacity-50"
        @click="save"
      >
        {{ saving ? '儲存中…' : '儲存配色' }}
      </button>
      <button
        v-if="dirty"
        type="button"
        class="px-4 py-2 text-sm text-gray-600 rounded-lg hover:bg-gray-100"
        @click="cancel"
      >
        取消
      </button>
      <p v-if="dirty" class="text-xs text-gray-500">預覽中，尚未儲存</p>
    </div>
  </section>
</template>
