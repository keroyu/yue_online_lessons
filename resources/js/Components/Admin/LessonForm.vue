<script setup>
import { ref, computed, onMounted } from 'vue'

const props = defineProps({
  lesson: {
    type: Object,
    default: null,
  },
  courseType: {
    type: String,
    default: 'standard',
  },
})

const emit = defineEmits(['save', 'close'])

const form = ref({
  title: '',
  video_url: '',
  html_content: '',
  duration_seconds: '',
  promo_delay_seconds: '',
  promo_html: '',
  promo_url: '',
  reward_html: '',
  video_access_hours: '',
})

const errors = ref({})

const ctaUrl = ref('')
const ctaText = ref('')

const insertCtaButton = () => {
  if (!ctaUrl.value || !ctaText.value) return
  const text = ctaText.value || '立即瞭解'
  const html = `<div style="text-align:center;margin:24px 0"><a href="${ctaUrl.value}" style="display:inline-block;background:#F0C14B;color:#373557;padding:12px 40px;border-radius:9999px;border:1px solid rgba(199,163,59,0.5);text-decoration:none;font-weight:600;font-size:15px;box-shadow:0 1px 3px rgba(0,0,0,0.1)">${text}</a></div>`
  form.value.promo_html = (form.value.promo_html || '') + '\n' + html
  ctaUrl.value = ''
  ctaText.value = ''
}

onMounted(() => {
  if (props.lesson) {
    form.value = {
      title: props.lesson.title || '',
      video_url: props.lesson.video_url || '',
      html_content: props.lesson.html_content || '',
      duration_seconds: props.lesson.duration_seconds || '',
      promo_delay_seconds: props.lesson.promo_delay_seconds ?? '',
      promo_html: props.lesson.promo_html || '',
      promo_url: props.lesson.promo_url || '',
      reward_html: props.lesson.reward_html || '',
      video_access_hours: props.lesson.video_access_hours ?? '',
    }
  }
})

const isEditing = computed(() => !!props.lesson)

const videoPlatform = computed(() => {
  const url = form.value.video_url
  if (!url) return null

  if (/vimeo\.com/.test(url)) return 'Vimeo'
  if (/youtube\.com|youtu\.be/.test(url)) return 'YouTube'
  return null
})

const validate = () => {
  errors.value = {}

  if (!form.value.title.trim()) {
    errors.value.title = '請輸入小節標題'
    return false
  }

  if (form.value.video_url && !videoPlatform.value) {
    errors.value.video_url = '請輸入有效的 Vimeo 或 YouTube 連結'
    return false
  }

  return true
}

const submit = () => {
  if (!validate()) return

  emit('save', {
    title: form.value.title,
    video_url: form.value.video_url || null,
    html_content: form.value.html_content || null,
    duration_seconds: form.value.duration_seconds ? parseInt(form.value.duration_seconds) : null,
    promo_delay_seconds: form.value.promo_delay_seconds !== '' ? parseInt(form.value.promo_delay_seconds) : null,
    promo_html: form.value.promo_html || null,
    promo_url: form.value.promo_url || null,
    reward_html: form.value.reward_html || null,
    video_access_hours: form.value.video_access_hours !== '' ? parseInt(form.value.video_access_hours) : null,
  })
}

const close = () => {
  emit('close')
}

// Consistent styling classes (matching CourseForm)
const inputClasses = 'mt-2 block w-full rounded-lg border-gray-300 px-4 py-3 text-base shadow-sm transition-colors focus:border-indigo-500 focus:ring-indigo-500'
const inputErrorClasses = 'border-red-300 focus:border-red-500 focus:ring-red-500'
const labelClasses = 'block text-sm font-semibold text-gray-900'
const helpTextClasses = 'mt-2 text-sm text-gray-500'
const errorTextClasses = 'mt-2 text-sm text-red-600'
</script>

