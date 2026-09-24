<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import draggable from 'vuedraggable'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import RoadmapStageCard from '@/Components/Admin/RoadmapStageCard.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  course: {
    type: Object,
    required: true,
  },
  stages: {
    type: Array,
    required: true,
  },
})

const newKey = () =>
  (window.crypto?.randomUUID?.() ?? `k${Date.now()}${Math.random()}`)

// vuedraggable needs a stable key per row, and new rows have no id yet.
const withKeys = (stages) =>
  stages.map((stage) => ({
    _key: newKey(),
    id: stage.id ?? null,
    title: stage.title ?? '',
    description_md: stage.description_md ?? '',
    checkpoints: (stage.checkpoints ?? []).map((checkpoint) => ({
      _key: newKey(),
      id: checkpoint.id ?? null,
      label: checkpoint.label ?? '',
      completed_count: checkpoint.completed_count ?? 0,
    })),
  }))

const form = useForm({
  roadmap_title: props.course.roadmap_title ?? '',
  stages: withKeys(props.stages),
})

const totalCheckpoints = computed(() =>
  form.stages.reduce((sum, stage) => sum + stage.checkpoints.length, 0)
)

const totalCompletions = computed(() =>
  form.stages.reduce(
    (sum, stage) => sum + stage.checkpoints.reduce((n, c) => n + (c.completed_count || 0), 0),
    0
  )
)

const addStage = () => {
  form.stages.push({
    _key: newKey(),
    id: null,
    title: '',
    description_md: '',
    checkpoints: [{ _key: newKey(), id: null, label: '', completed_count: 0 }],
  })
}

const removeStage = (index) => {
  form.stages.splice(index, 1)
}

const removeCheckpoint = (stageIndex, checkpointIndex) => {
  form.stages[stageIndex].checkpoints.splice(checkpointIndex, 1)
}

/* ---------- Markdown import (004 D21 / FR-023) ---------- */

const showImport = ref(false)
const importText = ref('')

