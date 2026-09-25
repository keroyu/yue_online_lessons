<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminModal from '@/Components/Admin/AdminModal.vue'

// 追蹤站長（SNS）settings (002 US24).
//
// The intro paragraph lives here rather than on the hero card: it renders
// inside this very block on the sidebar, and only rode along on the hero's
// multipart form for historical reasons (FR-087). It saves through its own
// endpoint, so editing it cannot disturb the hero image.
const props = defineProps({
  open: { type: Boolean, default: false },
  socialLinks: { type: Array, default: () => [] },
  snsProfileIntro: { type: String, default: '' },
})

const emit = defineEmits(['close'])

// ─── 站長介紹 ────────────────────────────────────────────────────────────────

const intro = ref('')
const introErrors = ref({})
const introSaving = ref(false)

watch(() => props.snsProfileIntro, value => {
  intro.value = value ?? ''
}, { immediate: true })

function saveIntro() {
  introSaving.value = true
  introErrors.value = {}

  router.post('/admin/homepage/sns-profile', { intro: intro.value }, {
    preserveScroll: true,
    onError: errors => { introErrors.value = errors },
    onFinish: () => { introSaving.value = false },
  })
}

// ─── SNS 連結 ────────────────────────────────────────────────────────────────

const localLinks = ref([])

watch(() => props.socialLinks, list => {
  localLinks.value = list.map(l => ({ ...l, editing: false, editUrl: l.url }))
}, { immediate: true, deep: true })

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
    onError: errors => { addErrors.value = errors },
    onSuccess: () => {
      showAddForm.value = false
      newLink.value = { platform: 'instagram', url: '' }
    },
  })
}
</script>

<template>
  <AdminModal :open="open" title="追蹤站長（SNS）" @close="emit('close')">
    <div class="space-y-6">
      <!-- 站長介紹 -->
      <div class="space-y-2">
        <label class="block text-sm font-medium text-gray-700">站長介紹</label>
        <textarea
          v-model="intro"
          rows="4"
          maxlength="500"
          placeholder="向訪客介紹自己（最多 500 字）"
          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm leading-relaxed resize-y focus:outline-none focus:ring-2 focus:ring-brand-navy"
        />
        <div class="flex items-center justify-between">
          <span class="text-xs" :class="intro.length > 500 ? 'text-red-500' : 'text-gray-400'">
            {{ intro.length }} / 500 字
          </span>
          <button
            type="button"
            :disabled="introSaving"
            class="cursor-pointer px-4 py-1.5 bg-brand-navy text-white text-sm font-semibold rounded-lg hover:bg-opacity-90 disabled:opacity-50"
            @click="saveIntro"
          >
            {{ introSaving ? '儲存中…' : '儲存介紹' }}
          </button>
        </div>
        <p v-if="introErrors.intro" class="text-sm text-red-600">{{ introErrors.intro }}</p>
        <p class="text-xs text-gray-400">顯示在側欄「追蹤站長」區塊的 SNS 按鈕上方；留空則不顯示這段文字。</p>
      </div>

      <hr class="border-gray-100" />

      <!-- SNS 連結 -->
      <div class="space-y-4">
        <h3 class="text-sm font-semibold text-gray-700">SNS 連結</h3>

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
            <button class="cursor-pointer text-sm text-brand-navy font-medium hover:underline" @click="saveLink(link)">儲存</button>
            <button class="cursor-pointer text-sm text-gray-500 hover:underline" @click="cancelEdit(link)">取消</button>
          </template>
          <template v-else>
            <span class="flex-1 truncate text-sm text-gray-700 mt-2">{{ link.url }}</span>
            <button class="cursor-pointer text-sm text-brand-navy hover:underline" @click="startEdit(link)">編輯</button>
            <button class="cursor-pointer text-sm text-red-500 hover:underline" @click="deleteLink(link)">刪除</button>
          </template>
        </div>

        <!-- Add form -->
        <div v-if="showAddForm" class="border border-gray-200 rounded-lg p-4 space-y-3">
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <select
              v-model="newLink.platform"
              class="cursor-pointer rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-navy"
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
            <button class="cursor-pointer px-4 py-1.5 bg-brand-navy text-white text-sm font-semibold rounded-lg hover:bg-opacity-90" @click="addLink">新增</button>
            <button class="cursor-pointer px-4 py-1.5 text-sm text-gray-500 hover:underline" @click="showAddForm = false; addErrors = {}">取消</button>
          </div>
        </div>

        <button
          v-if="!showAddForm"
          type="button"
          class="flex cursor-pointer items-center gap-1.5 text-sm text-brand-navy font-medium hover:underline"
          @click="showAddForm = true"
        >
          <span class="text-lg leading-none">+</span> 新增連結
        </button>
      </div>
    </div>
  </AdminModal>
</template>
