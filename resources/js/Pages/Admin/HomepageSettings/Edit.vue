<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import draggable from 'vuedraggable'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  settings: {
    type: Object,
    required: true,
  },
  socialLinks: {
    type: Array,
    required: true,
  },
  featuredCourses: {
    type: Array,
    default: () => [],
  },
  availableCourses: {
    type: Array,
    default: () => [],
  },
  sidebarOrder: {
    type: Array,
    default: () => [],
  },
  contentCategorySlots: {
    type: Array,
    default: () => [],
  },
  contentFilterEnabled: {
    type: Boolean,
    default: false,
  },
})

// ─── Section 1: Hero + RSS ───────────────────────────────────────────────────

const heroForm = ref({
  hero_title:          props.settings.hero_title ?? '',
  hero_description:    props.settings.hero_description ?? '',
  hero_button_label:   props.settings.hero_button_label ?? '',
  hero_button_url:     props.settings.hero_button_url ?? '',
  blog_rss_url:        props.settings.blog_rss_url ?? '',
  sns_section_enabled: props.settings.sns_section_enabled ? '1' : '0',
  hero_banner:         null,
})

const bannerPreviewUrl = ref(props.settings.hero_banner_url ?? null)
const heroErrors = ref({})
const heroSaving = ref(false)

function onBannerSelected(event) {
  const file = event.target.files[0]
  if (!file) return

  const MAX_BYTES = 5 * 1024 * 1024 // 5 MB
  if (file.size > MAX_BYTES) {
    heroErrors.value = { ...heroErrors.value, hero_banner: '圖片檔案過大，請壓縮後再上傳（上限 5MB）' }
    event.target.value = ''
    return
  }

  delete heroErrors.value.hero_banner
  heroForm.value.hero_banner = file
  bannerPreviewUrl.value = URL.createObjectURL(file)
}

function saveHeroSettings() {
  heroSaving.value = true
  heroErrors.value = {}

  const formData = new FormData()
  formData.append('hero_title',          heroForm.value.hero_title)
  formData.append('hero_description',    heroForm.value.hero_description)
  formData.append('hero_button_label',   heroForm.value.hero_button_label)
  formData.append('hero_button_url',     heroForm.value.hero_button_url)
  formData.append('blog_rss_url',        heroForm.value.blog_rss_url)
  formData.append('sns_section_enabled', heroForm.value.sns_section_enabled)
  if (heroForm.value.hero_banner) {
    formData.append('hero_banner', heroForm.value.hero_banner)
  }

  router.post('/admin/homepage', formData, {
    forceFormData: true,
    preserveScroll: true,
    onError: (errors) => { heroErrors.value = errors },
    onFinish: () => { heroSaving.value = false },
  })
}

function deleteBanner() {
  if (!confirm('確定要刪除橫幅圖片嗎？')) return
  router.delete('/admin/homepage/banner', {
    preserveScroll: true,
    onSuccess: () => { bannerPreviewUrl.value = null },
  })
}

// ─── Section 2: SNS Links ────────────────────────────────────────────────────

const localLinks = ref(props.socialLinks.map(l => ({ ...l, editing: false, editUrl: l.url })))

const showAddForm = ref(false)
const newLink = ref({ platform: 'instagram', url: '' })
const addErrors = ref({})

const platforms = ['instagram', 'threads', 'youtube', 'facebook', 'blog', 'podcast']

function startEdit(link) {
  link.editing = true
  link.editUrl = link.url
}

function cancelEdit(link) {
  link.editing = false
  link.editUrl = link.url
}

function saveLink(link) {
  router.put(`/admin/homepage/social-links/${link.id}`, { url: link.editUrl }, {
    preserveScroll: true,
    onSuccess: () => {
      link.url = link.editUrl
      link.editing = false
    },
  })
}

