<script setup>
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import ColorSchemePicker from '@/Components/Admin/ColorSchemePicker.vue'
import HomepageWidgetList from '@/Components/Admin/HomepageWidgetList.vue'
import FeaturedCoursesModal from '@/Components/Admin/HomepageWidgets/FeaturedCoursesModal.vue'
import SnsLinksModal from '@/Components/Admin/HomepageWidgets/SnsLinksModal.vue'
import ContentCategoriesModal from '@/Components/Admin/HomepageWidgets/ContentCategoriesModal.vue'

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
  promoCourses: {
    type: Array,
    default: () => [],
  },
  siteIdentity: {
    type: Object,
    required: true,
  },
  siteIcons: {
    type: Object,
    default: () => ({}),
  },
  colorSchemes: {
    type: Array,
    default: () => [],
  },
  activeColorScheme: {
    type: String,
    default: '',
  },
  widgetColumns: {
    type: Object,
    default: () => ({ main: [], side: [] }),
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

// ─── Section 0: Site identity ────────────────────────────────────────────────

const identityForm = ref({
  site_name:     props.siteIdentity.name ?? '',
  site_operator: props.siteIdentity.operator ?? '',
  site_address:  props.siteIdentity.address ?? '',
})
const identityErrors = ref({})
const identitySaving = ref(false)

const logoFile = ref(null)
const faviconFile = ref(null)
const logoPreviewUrl = ref(props.siteIcons.logo ?? null)
const faviconPreviewUrl = ref(props.siteIcons.favicon ?? null)

const MAX_ICON_BYTES = 2 * 1024 * 1024 // 2 MB

function onIconSelected(event, which) {
  const file = event.target.files[0]
  if (!file) return

  const field = which === 'logo' ? 'site_logo' : 'site_favicon'
  if (file.size > MAX_ICON_BYTES) {
    identityErrors.value = { ...identityErrors.value, [field]: '圖片檔案過大（上限 2MB）' }
    event.target.value = ''
    return
  }

  delete identityErrors.value[field]
  if (which === 'logo') {
    logoFile.value = file
    logoPreviewUrl.value = URL.createObjectURL(file)
  } else {
    faviconFile.value = file
    faviconPreviewUrl.value = URL.createObjectURL(file)
  }
}

function saveSiteIdentity() {
  identitySaving.value = true
  identityErrors.value = {}

  // multipart because the two icons ride along with the text fields
  const formData = new FormData()
  formData.append('site_name', identityForm.value.site_name)
  formData.append('site_operator', identityForm.value.site_operator)
  formData.append('site_address', identityForm.value.site_address)
  if (logoFile.value) formData.append('site_logo', logoFile.value)
  if (faviconFile.value) formData.append('site_favicon', faviconFile.value)

  router.post('/admin/homepage/site-identity', formData, {
    forceFormData: true,
    preserveScroll: true,
    onError: (errors) => { identityErrors.value = errors },
    onSuccess: () => { logoFile.value = null; faviconFile.value = null },
    onFinish: () => { identitySaving.value = false },
  })
}

function deleteLogo() {
  if (!confirm('確定要刪除網站圖示嗎？導覽列會改回預設圖。')) return
  router.delete('/admin/homepage/site-logo', {
    preserveScroll: true,
    onSuccess: () => { logoPreviewUrl.value = null; logoFile.value = null },
  })
}

function deleteFavicon() {
  if (!confirm('確定要刪除 Favicon 嗎？瀏覽器分頁會改回預設圖示。')) return
  router.delete('/admin/homepage/site-favicon', {
    preserveScroll: true,
    onSuccess: () => { faviconPreviewUrl.value = null; faviconFile.value = null },
  })
}

// ─── Section 2: Hero ─────────────────────────────────────────────────────────

const heroForm = ref({
  hero_title:           props.settings.hero_title ?? '',
  hero_subtitle:        props.settings.hero_subtitle ?? '',
  hero_description:     props.settings.hero_description ?? '',
  hero_promo_course_id: props.settings.hero_promo_course_id ?? '',
  hero_banner:          null,
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
  formData.append('hero_title',           heroForm.value.hero_title)
  formData.append('hero_subtitle',        heroForm.value.hero_subtitle)
  formData.append('hero_description',     heroForm.value.hero_description)
  formData.append('hero_promo_course_id', heroForm.value.hero_promo_course_id ?? '')
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

// ─── Section 3: Homepage blocks ──────────────────────────────────────────────
// Owned by HomepageWidgetList.vue (002 US23); the active palette is passed
// through so the custom-HTML editor can offer its CSS variables.

const activeScheme = computed(
  () => props.colorSchemes.find(s => s.key === props.activeColorScheme) ?? null
)

// Which settings modal is open, by widget key (null = none).
//
// This ref MUST live here rather than inside each modal: Inertia reuses this
// component across a partial reload, so the panel stays open while a save
// round-trips. A ref owned by the modal would be rebuilt and the panel would
// snap shut on every save (002 FR-086).
const openSettings = ref(null)

</script>

<template>
  <div class="max-w-3xl mx-auto px-4 py-8 space-y-10">
    <h1 class="text-2xl font-bold text-gray-900">首頁設定</h1>

    <!-- Section 0: 站台資訊 -->
    <section class="bg-white rounded-xl border border-gray-200 p-6 space-y-6">
      <div>
        <h2 class="text-lg font-semibold text-gray-800">站台資訊</h2>
        <p class="mt-1 text-sm text-gray-500">
          站名會顯示在導航列、頁尾、瀏覽器分頁標題與系統信；經營者與地址會顯示在服務條款與購買須知。
        </p>
      </div>

      <!-- 站名 -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">站名</label>
        <input
          v-model="identityForm.site_name"
          type="text"
          maxlength="100"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-teal focus:border-transparent"
          placeholder="例：線上課程學院"
        />
        <p v-if="identityErrors.site_name" class="mt-1 text-sm text-red-600">{{ identityErrors.site_name }}</p>
      </div>

      <!-- 經營者 -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">經營者</label>
        <input
          v-model="identityForm.site_operator"
          type="text"
          maxlength="255"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-teal focus:border-transparent"
          placeholder="例：某某有限公司"
        />
        <p v-if="identityErrors.site_operator" class="mt-1 text-sm text-red-600">{{ identityErrors.site_operator }}</p>
        <p class="mt-1 text-xs text-gray-400">留空則條款頁不顯示這一行</p>
      </div>

      <!-- 地址 -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">地址</label>
        <input
          v-model="identityForm.site_address"
          type="text"
          maxlength="255"
          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-teal focus:border-transparent"
          placeholder="例：臺北市中正區某某路 1 號"
        />
        <p v-if="identityErrors.site_address" class="mt-1 text-sm text-red-600">{{ identityErrors.site_address }}</p>
        <p class="mt-1 text-xs text-gray-400">留空則條款頁不顯示這一行</p>
      </div>

      <!-- 網站圖示（導覽列左上角） -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">網站圖示（導覽列左上角）</label>
        <div v-if="logoPreviewUrl" class="mb-3 flex items-center gap-3">
          <img :src="logoPreviewUrl" alt="網站圖示預覽" class="h-12 w-12 rounded bg-brand-navy object-contain p-1" />
          <button type="button" class="cursor-pointer text-sm text-red-600 hover:text-red-800" @click="deleteLogo">
            刪除
          </button>
        </div>
        <input
          type="file"
          accept=".png,.jpg,.jpeg,.webp"
          class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200"
          @change="onIconSelected($event, 'logo')"
        />
        <p v-if="identityErrors.site_logo" class="mt-1 text-sm text-red-600">{{ identityErrors.site_logo }}</p>
        <p class="mt-1 text-xs text-gray-400">建議正方形去背 PNG（導覽列是深色底）。未上傳時使用預設圖。最大 2MB</p>
      </div>

      <!-- Favicon -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Favicon（瀏覽器分頁圖示）</label>
        <div v-if="faviconPreviewUrl" class="mb-3 flex items-center gap-3">
          <img :src="faviconPreviewUrl" alt="Favicon 預覽" class="h-8 w-8 rounded border border-gray-200 object-contain" />
          <button type="button" class="cursor-pointer text-sm text-red-600 hover:text-red-800" @click="deleteFavicon">
            刪除
          </button>
        </div>
        <input
          type="file"
          accept=".png,.jpg,.jpeg,.webp"
          class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200"
          @change="onIconSelected($event, 'favicon')"
        />
        <p v-if="identityErrors.site_favicon" class="mt-1 text-sm text-red-600">{{ identityErrors.site_favicon }}</p>
        <p class="mt-1 text-xs text-gray-400">
          上傳一張正方形 PNG 就好，系統會自動縮成 32×32、180×180，並轉出 .ico，不必自己準備多個尺寸。最大 2MB
        </p>
      </div>

      <div class="pt-2">
        <button
          type="button"
          :disabled="identitySaving"
          class="px-5 py-2 bg-brand-navy text-white text-sm font-semibold rounded-lg hover:bg-opacity-90 disabled:opacity-50"
          @click="saveSiteIdentity"
        >
          {{ identitySaving ? '儲存中…' : '儲存站台資訊' }}
        </button>
      </div>
    </section>

    <!-- Section 1: 配色方案 (000 US14).

         Sits here, not further down, because this page splits in two: 站台資訊
         and 配色 apply to every page on the site, while Hero and everything
         below it only affect the homepage. Keeping the two site-wide cards
         together is what makes that boundary visible (000 D47). -->
    <ColorSchemePicker
      v-if="colorSchemes.length"
      :schemes="colorSchemes"
      :active="activeColorScheme"
    />

    <!-- Section 2: Hero 設定 -->
    <section class="bg-white rounded-xl border border-gray-200 p-6 space-y-6">
      <h2 class="text-lg font-semibold text-gray-800">Hero 主視覺</h2>

      <!-- Banner preview -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Hero 形象圖</label>
        <div v-if="bannerPreviewUrl" class="mb-3">
          <img :src="bannerPreviewUrl" alt="Hero image preview" class="max-h-48 w-auto object-contain rounded-lg border border-gray-200" />
          <button
            type="button"
            class="mt-2 text-sm text-red-600 hover:text-red-800"
            @click="deleteBanner"
          >
            刪除形象圖
          </button>
        </div>
        <input
          type="file"
          accept=".jpg,.jpeg,.png,.webp"
          class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200"
          @change="onBannerSelected"
        />
        <p v-if="heroErrors.hero_banner" class="mt-1 text-sm text-red-600">{{ heroErrors.hero_banner }}</p>
        <p class="mt-1 text-xs text-gray-400">顯示於 Hero 右欄，建議直式去背 PNG。JPG / PNG / WebP，最大 5MB</p>
      </div>

      <!-- Title -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">大標</label>
        <textarea
          v-model="heroForm.hero_title"
          rows="2"
          maxlength="255"
          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-navy"
        ></textarea>
        <p class="mt-1 text-xs text-gray-400">換行會原樣顯示在前台</p>
        <p v-if="heroErrors.hero_title" class="mt-1 text-sm text-red-600">{{ heroErrors.hero_title }}</p>
      </div>

      <!-- Subtitle -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">副標</label>
        <input
          v-model="heroForm.hero_subtitle"
          type="text"
          maxlength="255"
          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-navy"
        />
        <p v-if="heroErrors.hero_subtitle" class="mt-1 text-sm text-red-600">{{ heroErrors.hero_subtitle }}</p>
      </div>

      <!-- Description -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">網站介紹</label>
        <textarea
          v-model="heroForm.hero_description"
          rows="4"
          maxlength="2000"
          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-navy"
        />
        <p v-if="heroErrors.hero_description" class="mt-1 text-sm text-red-600">{{ heroErrors.hero_description }}</p>
      </div>

      <!-- 推薦商品：只控制「📌 立刻領取」那行，訂閱表單不受影響 -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">首頁推薦商品</label>
        <select
          v-model="heroForm.hero_promo_course_id"
          class="w-full cursor-pointer rounded-lg border border-gray-300 px-3 py-2 text-sm hover:border-brand-navy focus:outline-none focus:ring-2 focus:ring-brand-navy"
        >
          <option value="">不顯示推薦那行</option>
          <option v-for="c in promoCourses" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
        </select>
        <p class="mt-1 text-xs text-gray-400">指定 Hero 下方「📌 立刻領取」那行要連到哪個商品；留空則該行不顯示（訂閱表單不受影響）。</p>
        <p v-if="heroErrors.hero_promo_course_id" class="mt-1 text-sm text-red-600">{{ heroErrors.hero_promo_course_id }}</p>
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

    <!-- Section 3: 首頁區塊（排序、開關、自訂 HTML；內建區塊的設定走 modal） -->
    <HomepageWidgetList
      :columns="widgetColumns"
      :color-scheme="activeScheme"
      @open-settings="openSettings = $event"
    />

    <FeaturedCoursesModal
      :open="openSettings === 'featured_courses'"
      :featured-courses="featuredCourses"
      :available-courses="availableCourses"
      @close="openSettings = null"
    />
    <SnsLinksModal
      :open="openSettings === 'social'"
      :social-links="socialLinks"
      :sns-profile-intro="settings.sns_profile_intro ?? ''"
      @close="openSettings = null"
    />
    <ContentCategoriesModal
      :open="openSettings === 'course_catalog'"
      :content-category-slots="contentCategorySlots"
      :content-filter-enabled="contentFilterEnabled"
      @close="openSettings = null"
    />
  </div>
</template>
