<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminModal from '@/Components/Admin/AdminModal.vue'

// 內容分類 — the filter buttons that head the 所有資源 grid (002 US24).
//
// Reached from the `course_catalog` row because that is what they belong to:
// they filter the list directly below them and cannot be reordered away from
// it (002 FR-075). This toggle is the buttons' own switch, one level below
// the widget's show/hide.
const props = defineProps({
  open: { type: Boolean, default: false },
  contentCategorySlots: { type: Array, default: () => [] },
  contentFilterEnabled: { type: Boolean, default: false },
})

const emit = defineEmits(['close'])

const categoryForm = ref({ enabled: false, categories: [] })
const categoryErrors = ref({})
const categorySaving = ref(false)

watch(() => [props.contentCategorySlots, props.contentFilterEnabled], () => {
  categoryForm.value = {
    enabled: props.contentFilterEnabled,
    categories: props.contentCategorySlots.map(c => ({ label: c.label ?? '', slug: c.slug ?? '' })),
  }
}, { immediate: true, deep: true })

function saveCategories() {
  categorySaving.value = true
  categoryErrors.value = {}

  router.post('/admin/homepage/content-categories', {
    enabled: categoryForm.value.enabled,
    categories: categoryForm.value.categories,
  }, {
    preserveScroll: true,
    onError: errors => { categoryErrors.value = errors },
    onFinish: () => { categorySaving.value = false },
  })
}
</script>

<template>
  <AdminModal :open="open" title="內容分類（首頁過濾按鈕）" @close="emit('close')">
    <div class="space-y-4">
      <p class="text-xs text-gray-400">
        最多 3 組；有填寫「顯示文字＋英文名」的才會在首頁顯示按鈕。英文名只能用小寫英文與「-」。
        這些按鈕是「所有資源」課程列表的表頭，永遠跟著它一起移動。
      </p>

      <!-- Visibility toggle -->
      <div class="flex items-center gap-3">
        <label class="text-sm font-medium text-gray-700">在首頁顯示分類過濾按鈕</label>
        <button
          type="button"
          class="relative inline-flex h-6 w-11 cursor-pointer items-center rounded-full transition-colors"
          :class="categoryForm.enabled ? 'bg-brand-navy' : 'bg-gray-300'"
          @click="categoryForm.enabled = !categoryForm.enabled"
        >
          <span
            class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
            :class="categoryForm.enabled ? 'translate-x-6' : 'translate-x-1'"
          />
        </button>
        <span class="text-sm text-gray-500">{{ categoryForm.enabled ? '顯示' : '不顯示' }}</span>
      </div>

      <!-- 3 category slots -->
      <div class="space-y-3">
        <div
          v-for="(cat, i) in categoryForm.categories"
          :key="i"
          class="grid grid-cols-1 sm:grid-cols-2 gap-3 border border-gray-200 rounded-lg p-3"
        >
          <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">顯示文字 {{ i + 1 }}</label>
            <input
              v-model="cat.label"
              type="text"
              maxlength="50"
              placeholder="例：思維升級"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-navy"
            />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">英文名（slug）</label>
            <input
              v-model="cat.slug"
              type="text"
              maxlength="50"
              placeholder="例：mindset"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-navy"
            />
            <p v-if="categoryErrors[`categories.${i}.slug`]" class="mt-1 text-sm text-red-600">{{ categoryErrors[`categories.${i}.slug`] }}</p>
            <p v-if="categoryErrors[`categories.${i}`]" class="mt-1 text-sm text-red-600">{{ categoryErrors[`categories.${i}`] }}</p>
          </div>
        </div>
      </div>

      <p v-if="categoryErrors.categories" class="text-sm text-red-600">{{ categoryErrors.categories }}</p>

      <div class="pt-1">
        <button
          type="button"
          :disabled="categorySaving"
          class="cursor-pointer px-5 py-2 bg-brand-navy text-white text-sm font-semibold rounded-lg hover:bg-opacity-90 disabled:opacity-50"
          @click="saveCategories"
        >
          {{ categorySaving ? '儲存中…' : '儲存分類設定' }}
        </button>
      </div>
    </div>
  </AdminModal>
</template>
