<script setup>
import { useForm, router } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'
import ImageGalleryModal from './ImageGalleryModal.vue'

const props = defineProps({
  course: {
    type: Object,
    default: null,
  },
  images: {
    type: Array,
    default: () => [],
  },
  availableCourses: {
    type: Array,
    default: () => [],
  },
  courseLessons: {
    type: Array,
    default: () => [],
  },
  submitUrl: {
    type: String,
    required: true,
  },
  method: {
    type: String,
    default: 'post',
  },
})

const emit = defineEmits(['submitted'])

const form = useForm({
  name: props.course?.name || '',
  tagline: props.course?.tagline || '',
  description: props.course?.description || '',
  description_html: props.course?.description_html || '',
  price: props.course?.price || '',
  original_price: props.course?.original_price || '',
  promo_ends_at: props.course?.promo_ends_at || '',
  thumbnail: null,
  instructor_name: props.course?.instructor_name || '',
  type: props.course?.type || 'lecture',
  duration_minutes: props.course?.duration_minutes || '',
  sale_at: props.course?.sale_at || '',
  portaly_product_id: props.course?.portaly_product_id || '',
  is_visible: props.course?.is_visible ?? true,
  course_type: props.course?.course_type || 'standard',
  drip_interval_days: props.course?.drip_interval_days || '',
  target_course_ids: props.course?.target_course_ids || [],
})

const isDrip = computed(() => form.course_type === 'drip')

// Schedule preview for drip courses
const schedulePreview = computed(() => {
  if (!isDrip.value || !form.drip_interval_days || props.courseLessons.length === 0) return []
  const interval = parseInt(form.drip_interval_days) || 1
  return props.courseLessons.map((lesson, index) => ({
    title: lesson.title,
    day: index * interval,
  }))
})

// Image gallery modal
const showImageGallery = ref(false)
const descriptionHtmlTextarea = ref(null)

const openImageGallery = () => {
  showImageGallery.value = true
}

const closeImageGallery = () => {
  showImageGallery.value = false
}

const insertImageHtml = (html) => {
  const textarea = descriptionHtmlTextarea.value
  if (!textarea) {
    form.description_html += html
    return
  }

  const start = textarea.selectionStart
  const end = textarea.selectionEnd
  const text = form.description_html

  form.description_html = text.substring(0, start) + html + text.substring(end)

  // Move cursor after inserted HTML
  setTimeout(() => {
    textarea.focus()
    textarea.selectionStart = textarea.selectionEnd = start + html.length
  }, 0)
}

const thumbnailPreview = ref(props.course?.thumbnail ? `/storage/${props.course.thumbnail}` : null)

const courseTypes = [
  { value: 'lecture', label: '講座課程' },
  { value: 'mini', label: '迷你課程' },
  { value: 'full', label: '完整課程' },
]

const handleThumbnailChange = (event) => {
  const file = event.target.files[0]
  if (file) {
    form.thumbnail = file
    thumbnailPreview.value = URL.createObjectURL(file)
  }
}

const submit = () => {
  if (props.method === 'put') {
    form.transform((data) => ({
      ...data,
      _method: 'put',
    })).post(props.submitUrl, {
      forceFormData: true,
      preserveScroll: true,
    })
  } else {
    form.post(props.submitUrl, {
      forceFormData: true,
      preserveScroll: true,
    })
  }
}

// Input classes for consistent styling
const inputClasses = 'mt-2 block w-full rounded-lg border-gray-300 px-4 py-3 text-base shadow-sm transition-colors focus:border-indigo-500 focus:ring-indigo-500'
const inputErrorClasses = 'border-red-300 focus:border-red-500 focus:ring-red-500'
const labelClasses = 'block text-sm font-semibold text-gray-900'
const helpTextClasses = 'mt-2 text-sm text-gray-500'
const errorTextClasses = 'mt-2 text-sm text-red-600'
</script>

