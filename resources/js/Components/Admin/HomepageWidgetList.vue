<script setup>
import { computed, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import draggable from 'vuedraggable'
import ColorTokenHint from '@/Components/Admin/ColorTokenHint.vue'

// 首頁區塊 — order, visibility and custom HTML blocks for both columns (002 US23).
//
// Dragging only reorders within a column; a block changes column through the
// form field instead, because built-ins have to stay in the column they were
// written for and guarding a cross-column drag costs more than it saves (D70).
const props = defineProps({
  columns: { type: Object, required: true },
  colorScheme: { type: Object, default: null },
})

const emit = defineEmits(['open-settings'])

const AREA_LABELS = { main: '左欄（主內容）', side: '右欄（側欄）' }

// Built-ins whose CONTENT is editable, each behind a modal (002 FR-084).
// Deliberately not "every built-in": `popular_posts` and `blog` have nothing
// to configure, and a 設定 button that opens an empty panel is worse than no
// button at all.
const SETTINGS_KEYS = ['featured_courses', 'social', 'course_catalog']

const lists = ref({ main: [], side: [] })

// Re-seed from the server on every visit, so a failed save never leaves the
// list showing an order the database does not have.
watch(() => props.columns, cols => {
  lists.value = {
    main: [...(cols.main ?? [])],
    side: [...(cols.side ?? [])],
  }
}, { immediate: true, deep: true })

function onReorder(area) {
  router.post('/admin/homepage/widgets/reorder', {
    area,
    ids: lists.value[area].map(w => w.id),
  }, { preserveScroll: true })
}

function toggleVisibility(widget) {
  router.patch(`/admin/homepage/widgets/${widget.id}/visibility`, {
    is_visible: !widget.is_visible,
  }, { preserveScroll: true })
}

function destroy(widget) {
  if (! confirm(`確定要刪除「${widget.title || '未命名區塊'}」？刪除後無法復原。`)) return

  router.delete(`/admin/homepage/widgets/${widget.id}`, { preserveScroll: true })
}

// ─── Create / edit form ──────────────────────────────────────────────────────

const form = ref(null)
const errors = ref({})
const saving = ref(false)

function openCreate(area) {
  errors.value = {}
  form.value = { id: null, title: '', html: '', area }
}

function openEdit(widget) {
  errors.value = {}
  form.value = {
    id: widget.id,
    title: widget.title ?? '',
    html: widget.html ?? '',
    area: widget.area,
  }
}

function closeForm() {
  form.value = null
  errors.value = {}
}

function save() {
  saving.value = true

  const payload = {
    title: form.value.title || null,
    html: form.value.html,
    area: form.value.area,
  }

  const options = {
    preserveScroll: true,
    onSuccess: () => closeForm(),
    onError: e => { errors.value = e },
    onFinish: () => { saving.value = false },
  }

  if (form.value.id) {
    router.put(`/admin/homepage/widgets/${form.value.id}`, payload, options)
  } else {
    router.post('/admin/homepage/widgets', payload, options)
  }
}

const isEditing = computed(() => !! form.value?.id)
</script>

<template>
  <section class="bg-white rounded-xl border border-gray-200 p-6 space-y-5">
    <div>
      <h2 class="text-lg font-semibold text-gray-800">首頁區塊</h2>
      <p class="mt-1 text-xs text-gray-400">
        拖曳調整各欄區塊的上下順序，放開後自動儲存。預設區塊只能排序與開關顯示；自訂區塊可以編輯與刪除。
        右欄的區塊同時出現在首頁與每一篇部落格文章。
      </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
      <div v-for="(label, area) in AREA_LABELS" :key="area" class="space-y-2">
        <h3 class="text-sm font-semibold text-gray-600">{{ label }}</h3>

        <draggable
          v-model="lists[area]"
          item-key="id"
          handle=".drag-handle"
          class="space-y-2"
          @end="onReorder(area)"
        >
          <template #item="{ element: w }">
            <div
              class="flex items-center gap-3 border border-gray-200 rounded-lg px-3 py-2.5"
              :class="w.is_visible ? '' : 'opacity-50'"
            >
              <span class="drag-handle cursor-move select-none text-gray-400 hover:text-gray-600" title="拖曳排序">⠿</span>

              <span class="min-w-0 flex-1">
                <span class="block truncate text-sm font-medium text-gray-700">
                  {{ w.title || '未命名區塊' }}
                </span>
                <span v-if="w.type === 'html'" class="text-[10px] text-brand-teal">自訂 HTML</span>
                <span v-else class="text-[10px] text-gray-400">預設區塊</span>
              </span>

              <button
                type="button"
                class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer items-center rounded-full transition-colors"
                :class="w.is_visible ? 'bg-brand-navy' : 'bg-gray-300'"
                :title="w.is_visible ? '顯示中，點擊隱藏' : '已隱藏，點擊顯示'"
                @click="toggleVisibility(w)"
              >
                <span
                  class="inline-block h-3.5 w-3.5 transform rounded-full bg-white transition-transform"
                  :class="w.is_visible ? 'translate-x-5' : 'translate-x-1'"
                />
              </button>

              <button
                v-if="SETTINGS_KEYS.includes(w.key)"
                type="button"
                class="cursor-pointer shrink-0 text-xs text-gray-500 hover:text-brand-navy hover:underline"
                @click="emit('open-settings', w.key)"
              >設定</button>

              <template v-if="w.type === 'html'">
                <button
                  type="button"
                  class="cursor-pointer shrink-0 text-xs text-gray-500 hover:text-brand-navy hover:underline"
                  @click="openEdit(w)"
                >編輯</button>
                <button
                  type="button"
                  class="cursor-pointer shrink-0 text-xs text-gray-400 hover:text-red-600 hover:underline"
                  @click="destroy(w)"
                >刪除</button>
              </template>
            </div>
          </template>
        </draggable>

        <button
          type="button"
          class="flex cursor-pointer items-center gap-1.5 text-sm font-medium text-brand-navy hover:underline"
          @click="openCreate(area)"
        >
          <span class="text-lg leading-none">+</span> 在此欄新增自訂區塊
        </button>
      </div>
    </div>

    <!-- Create / edit custom block -->
    <div v-if="form" class="space-y-4 rounded-lg border border-brand-navy/20 bg-gray-50 p-4">
      <h3 class="text-sm font-semibold text-gray-700">
        {{ isEditing ? '編輯自訂區塊' : '新增自訂區塊' }}
      </h3>

      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-500">標題（選填）</label>
          <input
            v-model="form.title"
            type="text"
            maxlength="100"
            placeholder="留空則只顯示下方 HTML，不加標題框"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-navy"
          />
          <p v-if="errors.title" class="mt-1 text-sm text-red-600">{{ errors.title }}</p>
        </div>

        <div>
          <label class="mb-1 block text-xs font-medium text-gray-500">放在哪一欄</label>
          <select
            v-model="form.area"
            class="w-full cursor-pointer rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-navy"
          >
            <option v-for="(label, area) in AREA_LABELS" :key="area" :value="area">{{ label }}</option>
          </select>
          <p v-if="errors.area" class="mt-1 text-sm text-red-600">{{ errors.area }}</p>
        </div>
      </div>

      <div>
        <label class="mb-1 block text-xs font-medium text-gray-500">HTML 內容</label>
        <textarea
          v-model="form.html"
          rows="8"
          maxlength="20000"
          placeholder="<p style=&quot;color: var(--color-brand-teal)&quot;>你的內容</p>"
          class="w-full rounded-lg border border-gray-300 px-3 py-2 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-brand-navy"
        ></textarea>
        <p v-if="errors.html" class="mt-1 text-sm text-red-600">{{ errors.html }}</p>
        <p class="mt-1 text-xs text-gray-400">{{ form.html.length }} / 20000</p>
      </div>

      <ColorTokenHint v-if="colorScheme" :scheme="colorScheme" />

      <div class="flex items-center gap-3">
        <button
          type="button"
          :disabled="saving"
          class="cursor-pointer rounded-lg bg-brand-navy px-5 py-2 text-sm font-semibold text-white hover:bg-opacity-90 disabled:opacity-50"
          @click="save"
        >
          {{ saving ? '儲存中…' : (isEditing ? '儲存變更' : '新增區塊') }}
        </button>
        <button
          type="button"
          class="cursor-pointer text-sm text-gray-500 hover:text-gray-700 hover:underline"
          @click="closeForm"
        >取消</button>
      </div>
    </div>
  </section>
</template>