// `## heading` opens a stage, `- [ ]` / `- [x]` lines become its checkpoints,
// anything else that is not a separator becomes the stage description. The
// result is plain draft state: every row is id-less, so saving it replaces the
// current roadmap — which is why the confirm below spells out the cost.
const parseMarkdown = (text) => {
  const stages = []
  let current = null
  const descriptionLines = []

  const flushDescription = () => {
    if (current) {
      current.description_md = descriptionLines.join('\n').trim()
    }
    descriptionLines.length = 0
  }

  text.split(/\r?\n/).forEach((rawLine) => {
    const line = rawLine.trim()

    if (/^##\s+/.test(line)) {
      flushDescription()
      current = {
        _key: newKey(),
        id: null,
        title: line.replace(/^##\s+/, '').trim(),
        description_md: '',
        checkpoints: [],
      }
      stages.push(current)
      return
    }

    if (!current) return

    const checkpoint = line.match(/^[-*]\s*\[[ xX]\]\s*(.+)$/)
    if (checkpoint) {
      current.checkpoints.push({
        _key: newKey(),
        id: null,
        label: checkpoint[1].trim().slice(0, 500),
        completed_count: 0,
      })
      return
    }

    // Skip blank lines, the arrow separators and the "**Checkpoint：**" label.
    if (!line || line === '↓' || /^\*\*.*\*\*$/.test(line)) return

    descriptionLines.push(line)
  })

  flushDescription()

  return stages.filter((stage) => stage.title)
}

const applyImport = () => {
  const parsed = parseMarkdown(importText.value)

  if (!parsed.length) {
    window.alert('沒有解析到任何階段。請確認每個階段以「## 標題」開頭。')
    return
  }

  const warning = totalCompletions.value > 0
    ? `匯入會取代目前的 ${form.stages.length} 個階段，並刪除 ${totalCompletions.value} 筆學員完成紀錄（儲存後生效）。\n\n解析到 ${parsed.length} 個階段，確定要取代嗎？`
    : `匯入會取代目前的 ${form.stages.length} 個階段。\n\n解析到 ${parsed.length} 個階段，確定要取代嗎？`

  if (!window.confirm(warning)) return

  form.stages = parsed
  importText.value = ''
  showImport.value = false
}

/* ---------- Save ---------- */

const fieldLabels = {
  roadmap_title: 'Roadmap 標題',
}

const errorList = computed(() =>
  Object.entries(form.errors).map(([key, message]) => ({
    key,
    label: fieldLabels[key] || key,
    message,
  }))
)

const submit = () => {
  form
    .transform((data) => ({
      roadmap_title: data.roadmap_title || null,
      stages: data.stages.map((stage) => ({
        id: stage.id,
        title: stage.title,
        description_md: stage.description_md || null,
        checkpoints: stage.checkpoints.map((checkpoint) => ({
          id: checkpoint.id,
          label: checkpoint.label,
        })),
      })),
    }))
    .put(`/admin/courses/${props.course.id}/roadmap`, {
      preserveScroll: true,
    })
}
</script>

<template>
  <div class="px-4 sm:px-6 lg:px-8 pb-6">
    <div class="mb-8">
      <nav class="flex" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-4">
          <li>
            <Link href="/admin/courses" class="text-sm font-medium text-gray-500 hover:text-gray-700">
              課程管理
            </Link>
          </li>
          <li>
            <div class="flex items-center">
              <svg class="flex-shrink-0 h-5 w-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
              </svg>
              <Link :href="`/admin/courses/${course.id}/edit`" class="ml-4 text-sm font-medium text-gray-500 hover:text-gray-700">
                {{ course.name }}
              </Link>
            </div>
          </li>
        </ol>
      </nav>
      <h1 class="mt-2 text-2xl font-semibold text-gray-900">Roadmap</h1>
      <p class="mt-1 text-sm text-gray-500">
        設定學員在教室看到的階段里程碑與自我檢核清單。沒有任何階段時，教室不會出現 Roadmap 入口。
      </p>
    </div>

    <!-- Roadmap title -->
    <div class="bg-white shadow rounded-lg p-4 mb-6">
      <label class="block text-sm font-medium text-gray-700 mb-1">Roadmap 標題</label>
      <input
        v-model="form.roadmap_title"
        type="text"
        maxlength="100"
        placeholder="留空則顯示「Roadmap」"
        class="w-full sm:w-96 rounded-md border-gray-300 text-sm focus:border-brand-teal focus:ring-brand-teal"
      />
      <p class="mt-2 text-xs text-gray-500">
        目前共 {{ form.stages.length }} 個階段、{{ totalCheckpoints }} 個檢核項目。
      </p>
    </div>

    <!-- Markdown import -->
    <div class="bg-white shadow rounded-lg p-4 mb-6">
      <button
        type="button"
        class="text-sm font-medium text-brand-teal hover:text-brand-navy cursor-pointer"
        @click="showImport = !showImport"
      >
        {{ showImport ? '收起' : '從 Markdown 匯入' }}
      </button>
      <p class="mt-1 text-xs text-gray-500">
        貼上草稿：<code>## 標題</code> 會變成階段，其下的 <code>- [ ]</code> 變成檢核項目，其餘文字成為階段說明。
      </p>

      <div v-if="showImport" class="mt-3">
        <textarea
          v-model="importText"
          rows="10"
          placeholder="## 01｜找到可變現的知識方向&#10;&#10;- [ ] 列出至少 10 項能力&#10;- [ ] 選出 3 個可能變現的方向"
          class="w-full rounded-md border-gray-300 font-mono text-xs focus:border-brand-teal focus:ring-brand-teal"
        />
        <div class="mt-2 flex items-center gap-3">
          <button
            type="button"
            class="inline-flex items-center px-3 py-2 rounded-md text-sm font-medium text-white bg-brand-teal hover:bg-brand-navy cursor-pointer"
            @click="applyImport"
          >
            解析並取代
          </button>
          <span class="text-xs text-amber-700">
            匯入會取代目前所有階段，按下方「儲存」才會真的生效。
          </span>
        </div>
      </div>
    </div>

    <!-- Stages -->
    <draggable
      v-model="form.stages"
      item-key="_key"
      handle=".stage-handle"
      ghost-class="opacity-50"
      class="space-y-4"
    >
      <template #item="{ element: stage, index }">
        <RoadmapStageCard
          :stage="stage"
          :index="index"
          :errors="form.errors"
          @remove="removeStage(index)"
          @remove-checkpoint="(checkpointIndex) => removeCheckpoint(index, checkpointIndex)"
        />
      </template>
    </draggable>

    <div v-if="!form.stages.length" class="bg-white shadow rounded-lg p-8 text-center">
      <p class="text-gray-500">這門課還沒有 Roadmap。</p>
      <p class="mt-1 text-sm text-gray-400">新增第一個階段，或從 Markdown 匯入現成的草稿。</p>
    </div>

    <button
      type="button"
      class="mt-4 w-full rounded-lg border-2 border-dashed border-gray-300 py-3 text-sm font-medium text-gray-500 hover:border-brand-teal hover:text-brand-teal cursor-pointer"
      @click="addStage"
    >
      + 新增階段
    </button>

    <!-- Sticky save bar -->
    <!-- sticky (not fixed): a fixed full-width bar spans the viewport and paints
         over the bottom of AdminLayout's navy sidebar. Same shape as CourseForm. -->
    <div class="sticky bottom-0 z-10 mt-4 -mx-4 sm:mx-0 border-t border-gray-200 bg-white/95 backdrop-blur px-4 py-3 sm:px-6 lg:px-8">
      <div v-if="errorList.length" class="mb-2 rounded-md bg-red-50 px-3 py-2">
        <p class="text-sm font-medium text-red-800">有 {{ errorList.length }} 個欄位需要修正</p>
        <ul class="mt-1 space-y-0.5">
          <li v-for="error in errorList" :key="error.key" class="text-xs text-red-700">
            {{ error.label }}：{{ error.message }}
          </li>
        </ul>
      </div>

      <div class="flex items-center justify-end gap-3">
        <Link
          :href="`/admin/courses/${course.id}/edit`"
          class="px-4 py-2 rounded-md border border-gray-300 text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
        >
          取消
        </Link>
        <button
          type="button"
          :disabled="form.processing"
          class="px-5 py-2 rounded-md text-sm font-medium text-white bg-brand-teal hover:bg-brand-navy disabled:opacity-50"
          @click="submit"
        >
          {{ form.processing ? '儲存中…' : '儲存 Roadmap' }}
        </button>
      </div>
    </div>
  </div>
</template>