function deleteLink(link) {
  if (!confirm(`確定要刪除此 ${link.platform} 連結嗎？`)) return
  router.delete(`/admin/homepage/social-links/${link.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      const idx = localLinks.value.findIndex(l => l.id === link.id)
      if (idx !== -1) localLinks.value.splice(idx, 1)
    },
  })
}

function addLink() {
  addErrors.value = {}
  router.post('/admin/homepage/social-links', {
    platform: newLink.value.platform,
    url:      newLink.value.url,
  }, {
    preserveScroll: true,
    onError: (errors) => { addErrors.value = errors },
    onSuccess: () => {
      showAddForm.value = false
      newLink.value = { platform: 'instagram', url: '' }
    },
  })
}

// ─── Section 3: Featured Courses (right sidebar) ─────────────────────────────

const featured = ref(props.featuredCourses.map(c => ({ ...c, editBlurb: c.blurb ?? '' })))

// Re-sync the local list whenever the server returns fresh props
// (after add / remove / reorder), so no manual refresh is needed.
watch(() => props.featuredCourses, (list) => {
  featured.value = list.map(c => ({ ...c, editBlurb: c.blurb ?? '' }))
})

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
    onError: (errors) => { featuredErrors.value = errors },
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

// ─── Section 4: Sidebar widget order ─────────────────────────────────────────

const widgetLabels = {
  featured_courses: '精選推薦（課程）',
  social:           '追蹤站長（SNS）',
  blog:             '近期文章（Blog）',
}

const widgetOrder = ref([...props.sidebarOrder])

function onWidgetReorder() {
  router.post('/admin/homepage/widget-order', {
    order: widgetOrder.value,
  }, { preserveScroll: true })
}

// ─── Section 5: Content categories (homepage type filter) ────────────────────

const categoryForm = ref({
  enabled: props.contentFilterEnabled,
  categories: props.contentCategorySlots.map(c => ({ label: c.label ?? '', slug: c.slug ?? '' })),
})
const categoryErrors = ref({})
const categorySaving = ref(false)

function saveCategories() {
  categorySaving.value = true
  categoryErrors.value = {}
  router.post('/admin/homepage/content-categories', {
    enabled: categoryForm.value.enabled,
    categories: categoryForm.value.categories,
  }, {
    preserveScroll: true,
    onError: (errors) => { categoryErrors.value = errors },
    onFinish: () => { categorySaving.value = false },
  })
}
</script>

<template>
  <div class="max-w-3xl mx-auto px-4 py-8 space-y-10">
    <h1 class="text-2xl font-bold text-gray-900">首頁設定</h1>

    <!-- Section 1: Hero 設定 -->
    <section class="bg-white rounded-xl border border-gray-200 p-6 space-y-6">
      <h2 class="text-lg font-semibold text-gray-800">Hero 主視覺</h2>

      <!-- Banner preview -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">橫幅圖片</label>
        <div v-if="bannerPreviewUrl" class="mb-3">
          <img :src="bannerPreviewUrl" alt="Banner preview" class="w-full max-h-48 object-cover rounded-lg border border-gray-200" />
          <button
            type="button"
            class="mt-2 text-sm text-red-600 hover:text-red-800"
            @click="deleteBanner"
          >
            刪除橫幅圖片
          </button>
        </div>
        <input
          type="file"
          accept=".jpg,.jpeg,.png,.webp"
          class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200"
          @change="onBannerSelected"
        />
        <p v-if="heroErrors.hero_banner" class="mt-1 text-sm text-red-600">{{ heroErrors.hero_banner }}</p>
        <p class="mt-1 text-xs text-gray-400">JPG / PNG / WebP，最大 5MB，寬度至少 1200px</p>
      </div>

      <!-- Title -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">標題</label>
        <input
          v-model="heroForm.hero_title"
          type="text"
          maxlength="255"
          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-navy"
        />
        <p v-if="heroErrors.hero_title" class="mt-1 text-sm text-red-600">{{ heroErrors.hero_title }}</p>
      </div>

      <!-- Description -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">說明文字</label>
        <textarea
          v-model="heroForm.hero_description"
          rows="4"
          maxlength="2000"
          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-navy"
        />
        <p v-if="heroErrors.hero_description" class="mt-1 text-sm text-red-600">{{ heroErrors.hero_description }}</p>
      </div>

      <!-- Button label + URL -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">按鈕文字</label>
          <input
            v-model="heroForm.hero_button_label"
            type="text"
            maxlength="100"
            placeholder="例：EXPLORE"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-navy"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">按鈕連結</label>
          <input
            v-model="heroForm.hero_button_url"
            type="url"
            maxlength="500"
            placeholder="https://"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-navy"
          />
          <p v-if="heroErrors.hero_button_url" class="mt-1 text-sm text-red-600">{{ heroErrors.hero_button_url }}</p>
        </div>
      </div>

      <!-- SNS toggle -->
      <div class="flex items-center gap-3">
        <label class="text-sm font-medium text-gray-700">顯示 SNS 區塊</label>
        <button
          type="button"
          class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
          :class="heroForm.sns_section_enabled === '1' ? 'bg-brand-navy' : 'bg-gray-300'"
          @click="heroForm.sns_section_enabled = heroForm.sns_section_enabled === '1' ? '0' : '1'"
        >
          <span
            class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
            :class="heroForm.sns_section_enabled === '1' ? 'translate-x-6' : 'translate-x-1'"
          />
        </button>
        <span class="text-sm text-gray-500">{{ heroForm.sns_section_enabled === '1' ? '顯示' : '不顯示' }}</span>
      </div>

      <!-- Blog RSS URL -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Blog RSS 網址</label>
        <input
          v-model="heroForm.blog_rss_url"
          type="url"
          maxlength="500"
          placeholder="https://example.com/feed"
          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-navy"
        />
        <p class="mt-1 text-xs text-gray-400">留空則隱藏「近期文章」區塊</p>
        <p v-if="heroErrors.blog_rss_url" class="mt-1 text-sm text-red-600">{{ heroErrors.blog_rss_url }}</p>
      </div>

      <div class="pt-2">
        <button
          type="button"
          :disabled="heroSaving"
          class="px-5 py-2 bg-brand-navy text-white text-sm font-semibold rounded-lg hover:bg-opacity-90 disabled:opacity-50"
          @click="saveHeroSettings"
        >
          {{ heroSaving ? '儲存中…' : '儲存設定' }}
        </button>
      </div>
    </section>

    <!-- Section 2: SNS 連結 -->
    <section class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
      <h2 class="text-lg font-semibold text-gray-800">SNS 連結</h2>

      <div v-if="localLinks.length === 0" class="text-sm text-gray-400">尚未新增任何連結</div>

      <div v-for="link in localLinks" :key="link.id" class="flex items-start gap-3 border-b border-gray-100 pb-3 last:border-0">
        <span class="mt-2 w-20 shrink-0 text-xs font-medium text-gray-500 uppercase">{{ link.platform }}</span>

        <template v-if="link.editing">
          <input
            v-model="link.editUrl"
            type="url"
            class="flex-1 rounded-lg border border-gray-300 px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-brand-navy"
            @keyup.enter="saveLink(link)"
            @keyup.escape="cancelEdit(link)"
          />
          <button class="text-sm text-brand-navy font-medium hover:underline" @click="saveLink(link)">儲存</button>
          <button class="text-sm text-gray-500 hover:underline" @click="cancelEdit(link)">取消</button>
        </template>
        <template v-else>
          <span class="flex-1 truncate text-sm text-gray-700 mt-2">{{ link.url }}</span>
          <button class="text-sm text-brand-navy hover:underline" @click="startEdit(link)">Edit</button>
          <button class="text-sm text-red-500 hover:underline" @click="deleteLink(link)">刪除</button>
        </template>
      </div>

      <!-- Add form -->
      <div v-if="showAddForm" class="border border-gray-200 rounded-lg p-4 space-y-3">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <select
            v-model="newLink.platform"
            class="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-navy"
          >
            <option v-for="p in platforms" :key="p" :value="p">{{ p }}</option>
          </select>
          <input
            v-model="newLink.url"
            type="url"
            placeholder="https://"
            class="sm:col-span-2 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-navy"
            @keyup.enter="addLink"
          />
        </div>
        <p v-if="addErrors.url" class="text-sm text-red-600">{{ addErrors.url }}</p>
        <p v-if="addErrors.platform" class="text-sm text-red-600">{{ addErrors.platform }}</p>
        <div class="flex gap-2">
          <button class="px-4 py-1.5 bg-brand-navy text-white text-sm font-semibold rounded-lg hover:bg-opacity-90" @click="addLink">新增</button>
          <button class="px-4 py-1.5 text-sm text-gray-500 hover:underline" @click="showAddForm = false; addErrors = {}">取消</button>
        </div>
      </div>

      <button
        v-if="!showAddForm"
        type="button"
        class="flex items-center gap-1.5 text-sm text-brand-navy font-medium hover:underline"
        @click="showAddForm = true"
      >
        <span class="text-lg leading-none">+</span> 新增連結
      </button>
    </section>

    <!-- Section 3: 精選課程（右欄） -->
    <section class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
      <div>
        <h2 class="text-lg font-semibold text-gray-800">精選課程（首頁右欄）</h2>
        <p class="mt-1 text-xs text-gray-400">顯示縮圖 + 自訂介紹 + 進入銷售頁按鈕。可拖曳排序。</p>
      </div>

      <div v-if="featured.length === 0" class="text-sm text-gray-400">尚未加入任何精選課程</div>

      <draggable
        v-model="featured"
        item-key="id"
        handle=".drag-handle"
        class="space-y-3"
        @end="onFeaturedReorder"
      >
        <template #item="{ element: item }">
          <div class="flex items-start gap-3 border border-gray-200 rounded-lg p-3">
            <span class="drag-handle mt-1 cursor-move select-none text-gray-400 hover:text-gray-600" title="拖曳排序">⠿</span>
            <div class="flex-1 min-w-0 space-y-2">
              <div class="flex items-center gap-3">
                <img
                  v-if="item.thumbnail"
                  :src="item.thumbnail"
                  :alt="item.name"
                  class="w-24 h-14 shrink-0 object-cover rounded border border-gray-200"
                />
                <div v-else class="w-24 h-14 shrink-0 rounded border border-gray-200 bg-gray-100" />
                <p class="flex-1 text-sm font-medium text-gray-800 truncate">{{ item.name }}</p>
                <button
                  class="text-sm text-red-500 hover:underline shrink-0"
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
                  class="px-4 py-1.5 bg-brand-navy text-white text-sm font-semibold rounded-lg hover:bg-opacity-90"
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
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-navy"
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
          <button class="px-4 py-1.5 bg-brand-navy text-white text-sm font-semibold rounded-lg hover:bg-opacity-90" @click="addFeatured">加入</button>
          <button class="px-4 py-1.5 text-sm text-gray-500 hover:underline" @click="showFeaturedForm = false; featuredErrors = {}">取消</button>
        </div>
      </div>

      <button
        v-if="!showFeaturedForm"
        type="button"
        class="flex items-center gap-1.5 text-sm text-brand-navy font-medium hover:underline"
        @click="showFeaturedForm = true"
      >
        <span class="text-lg leading-none">+</span> 加入精選課程
      </button>
    </section>

    <!-- Section 4: 右欄排序 -->
    <section class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
      <div>
        <h2 class="text-lg font-semibold text-gray-800">右欄區塊排序</h2>
        <p class="mt-1 text-xs text-gray-400">拖曳調整首頁右欄各區塊的上下順序，放開後自動儲存。</p>
      </div>

      <draggable
        v-model="widgetOrder"
        item-key="self"
        handle=".drag-handle"
        class="space-y-2"
        @end="onWidgetReorder"
      >
        <template #item="{ element: key }">
          <div class="flex items-center gap-3 border border-gray-200 rounded-lg px-3 py-2.5">
            <span class="drag-handle cursor-move select-none text-gray-400 hover:text-gray-600" title="拖曳排序">⠿</span>
            <span class="text-sm font-medium text-gray-700">{{ widgetLabels[key] ?? key }}</span>
          </div>
        </template>
      </draggable>
    </section>

    <!-- Section 5: 內容分類（首頁類型過濾按鈕） -->
    <section class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
      <div>
        <h2 class="text-lg font-semibold text-gray-800">內容分類（首頁過濾按鈕）</h2>
        <p class="mt-1 text-xs text-gray-400">最多 3 組；有填寫「顯示文字＋英文名」的才會在首頁顯示按鈕。英文名只能用小寫英文與「-」。</p>
      </div>

      <!-- Visibility toggle -->
      <div class="flex items-center gap-3">
        <label class="text-sm font-medium text-gray-700">在首頁顯示分類過濾按鈕</label>
        <button
          type="button"
          class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
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
          class="px-5 py-2 bg-brand-navy text-white text-sm font-semibold rounded-lg hover:bg-opacity-90 disabled:opacity-50"
          @click="saveCategories"
        >
          {{ categorySaving ? '儲存中…' : '儲存分類設定' }}
        </button>
      </div>
    </section>
  </div>
</template>