<template>
  <form @submit.prevent="submit" class="space-y-8">
    <!-- Basic Info -->
    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl">
      <div class="px-6 py-6 sm:p-8">
        <div class="border-b border-gray-200 pb-6 mb-8">
          <h3 class="text-xl font-semibold text-gray-900">基本資訊</h3>
          <p class="mt-1 text-sm text-gray-500">課程的基本資料設定。</p>
        </div>

        <div class="space-y-8">
          <!-- Course Mode -->
          <div>
            <label :class="labelClasses">
              課程模式 <span class="text-red-500">*</span>
            </label>
            <div class="mt-3 flex gap-4">
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  v-model="form.course_type"
                  type="radio"
                  value="standard"
                  class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500"
                />
                <span class="text-sm text-gray-700">一般課程</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  v-model="form.course_type"
                  type="radio"
                  value="drip"
                  class="h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500"
                />
                <span class="text-sm text-gray-700">連鎖課程</span>
              </label>
            </div>
            <p v-if="isDrip" class="mt-2 text-sm text-amber-600">連鎖課程為免費訂閱制，訪客輸入 Email 或會員一鍵即可訂閱。</p>
            <p v-if="form.errors.course_type" :class="errorTextClasses">{{ form.errors.course_type }}</p>
          </div>

          <!-- Name -->
          <div>
            <label for="name" :class="labelClasses">
              課程名稱 <span class="text-red-500">*</span>
            </label>
            <input
              id="name"
              v-model="form.name"
              type="text"
              placeholder="輸入課程名稱"
              :class="[inputClasses, form.errors.name ? inputErrorClasses : '']"
            />
            <p v-if="form.errors.name" :class="errorTextClasses">{{ form.errors.name }}</p>
          </div>

          <!-- Tagline -->
          <div>
            <label for="tagline" :class="labelClasses">
              副標題 <span class="text-red-500">*</span>
            </label>
            <input
              id="tagline"
              v-model="form.tagline"
              type="text"
              placeholder="簡短描述課程特色"
              :class="[inputClasses, form.errors.tagline ? inputErrorClasses : '']"
            />
            <p v-if="form.errors.tagline" :class="errorTextClasses">{{ form.errors.tagline }}</p>
          </div>

          <!-- Description -->
          <div>
            <label for="description" :class="labelClasses">
              課程描述 <span class="text-red-500">*</span>
            </label>
            <textarea
              id="description"
              v-model="form.description"
              rows="4"
              placeholder="詳細說明課程內容與學習目標"
              :class="[inputClasses, form.errors.description ? inputErrorClasses : '']"
            />
            <p v-if="form.errors.description" :class="errorTextClasses">{{ form.errors.description }}</p>
          </div>

          <!-- Two columns for Instructor & Type -->
          <div class="grid grid-cols-1 gap-8 sm:grid-cols-2">
            <!-- Instructor Name -->
            <div>
              <label for="instructor_name" :class="labelClasses">
                講師名稱 <span class="text-red-500">*</span>
              </label>
              <input
                id="instructor_name"
                v-model="form.instructor_name"
                type="text"
                placeholder="講師姓名"
                :class="[inputClasses, form.errors.instructor_name ? inputErrorClasses : '']"
              />
              <p v-if="form.errors.instructor_name" :class="errorTextClasses">{{ form.errors.instructor_name }}</p>
            </div>

            <!-- Type -->
            <div>
              <label for="type" :class="labelClasses">
                課程類型 <span class="text-red-500">*</span>
              </label>
              <select
                id="type"
                v-model="form.type"
                :class="[inputClasses, form.errors.type ? inputErrorClasses : '']"
              >
                <option v-for="type in courseTypes" :key="type.value" :value="type.value">
                  {{ type.label }}
                </option>
              </select>
              <p v-if="form.errors.type" :class="errorTextClasses">{{ form.errors.type }}</p>
            </div>
          </div>

          <!-- Thumbnail -->
          <div>
            <label :class="labelClasses">課程縮圖</label>
            <div class="mt-3 flex items-center gap-6">
              <div class="flex-shrink-0 h-32 w-32 overflow-hidden rounded-xl bg-gray-100 ring-1 ring-gray-200">
                <img
                  v-if="thumbnailPreview"
                  :src="thumbnailPreview"
                  class="h-full w-full object-cover"
                />
                <div v-else class="h-full w-full flex items-center justify-center">
                  <svg class="h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                </div>
              </div>
              <div>
                <label class="cursor-pointer inline-flex items-center gap-2 bg-white px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                  <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                  </svg>
                  選擇圖片
                  <input type="file" class="sr-only" accept="image/*" @change="handleThumbnailChange" />
                </label>
                <p class="mt-2 text-xs text-gray-500">支援 JPG、PNG、GIF，最大 10MB</p>
              </div>
            </div>
            <p v-if="form.errors.thumbnail" :class="errorTextClasses">{{ form.errors.thumbnail }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Pricing & Duration (standard courses only) -->
    <div v-if="!isDrip" class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl">
      <div class="px-6 py-6 sm:p-8">
        <div class="border-b border-gray-200 pb-6 mb-8">
          <h3 class="text-xl font-semibold text-gray-900">價格與時長</h3>
          <p class="mt-1 text-sm text-gray-500">設定課程價格和預計學習時間。</p>
        </div>

        <div class="space-y-8">
          <!-- Promotional Pricing -->
          <div class="grid grid-cols-1 gap-8 sm:grid-cols-2">
            <!-- Price (優惠價) -->
            <div>
              <label for="price" :class="labelClasses">
                優惠價 (TWD) <span class="text-red-500">*</span>
              </label>
              <div class="mt-2 relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                  <span class="text-gray-500 text-base">$</span>
                </div>
                <input
                  id="price"
                  v-model="form.price"
                  type="number"
                  step="1"
                  min="0"
                  placeholder="0"
                  class="pl-8 block w-full rounded-lg border-gray-300 px-4 py-3 text-base shadow-sm transition-colors focus:border-indigo-500 focus:ring-indigo-500"
                  :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': form.errors.price }"
                />
              </div>
              <p :class="helpTextClasses">實際售價（Portaly 上須手動同步）</p>
              <p v-if="form.errors.price" :class="errorTextClasses">{{ form.errors.price }}</p>
            </div>

            <!-- Original Price (原價) -->
            <div>
              <label for="original_price" :class="labelClasses">原價 (TWD)</label>
              <div class="mt-2 relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                  <span class="text-gray-500 text-base">$</span>
                </div>
                <input
                  id="original_price"
                  v-model="form.original_price"
                  type="number"
                  step="1"
                  min="0"
                  placeholder="0"
                  class="pl-8 block w-full rounded-lg border-gray-300 px-4 py-3 text-base shadow-sm transition-colors focus:border-indigo-500 focus:ring-indigo-500"
                  :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': form.errors.original_price }"
                />
              </div>
              <p :class="helpTextClasses">留空則不顯示優惠倒數</p>
              <p v-if="form.errors.original_price" :class="errorTextClasses">{{ form.errors.original_price }}</p>
            </div>
          </div>

          <!-- Promo Ends At & Duration -->
          <div class="grid grid-cols-1 gap-8 sm:grid-cols-2">
            <!-- Promo Ends At -->
            <div>
              <label for="promo_ends_at" :class="labelClasses">優惠到期時間</label>
              <input
                id="promo_ends_at"
                v-model="form.promo_ends_at"
                type="datetime-local"
                :class="[inputClasses, form.errors.promo_ends_at ? inputErrorClasses : '']"
              />
              <p :class="helpTextClasses">新增時若填原價但未填到期時間，預設為 30 天後</p>
              <p v-if="form.errors.promo_ends_at" :class="errorTextClasses">{{ form.errors.promo_ends_at }}</p>
            </div>

            <!-- Duration -->
            <div>
              <label for="duration_minutes" :class="labelClasses">時間總長（分鐘）</label>
              <input
                id="duration_minutes"
                v-model="form.duration_minutes"
                type="number"
                min="0"
                placeholder="例如：190"
                :class="[inputClasses, form.errors.duration_minutes ? inputErrorClasses : '']"
              />
              <p :class="helpTextClasses">190 分鐘會顯示為「3小時10分鐘」</p>
              <p v-if="form.errors.duration_minutes" :class="errorTextClasses">{{ form.errors.duration_minutes }}</p>
            </div>
          </div>

          <!-- Sale At -->
          <div>
            <label for="sale_at" :class="labelClasses">預購開賣時間（選填）</label>
            <input
              id="sale_at"
              v-model="form.sale_at"
              type="datetime-local"
              :class="[inputClasses, form.errors.sale_at ? inputErrorClasses : '']"
            />
            <p :class="helpTextClasses">設定後，發佈時將自動設為「預購中」，時間到會自動切換為「熱賣中」。</p>
            <p v-if="form.errors.sale_at" :class="errorTextClasses">{{ form.errors.sale_at }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Description HTML -->
    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl">
      <div class="px-6 py-6 sm:p-8">
        <div class="border-b border-gray-200 pb-6 mb-8">
          <h3 class="text-xl font-semibold text-gray-900">課程介紹 HTML</h3>
          <p class="mt-1 text-sm text-gray-500">
            自定義 HTML 內容，將顯示於課程販售頁面。
          </p>
        </div>

        <div>
          <!-- Toolbar -->
          <div v-if="course" class="flex items-center gap-3 mb-3">
            <button
              type="button"
              class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
              @click="openImageGallery"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              插入圖片
            </button>
            <a
              :href="`/admin/courses/${course.id}/images`"
              class="text-sm text-indigo-600 hover:text-indigo-500 font-medium"
            >
              前往相簿管理 →
            </a>
          </div>

          <textarea
            ref="descriptionHtmlTextarea"
            v-model="form.description_html"
            rows="12"
            placeholder="<h2>課程特色</h2>&#10;<p>這是一門精心設計的課程...</p>"
            class="block w-full rounded-lg border-gray-300 px-4 py-3 text-base shadow-sm transition-colors focus:border-indigo-500 focus:ring-indigo-500 font-mono text-sm leading-relaxed"
            :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': form.errors.description_html }"
          />
          <p v-if="form.errors.description_html" :class="errorTextClasses">{{ form.errors.description_html }}</p>
        </div>
      </div>
    </div>

    <!-- Image Gallery Modal -->
    <ImageGalleryModal
      v-if="course"
      :course-id="course.id"
      :images="images"
      :show="showImageGallery"
      @close="closeImageGallery"
      @insert="insertImageHtml"
    />

    <!-- Portaly Integration (standard courses only) -->
    <div v-if="!isDrip" class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl">
      <div class="px-6 py-6 sm:p-8">
        <div class="border-b border-gray-200 pb-6 mb-8">
          <h3 class="text-xl font-semibold text-gray-900">Portaly 整合</h3>
          <p class="mt-1 text-sm text-gray-500">連結 Portaly 商品頁面以啟用購買功能。</p>
        </div>

        <div>
          <label for="portaly_product_id" :class="labelClasses">Portaly 商品 ID</label>
          <input
            id="portaly_product_id"
            v-model="form.portaly_product_id"
            type="text"
            placeholder="例如：LaHt56zWV8VlHbMnXbvQ"
            :class="[inputClasses, form.errors.portaly_product_id ? inputErrorClasses : '']"
          />
          <p :class="helpTextClasses">
            輸入商品 ID 後，系統會自動產生購買連結：
            <code class="bg-gray-100 px-1.5 py-0.5 rounded text-xs">https://portaly.cc/kyontw/product/{ID}</code>
          </p>
          <p v-if="form.errors.portaly_product_id" :class="errorTextClasses">{{ form.errors.portaly_product_id }}</p>
        </div>
      </div>
    </div>

    <!-- Visibility Settings -->
    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl">
      <div class="px-6 py-6 sm:p-8">
        <div class="border-b border-gray-200 pb-6 mb-8">
          <h3 class="text-xl font-semibold text-gray-900">顯示設定</h3>
          <p class="mt-1 text-sm text-gray-500">控制課程在首頁的顯示狀態。</p>
        </div>

        <div class="flex items-start">
          <div class="flex items-center h-6">
            <input
              id="is_visible"
              v-model="form.is_visible"
              type="checkbox"
              class="h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
            />
          </div>
          <div class="ml-3">
            <label for="is_visible" class="text-sm font-semibold text-gray-900 cursor-pointer">
              是否顯示於首頁
            </label>
            <p class="text-sm text-gray-500 mt-1">
              關閉後課程不會出現在首頁，但仍可透過網址存取和購買
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Drip Course Settings (drip courses only) -->
    <div v-if="isDrip" class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl">
      <div class="px-6 py-6 sm:p-8">
        <div class="border-b border-gray-200 pb-6 mb-8">
          <h3 class="text-xl font-semibold text-gray-900">連鎖課程設定</h3>
          <p class="mt-1 text-sm text-gray-500">訂閱者將依照間隔天數自動收到 Lesson 通知。</p>
        </div>

        <div class="space-y-8">
          <!-- Interval Days -->
          <div>
            <label for="drip_interval_days" :class="labelClasses">
              發信間隔天數 <span class="text-red-500">*</span>
            </label>
            <input
              id="drip_interval_days"
              v-model="form.drip_interval_days"
              type="number"
              min="1"
              max="30"
              placeholder="例如：3"
              :class="[inputClasses, form.errors.drip_interval_days ? inputErrorClasses : '']"
            />
            <p :class="helpTextClasses">每隔幾天發送一封 Lesson 通知信（1-30 天）</p>
            <p v-if="form.errors.drip_interval_days" :class="errorTextClasses">{{ form.errors.drip_interval_days }}</p>
          </div>

          <!-- Target Courses -->
          <div>
            <label :class="labelClasses">目標課程（行銷漏斗）</label>
            <p :class="helpTextClasses" class="!mt-1 mb-3">訂閱者購買以下任一課程後，連鎖課程將自動標記為已轉換，停止發信並解鎖全部內容。</p>
            <div v-if="availableCourses.length > 0" class="space-y-2">
              <label
                v-for="ac in availableCourses"
                :key="ac.id"
                class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:bg-gray-50 cursor-pointer transition-colors"
                :class="{ 'bg-indigo-50 border-indigo-300': form.target_course_ids.includes(ac.id) }"
              >
                <input
                  type="checkbox"
                  :value="ac.id"
                  v-model="form.target_course_ids"
                  class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                />
                <span class="text-sm text-gray-700">{{ ac.name }}</span>
              </label>
            </div>
            <p v-else class="text-sm text-gray-400">目前沒有可選的目標課程</p>
            <p v-if="form.errors.target_course_ids" :class="errorTextClasses">{{ form.errors.target_course_ids }}</p>
          </div>

          <!-- Schedule Preview -->
          <div v-if="schedulePreview.length > 0">
            <label :class="labelClasses">發信排程預覽</label>
            <div class="mt-3 overflow-hidden rounded-lg border border-gray-200">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Lesson</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">解鎖日</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                  <tr v-for="(item, index) in schedulePreview" :key="index">
                    <td class="px-4 py-2 text-sm text-gray-700">{{ item.title }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">
                      {{ item.day === 0 ? '訂閱當天' : `第 ${item.day} 天` }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Submit -->
    <div class="flex items-center justify-end gap-4 pt-4">
      <a
        href="/admin/courses"
        class="px-6 py-3 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 transition-colors"
      >
        取消
      </a>
      <button
        type="submit"
        :disabled="form.processing"
        class="px-8 py-3 text-base font-medium text-white bg-indigo-600 border border-transparent rounded-lg shadow-sm hover:bg-indigo-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
      >
        {{ form.processing ? '儲存中...' : '儲存課程' }}
      </button>
    </div>
  </form>
</template>
