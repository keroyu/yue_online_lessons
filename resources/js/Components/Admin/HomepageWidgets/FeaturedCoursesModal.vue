<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import draggable from 'vuedraggable'
import AdminModal from '@/Components/Admin/AdminModal.vue'

// 精選推薦（課程）settings, reached from that widget's row (002 US24).
//
// Owns its own state and requests: moving only the markup out of Edit.vue
// would not have shortened that file, and shortening it is the point (FR-085).
const props = defineProps({
  open: { type: Boolean, default: false },
  featuredCourses: { type: Array, default: () => [] },
  availableCourses: { type: Array, default: () => [] },
})

const emit = defineEmits(['close'])

const featured = ref([])

// Re-sync whenever the server returns fresh props (after add / remove /
// reorder). Copying once in setup would leave the panel showing pre-save
// values, which reads as "my save did nothing" (FR-086).
watch(() => props.featuredCourses, list => {
  featured.value = list.map(c => ({ ...c, editBlurb: c.blurb ?? '' }))
}, { immediate: true, deep: true })

const showFeaturedForm = ref(false)
const newFeatured = ref({ course_id: '', blurb: '' })
const featuredErrors = ref({})

function addFeatured() {
  featuredErrors.value = {}
  router.post('/admin/homepage/featured-courses', {
    course_id: newFeatured.value.course_id,
    blurb:     newFeatured.value.blurb,
  }, {
    preserveScroll: true,
    onError: errors => { featuredErrors.value = errors },
    onSuccess: () => {
      showFeaturedForm.value = false
      newFeatured.value = { course_id: '', blurb: '' }
    },
  })
}

function saveFeaturedBlurb(item) {
  router.put(`/admin/homepage/featured-courses/${item.id}`, { blurb: item.editBlurb }, {
    preserveScroll: true,
    onSuccess: () => { item.blurb = item.editBlurb },
  })
}

// 顯示/隱藏：點下即生效，傳明確的布林值而非讓伺服器翻轉（D53）。
function toggleFeaturedVisibility(item) {
  const next = !item.is_visible

  router.patch(`/admin/homepage/featured-courses/${item.id}/visibility`, { is_visible: next }, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => { item.is_visible = next },
  })
}

