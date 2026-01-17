<script setup>
import { useForm } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'

const props = defineProps({
  course: {
    type: Object,
    default: null,
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
  thumbnail: null,
  instructor_name: props.course?.instructor_name || '',
  type: props.course?.type || 'lecture',
  duration_minutes: props.course?.duration_minutes || '',
  sale_at: props.course?.sale_at || '',
  portaly_product_id: props.course?.portaly_product_id || '',
})

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
    form.post(props.submitUrl, {
      _method: 'put',
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

    <!-- Pricing & Duration -->
    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl">
      <div class="px-6 py-6 sm:p-8">
        <div class="border-b border-gray-200 pb-6 mb-8">
          <h3 class="text-xl font-semibold text-gray-900">價格與時長</h3>
          <p class="mt-1 text-sm text-gray-500">設定課程價格和預計學習時間。</p>
        </div>

        <div class="space-y-8">
          <!-- Two columns for Price & Duration -->
          <div class="grid grid-cols-1 gap-8 sm:grid-cols-2">
            <!-- Price -->
            <div>
              <label for="price" :class="labelClasses">
                價格 (TWD) <span class="text-red-500">*</span>
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
              <p v-if="form.errors.price" :class="errorTextClasses">{{ form.errors.price }}</p>
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
            <span v-if="course" class="inline-flex items-center gap-1 ml-2">
              <a :href="`/admin/courses/${course.id}/images`" class="text-indigo-600 hover:text-indigo-500 font-medium">
                前往相簿管理 →
              </a>
            </span>
          </p>
        </div>

        <div>
          <textarea
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

    <!-- Portaly Integration -->
    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl">
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