<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-50 overflow-y-auto">
      <!-- Backdrop -->
      <div class="fixed inset-0 bg-black/50 transition-opacity" @click="close" />

      <!-- Modal container -->
      <div class="flex min-h-full items-center justify-center p-4">
        <!-- Modal -->
        <div
          class="relative bg-white rounded-xl shadow-2xl w-full max-w-2xl transform transition-all"
          @click.stop
        >
          <!-- Header -->
          <div class="px-6 py-5 border-b border-gray-200">
            <h3 class="text-xl font-semibold text-gray-900">
              {{ isEditing ? '編輯小節' : '新增小節' }}
            </h3>
            <p class="mt-1 text-sm text-gray-500">
              {{ isEditing ? '修改小節的內容與設定' : '填寫小節資訊，可設定影片或 HTML 內容' }}
            </p>
          </div>

          <!-- Body -->
          <form @submit.prevent="submit" class="px-6 py-6">
            <div class="space-y-6">
              <!-- Title -->
              <div>
                <label for="title" :class="labelClasses">
                  小節標題 <span class="text-red-500">*</span>
                </label>
                <input
                  id="title"
                  v-model="form.title"
                  type="text"
                  placeholder="輸入小節標題"
                  :class="[inputClasses, errors.title ? inputErrorClasses : '']"
                />
                <p v-if="errors.title" :class="errorTextClasses">{{ errors.title }}</p>
              </div>

              <!-- Video URL -->
              <div>
                <label for="video_url" :class="labelClasses">
                  影片連結
                </label>
                <input
                  id="video_url"
                  v-model="form.video_url"
                  type="url"
                  placeholder="https://vimeo.com/... 或 https://youtube.com/..."
                  :class="[inputClasses, errors.video_url ? inputErrorClasses : '']"
                />
                <p v-if="videoPlatform" class="mt-2 text-sm text-green-600 flex items-center gap-1.5">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                  已偵測到 {{ videoPlatform }} 影片
                </p>
                <p v-else :class="helpTextClasses">支援 Vimeo 或 YouTube 連結</p>
                <p v-if="errors.video_url" :class="errorTextClasses">{{ errors.video_url }}</p>
              </div>

              <!-- Duration -->
              <div>
                <label for="duration_seconds" :class="labelClasses">
                  影片時長（秒）
                </label>
                <input
                  id="duration_seconds"
                  v-model="form.duration_seconds"
                  type="number"
                  min="0"
                  placeholder="例如：230"
                  :class="inputClasses"
                />
                <p :class="helpTextClasses">230 秒會顯示為「3:50」</p>
              </div>

              <!-- HTML Content -->
              <div>
                <label for="html_content" :class="labelClasses">
                  HTML 內容
                </label>
                <textarea
                  id="html_content"
                  v-model="form.html_content"
                  rows="8"
                  placeholder="## 標題&#10;內容..."
                  class="mt-2 block w-full rounded-lg border-gray-300 px-4 py-3 text-sm shadow-sm transition-colors focus:border-indigo-500 focus:ring-indigo-500 font-mono leading-relaxed"
                />
                <p :class="helpTextClasses">
                  如無影片連結，將顯示此 HTML 內容（電子書或文章形式）
                </p>
              </div>

              <!-- Reward Block Settings (drip courses only) -->
              <div v-if="courseType === 'drip'" class="border-t pt-6 mt-2">
                <h4 class="text-sm font-semibold text-gray-900 mb-4">準時到課獎勵設定</h4>
                <p class="text-xs text-gray-500 mb-4">
                  設定後，在免費觀看期倒數旁顯示獎勵欄。停留達全站設定時間後顯示以下 HTML 內容。
                </p>
                <div class="space-y-4">
                  <div>
                    <label for="video_access_hours" :class="labelClasses">影片觀看期限（小時）</label>
                    <input
                      id="video_access_hours"
                      v-model="form.video_access_hours"
                      type="number"
                      min="1"
                      :class="inputClasses"
                      placeholder="留空表示無限期觀看"
                    />
                    <p :class="helpTextClasses">drip 課程有影片的 Lesson 專用。設定後啟用倒數計時與準時到課獎勵欄。</p>
                  </div>
                  <div>
                    <label for="reward_html" :class="labelClasses">獎勵內容（HTML）</label>
                    <textarea
                      id="reward_html"
                      v-model="form.reward_html"
                      rows="4"
                      placeholder="<div>送你優惠代碼 XXXXX</div>"
                      class="mt-2 block w-full rounded-lg border-gray-300 px-4 py-3 text-sm shadow-sm transition-colors focus:border-indigo-500 focus:ring-indigo-500 font-mono leading-relaxed"
                    />
                    <p :class="helpTextClasses">留空則不顯示獎勵欄</p>
                  </div>
                </div>
              </div>

              <!-- Promo Block Settings -->
              <div class="border-t pt-6 mt-2">
                <h4 class="text-sm font-semibold text-gray-900 mb-4">促銷區塊設定</h4>

                <div class="space-y-4">
                  <div>
                    <label for="promo_delay_seconds" :class="labelClasses">延遲顯示（秒）</label>
                    <input
                      id="promo_delay_seconds"
                      v-model="form.promo_delay_seconds"
                      type="number"
                      min="0"
                      max="7200"
                      placeholder="留空則不顯示促銷區塊"
                      :class="inputClasses"
                    />
                    <p :class="helpTextClasses">0 = 立即顯示，留空 = 不啟用</p>
                  </div>

                  <div>
                    <label for="promo_html" :class="labelClasses">促銷內容（HTML）</label>
                    <!-- Quick insert CTA button -->
                    <div class="mt-2 flex items-end gap-2">
                      <div class="flex-1">
                        <label class="block text-xs text-gray-500">連結</label>
                        <input
                          v-model="ctaUrl"
                          type="url"
                          placeholder="https://..."
                          class="block w-full rounded border-gray-300 px-3 py-1.5 text-sm"
                        />
                      </div>
                      <div class="flex-1">
                        <label class="block text-xs text-gray-500">按鈕文字</label>
                        <input
                          v-model="ctaText"
                          type="text"
                          placeholder="心動不如馬上行動"
                          class="block w-full rounded border-gray-300 px-3 py-1.5 text-sm"
                        />
                      </div>
                      <button
                        type="button"
                        class="shrink-0 rounded bg-orange-500 px-3 py-1.5 text-sm font-medium text-white hover:bg-orange-600 transition-colors"
                        @click="insertCtaButton"
                      >
                        插入按鈕
                      </button>
                    </div>
                    <textarea
                      id="promo_html"
                      v-model="form.promo_html"
                      rows="5"
                      placeholder="<div class='bg-yellow-100 p-4'>...</div>"
                      class="mt-2 block w-full rounded-lg border-gray-300 px-4 py-3 text-sm shadow-sm transition-colors focus:border-indigo-500 focus:ring-indigo-500 font-mono leading-relaxed"
                    />
                  </div>

                  <div>
                    <label for="promo_url" :class="labelClasses">促銷連結 URL（教室追蹤）</label>
                    <input
                      id="promo_url"
                      v-model="form.promo_url"
                      type="url"
                      :class="inputClasses"
                      placeholder="https://example.com/product/..."
                    />
                    <p :class="helpTextClasses">設定後，教室頁面的促銷區塊旁顯示可追蹤點擊的按鈕，追蹤訂閱者在教室的促銷互動。留空則不顯示。</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="mt-8 flex items-center justify-end gap-3 pt-6 border-t border-gray-200">
              <button
                type="button"
                class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 transition-colors"
                @click="close"
              >
                取消
              </button>
              <button
                type="submit"
                class="px-6 py-2.5 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-lg shadow-sm hover:bg-indigo-700 transition-colors"
              >
                {{ isEditing ? '更新' : '新增' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Teleport>
</template>