function removeFeatured(item) {
  if (!confirm(`確定要將「${item.name}」從精選中移除嗎？`)) return
  router.delete(`/admin/homepage/featured-courses/${item.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      const idx = featured.value.findIndex(f => f.id === item.id)
      if (idx !== -1) featured.value.splice(idx, 1)
    },
  })
}

function onFeaturedReorder() {
  router.post('/admin/homepage/featured-courses/reorder', {
    ids: featured.value.map(f => f.id),
  }, { preserveScroll: true })
}
</script>

<template>
  <AdminModal :open="open" title="精選推薦（課程）" @close="emit('close')">
    <div class="space-y-4">
      <p class="text-xs text-gray-400">顯示縮圖 + 自訂介紹 + 進入銷售頁按鈕。可拖曳排序。</p>

      <div v-if="featured.length === 0" class="text-sm text-gray-400">尚未加入任何精選課程</div>

      <draggable
        v-model="featured"
        item-key="id"
        handle=".drag-handle"
        class="space-y-3"
        @end="onFeaturedReorder"
      >
        <template #item="{ element: item }">
          <div
            class="flex items-start gap-3 border rounded-lg p-3"
            :class="item.is_visible ? 'border-gray-200' : 'border-gray-200 bg-gray-50'"
          >
            <span class="drag-handle mt-1 cursor-move select-none text-gray-400 hover:text-gray-600" title="拖曳排序">⠿</span>
            <div class="flex-1 min-w-0 space-y-2">
              <div class="flex items-center gap-3">
                <img
                  v-if="item.thumbnail"
                  :src="item.thumbnail"
                  :alt="item.name"
                  class="w-24 h-14 shrink-0 object-cover rounded border border-gray-200"
                  :class="{ 'opacity-40': !item.is_visible }"
                />
                <div v-else class="w-24 h-14 shrink-0 rounded border border-gray-200 bg-gray-100" />
                <div class="flex-1 min-w-0 flex items-center gap-2">
                  <p class="text-sm font-medium truncate" :class="item.is_visible ? 'text-gray-800' : 'text-gray-400'">
                    {{ item.name }}
                  </p>
                  <span
                    v-if="!item.is_visible"
                    class="shrink-0 px-1.5 py-0.5 rounded bg-gray-200 text-gray-500 text-xs"
                  >
                    隱藏中
                  </span>
                </div>
                <!-- 隱藏只是不顯示在前台，介紹文字與排序都留著 -->
                <button
                  type="button"
                  class="shrink-0 text-sm cursor-pointer transition-colors"
                  :class="item.is_visible ? 'text-gray-500 hover:text-gray-800 hover:underline' : 'text-brand-navy font-medium hover:underline'"
                  :title="item.is_visible ? '從首頁右欄隱藏（介紹與排序都會保留）' : '重新顯示於首頁右欄'"
                  @click="toggleFeaturedVisibility(item)"
                >
                  {{ item.is_visible ? '隱藏' : '顯示' }}
                </button>
                <button
                  class="text-sm text-red-500 hover:underline shrink-0 cursor-pointer"
                  @click="removeFeatured(item)"
                >
                  移除
                </button>
              </div>
              <textarea
                v-model="item.editBlurb"
                rows="4"
                maxlength="500"
                placeholder="自訂介紹（例：馬上領取理財電子書！可換行、最多 500 字）"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm leading-relaxed resize-y focus:outline-none focus:ring-2 focus:ring-brand-navy"
              />
              <div class="flex items-center justify-between">
                <span class="text-xs" :class="item.editBlurb.length > 500 ? 'text-red-500' : 'text-gray-400'">
                  {{ item.editBlurb.length }} / 500 字
                </span>
                <button
                  class="cursor-pointer px-4 py-1.5 bg-brand-navy text-white text-sm font-semibold rounded-lg hover:bg-opacity-90"
                  @click="saveFeaturedBlurb(item)"
                >
                  儲存介紹
                </button>
              </div>
            </div>
          </div>
        </template>
      </draggable>

      <!-- Add featured course -->
      <div v-if="showFeaturedForm" class="border border-gray-200 rounded-lg p-4 space-y-3">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">選擇課程</label>
          <select
            v-model="newFeatured.course_id"
            class="w-full cursor-pointer rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-navy"
          >
            <option value="" disabled>請選擇課程…</option>
            <option v-for="c in availableCourses" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
          <p v-if="featuredErrors.course_id" class="mt-1 text-sm text-red-600">{{ featuredErrors.course_id }}</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">自訂介紹（可留空）</label>
          <textarea
            v-model="newFeatured.blurb"
            rows="4"
            maxlength="500"
            placeholder="例：你也在為自己的退休感到煩惱嗎？三個步驟簡單解決你的困擾。馬上索取你的第一本理財電子書！（可換行、最多 500 字）"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm leading-relaxed resize-y focus:outline-none focus:ring-2 focus:ring-brand-navy"
          />
          <div class="mt-1 flex items-center justify-between">
            <p v-if="featuredErrors.blurb" class="text-sm text-red-600">{{ featuredErrors.blurb }}</p>
            <span v-else class="text-xs text-gray-400">{{ newFeatured.blurb.length }} / 500 字</span>
          </div>
        </div>
        <div class="flex gap-2">
          <button class="cursor-pointer px-4 py-1.5 bg-brand-navy text-white text-sm font-semibold rounded-lg hover:bg-opacity-90" @click="addFeatured">加入</button>
          <button class="cursor-pointer px-4 py-1.5 text-sm text-gray-500 hover:underline" @click="showFeaturedForm = false; featuredErrors = {}">取消</button>
        </div>
      </div>

      <button
        v-if="!showFeaturedForm"
        type="button"
        class="flex cursor-pointer items-center gap-1.5 text-sm text-brand-navy font-medium hover:underline"
        @click="showFeaturedForm = true"
      >
        <span class="text-lg leading-none">+</span> 加入精選課程
      </button>
    </div>
  </AdminModal>
</template>
