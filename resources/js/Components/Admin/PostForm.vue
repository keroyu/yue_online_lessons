<script setup>
import { ref, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { marked } from 'marked'

const props = defineProps({
  post: { type: Object, default: null },
  courses: { type: Array, default: () => [] },
  images: { type: Array, default: () => [] },
  popularTags: { type: Array, default: () => [] },
})

const isEdit = computed(() => !!props.post?.id)

const form = useForm({
  title: props.post?.title ?? '',
  slug: props.post?.slug ?? '',
  body_md: props.post?.body_md ?? '',
  excerpt: props.post?.excerpt ?? '',
  seo_title: props.post?.seo_title ?? '',
  meta_description: props.post?.meta_description ?? '',
  status: props.post?.status ?? 'draft',
  published_at: props.post?.published_at ?? '',
  is_featured: props.post?.is_featured ?? false,
  related_course_id: props.post?.related_course_id ?? null,
  tagsText: (props.post?.tags ?? []).join(', '),
  related_post_ids: (props.post?.related ?? []).map((r) => r.id),
  cover_image: null,
  og_image: null,
})

// Related posts (curated) — display list keeps titles; form holds the ordered ids
const relatedList = ref([...(props.post?.related ?? [])])
const relSearch = ref('')
const relResults = ref([])
let relTimer = null

const searchRelated = () => {
  clearTimeout(relTimer)
  const q = relSearch.value.trim()
  if (!q) { relResults.value = []; return }
  relTimer = setTimeout(async () => {
    const exclude = props.post?.id ? `&exclude=${props.post.id}` : ''
    const res = await fetch(`/admin/posts/search?q=${encodeURIComponent(q)}${exclude}`, {
      headers: { Accept: 'application/json' }, credentials: 'same-origin',
    })
    const data = await res.json()
    const chosen = new Set(form.related_post_ids)
    relResults.value = (data.posts || []).filter((p) => !chosen.has(p.id))
  }, 300)
}

const addRelated = (p) => {
  if (form.related_post_ids.includes(p.id)) return
  relatedList.value.push(p)
  form.related_post_ids.push(p.id)
  relResults.value = relResults.value.filter((r) => r.id !== p.id)
  relSearch.value = ''
}

const removeRelated = (id) => {
  relatedList.value = relatedList.value.filter((r) => r.id !== id)
  form.related_post_ids = form.related_post_ids.filter((r) => r !== id)
}

const bodyRef = ref(null)
const preview = computed(() => marked(form.body_md || ''))

// Tags currently entered (parsed from the comma-separated field)
const selectedTags = computed(() =>
  form.tagsText.split(',').map((t) => t.trim()).filter(Boolean)
)

const toggleTag = (name) => {
  const current = selectedTags.value
  if (current.includes(name)) {
    form.tagsText = current.filter((t) => t !== name).join(', ')
  } else {
    form.tagsText = [...current, name].join(', ')
  }
}

const submit = () => {
  form
    .transform((data) => ({
      ...data,
      tags: data.tagsText.split(',').map((t) => t.trim()).filter(Boolean),
      published_at: data.status === 'draft' ? '' : data.published_at,
      ...(isEdit.value ? { _method: 'put' } : {}),
    }))
    .post(isEdit.value ? `/admin/posts/${props.post.id}` : '/admin/posts', {
      forceFormData: true,
      preserveScroll: true,
    })
}

// Image gallery (edit mode only — needs a persisted post id)
const uploading = ref(false)
const uploadImages = (event) => {
  const files = Array.from(event.target.files || [])
  if (!files.length) return
  const data = new FormData()
  files.forEach((f) => data.append('images[]', f))
  uploading.value = true
  router.post(`/admin/posts/${props.post.id}/images`, data, {
    preserveScroll: true,
    onFinish: () => {
      uploading.value = false
      event.target.value = ''
    },
  })
}

const insertImage = (url) => {
  const md = `\n![](${url})\n`
  const el = bodyRef.value
  if (el && typeof el.selectionStart === 'number') {
    const at = el.selectionStart
    form.body_md = form.body_md.slice(0, at) + md + form.body_md.slice(at)
  } else {
    form.body_md += md
  }
}
</script>

<template>
  <form class="space-y-6" @submit.prevent="submit">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Left: editor -->
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">標題 *</label>
          <input v-model="form.title" type="text" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2" />
          <p v-if="form.errors.title" class="text-sm text-red-600 mt-1">{{ form.errors.title }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">網址代稱 slug *（小寫英數與 -）</label>
          <input v-model="form.slug" type="text" placeholder="my-post-slug" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 font-mono text-sm" />
          <p class="text-xs text-gray-400 mt-1">前台網址：/blog/{{ form.slug || '…' }}</p>
          <p v-if="form.errors.slug" class="text-sm text-red-600 mt-1">{{ form.errors.slug }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">內文（Markdown，貼上 YouTube 連結會自動嵌入）*</label>
          <textarea ref="bodyRef" v-model="form.body_md" rows="16" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 font-mono text-sm"></textarea>
          <p v-if="form.errors.body_md" class="text-sm text-red-600 mt-1">{{ form.errors.body_md }}</p>
        </div>

        <!-- Related posts (curated, priority-ordered) -->
        <div>
          <label class="block text-sm font-medium text-gray-700">關聯文章</label>
          <p class="text-xs text-gray-400 mb-1">會優先顯示在前台文章底部；未加時自動用同標籤文章補上。加入順序＝顯示順序。</p>
          <input
            v-model="relSearch"
            type="text"
            placeholder="搜尋文章標題／slug 加入…"
            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
            @input="searchRelated"
          />
          <ul v-if="relResults.length" class="mt-1 border border-gray-200 rounded-md divide-y divide-gray-100 max-h-48 overflow-y-auto">
            <li v-for="p in relResults" :key="p.id">
              <button type="button" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50 cursor-pointer" @click="addRelated(p)">＋ {{ p.title }}</button>
            </li>
          </ul>
          <ul v-if="relatedList.length" class="mt-2 space-y-1">
            <li v-for="(p, i) in relatedList" :key="p.id" class="flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-md px-3 py-1.5 text-sm">
              <span class="text-xs text-gray-400 w-5 text-center">{{ i + 1 }}</span>
              <span class="flex-1 min-w-0 truncate text-gray-800">{{ p.title }}</span>
              <button type="button" class="text-gray-400 hover:text-red-600 cursor-pointer" title="移除" @click="removeRelated(p.id)">✕</button>
            </li>
          </ul>
        </div>

        <!-- 文章設定：標籤 / 狀態 / 精選 / 引流課程（置於關聯文章下方） -->
        <div>
          <label class="block text-sm font-medium text-gray-700">標籤（逗號分隔）</label>
          <input v-model="form.tagsText" type="text" placeholder="思維升級, 財務覺醒" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
          <div v-if="popularTags.length" class="mt-2">
            <p class="text-xs text-gray-400 mb-1.5">熱門標籤（點選即加入）：</p>
            <div class="flex flex-wrap gap-1.5">
              <button
                v-for="tag in popularTags"
                :key="tag"
                type="button"
                class="text-xs px-2 py-0.5 rounded-full border cursor-pointer transition-colors"
                :class="selectedTags.includes(tag)
                  ? 'bg-brand-teal text-white border-brand-teal'
                  : 'bg-white text-gray-600 border-gray-300 hover:border-brand-teal hover:text-brand-teal'"
                @click="toggleTag(tag)"
              >{{ selectedTags.includes(tag) ? '✓ ' : '＋ ' }}{{ tag }}</button>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-medium text-gray-700">狀態</label>
            <select v-model="form.status" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
              <option value="draft">草稿</option>
              <option value="scheduled">排程發佈</option>
              <option value="published">已發佈</option>
            </select>
          </div>
          <div v-if="form.status === 'scheduled'">
            <label class="block text-sm font-medium text-gray-700">發佈時間</label>
            <input v-model="form.published_at" type="datetime-local" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2" />
            <p v-if="form.errors.published_at" class="text-sm text-red-600 mt-1">{{ form.errors.published_at }}</p>
          </div>
        </div>

        <label class="flex items-center gap-2 text-sm text-gray-700">
          <input v-model="form.is_featured" type="checkbox" class="rounded border-gray-300" />
          設為精選（首頁優先顯示）
        </label>

        <div>
          <label class="block text-sm font-medium text-gray-700">引流課程（選填，文章底部顯示 CTA）</label>
          <select v-model="form.related_course_id" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
            <option :value="null">— 不綁定 —</option>
            <option v-for="c in courses" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </div>

        <!-- Image gallery (edit mode) -->
        <div v-if="isEdit" class="border border-gray-200 p-3">
          <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-gray-700">圖片庫</span>
            <label class="text-sm text-brand-teal cursor-pointer hover:underline">
              {{ uploading ? '上傳中…' : '+ 上傳圖片' }}
              <input type="file" accept="image/*" multiple class="hidden" @change="uploadImages" />
            </label>
          </div>
          <div v-if="images.length" class="grid grid-cols-4 gap-2 mt-3">
            <button
              v-for="img in images"
              :key="img.id"
              type="button"
              class="aspect-square border border-gray-200 overflow-hidden hover:ring-2 hover:ring-brand-teal"
              title="點擊插入到內文"
              @click="insertImage(img.url)"
            >
              <img :src="img.url" :alt="img.filename" class="w-full h-full object-cover" />
            </button>
          </div>
          <p v-else class="text-xs text-gray-400 mt-2">尚無圖片。上傳後點縮圖插入內文。</p>
        </div>
      </div>

      <!-- Right: live preview + meta -->
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700">預覽</label>
          <div class="mt-1 border border-gray-200 p-4 min-h-[8rem] bg-white md-preview" v-html="preview"></div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700">摘要（列表 / RSS / 信件前段）</label>
          <textarea v-model="form.excerpt" rows="2" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm"></textarea>
        </div>

        <details class="border border-gray-200 p-3">
          <summary class="text-sm font-medium text-gray-700 cursor-pointer">SEO 與圖片</summary>
          <div class="space-y-3 mt-3">
            <div>
              <label class="block text-sm text-gray-600">SEO 標題（留空用文章標題）</label>
              <input v-model="form.seo_title" type="text" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
            </div>
            <div>
              <label class="block text-sm text-gray-600">Meta description</label>
              <textarea v-model="form.meta_description" rows="2" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm"></textarea>
            </div>
            <div>
              <label class="block text-sm text-gray-600">封面圖</label>
              <input type="file" accept="image/*" class="mt-1 text-sm" @input="form.cover_image = $event.target.files[0]" />
            </div>
            <div>
              <label class="block text-sm text-gray-600">OG 分享圖（留空用封面）</label>
              <input type="file" accept="image/*" class="mt-1 text-sm" @input="form.og_image = $event.target.files[0]" />
            </div>
          </div>
        </details>
      </div>
    </div>

    <div class="flex items-center gap-3">
      <button type="submit" :disabled="form.processing" class="bg-brand-teal text-white rounded-md px-6 py-2 font-medium hover:bg-brand-teal/90 cursor-pointer disabled:opacity-50">
        {{ form.processing ? '儲存中…' : (isEdit ? '更新文章' : '建立文章') }}
      </button>
      <a href="/admin/posts" class="text-gray-500 hover:text-gray-700">取消</a>
    </div>
  </form>
</template>

<style scoped>
/* Tailwind preflight strips default heading/list styles, so style the v-html preview explicitly */
.md-preview :deep(h1) { font-size: 1.6rem; font-weight: 700; margin: 0.6rem 0 0.9rem; }
.md-preview :deep(h2) { font-size: 1.35rem; font-weight: 700; margin: 1.5rem 0 0.7rem; }
.md-preview :deep(h3) { font-size: 1.15rem; font-weight: 600; margin: 1.25rem 0 0.5rem; }
.md-preview :deep(p) { margin: 0.85rem 0; line-height: 1.75; }
.md-preview :deep(ul), .md-preview :deep(ol) { margin: 0.85rem 0; padding-left: 1.5rem; list-style: revert; }
.md-preview :deep(li) { margin: 0.25rem 0; }
.md-preview :deep(a) { color: #0d9488; text-decoration: underline; }
.md-preview :deep(strong) { font-weight: 700; }
.md-preview :deep(img) { max-width: 100%; height: auto; }
</style>
