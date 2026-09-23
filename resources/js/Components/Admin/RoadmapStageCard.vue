<script setup>
import draggable from 'vuedraggable'

const props = defineProps({
  stage: {
    type: Object,
    required: true,
  },
  index: {
    type: Number,
    required: true,
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
})

const emit = defineEmits(['remove', 'removeCheckpoint'])

// New rows carry no id on purpose: the backend treats an id-less item as an
// insert, and anything with an id as an update (004 FR-020).
const newKey = () =>
  (window.crypto?.randomUUID?.() ?? `k${Date.now()}${Math.random()}`)

const addCheckpoint = (afterIndex = null) => {
  const row = { _key: newKey(), id: null, label: '', completed_count: 0 }
  if (afterIndex === null) {
    props.stage.checkpoints.push(row)
  } else {
    props.stage.checkpoints.splice(afterIndex + 1, 0, row)
  }
}

// Enter at the end of a checkpoint opens the next one, like a list editor.
const onCheckpointEnter = (checkpointIndex) => {
  addCheckpoint(checkpointIndex)
}

const stageCompletions = () =>
  props.stage.checkpoints.reduce((sum, c) => sum + (c.completed_count || 0), 0)

const confirmRemoveStage = () => {
  const records = stageCompletions()
  const message = records > 0
    ? `刪除「${props.stage.title || '未命名階段'}」會一併刪除 ${records} 筆學員完成紀錄，確定要刪除嗎？`
    : `確定要刪除「${props.stage.title || '未命名階段'}」？`

  if (window.confirm(message)) emit('remove')
}

const confirmRemoveCheckpoint = (checkpointIndex) => {
  const checkpoint = props.stage.checkpoints[checkpointIndex]
  const records = checkpoint.completed_count || 0

  if (records > 0 && !window.confirm(`這個檢核項目已有 ${records} 位學員勾選，刪除後該紀錄會一併消失，確定嗎？`)) {
    return
  }

  emit('removeCheckpoint', checkpointIndex)
}

const fieldError = (key) => props.errors[key] || null
</script>

<template>
  <div class="bg-white shadow rounded-lg overflow-hidden" :data-field="`stages.${index}.title`">
    <!-- Stage header -->
    <div class="flex items-start gap-3 px-4 py-3 bg-brand-cream/60 border-b border-gray-200">
      <span class="stage-handle mt-1 cursor-move text-gray-400 hover:text-gray-600" title="拖曳排序">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
        </svg>
      </span>

      <span class="mt-0.5 flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-brand-teal text-sm font-semibold text-white">
        {{ index + 1 }}
      </span>

      <div class="flex-1 min-w-0">
        <input
          v-model="stage.title"
          type="text"
          maxlength="200"
          placeholder="階段標題，例如：01｜找到可變現的知識方向"
          class="w-full rounded-md border-gray-300 text-sm font-medium text-brand-navy focus:border-brand-teal focus:ring-brand-teal"
          :class="{ 'border-red-400': fieldError(`stages.${index}.title`) }"
        />
        <p v-if="fieldError(`stages.${index}.title`)" class="mt-1 text-xs text-red-600">
          {{ fieldError(`stages.${index}.title`) }}
        </p>
      </div>

      <button
        type="button"
        class="mt-1 text-sm text-red-500 hover:text-red-700"
        @click="confirmRemoveStage"
      >
        刪除
      </button>
    </div>

    <!-- Stage description -->
    <div class="px-4 py-3 border-b border-gray-100">
      <label class="block text-xs font-medium text-gray-500 mb-1">階段說明（Markdown，選填）</label>
      <textarea
        v-model="stage.description_md"
        rows="2"
        maxlength="5000"
        placeholder="這個階段要達成什麼？會顯示在學員的 Roadmap 卡片上。"
        class="w-full rounded-md border-gray-300 text-sm focus:border-brand-teal focus:ring-brand-teal"
      />
      <p v-if="fieldError(`stages.${index}.description_md`)" class="mt-1 text-xs text-red-600">
        {{ fieldError(`stages.${index}.description_md`) }}
      </p>
    </div>

    <!-- Checkpoints -->
    <div class="px-4 py-3">
      <div class="flex items-center justify-between mb-2">
        <label class="block text-xs font-medium text-gray-500">
          檢核項目（{{ stage.checkpoints.length }}）
        </label>
        <button
          type="button"
          class="text-xs font-medium text-brand-teal hover:text-brand-navy"
          @click="addCheckpoint()"
        >
          + 新增項目
        </button>
      </div>

      <draggable
        v-model="stage.checkpoints"
        item-key="_key"
        handle=".checkpoint-handle"
        ghost-class="opacity-50"
        class="space-y-2"
      >
        <template #item="{ element: checkpoint, index: checkpointIndex }">
          <div class="flex items-center gap-2">
            <span class="checkpoint-handle cursor-move text-gray-300 hover:text-gray-500" title="拖曳排序">
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
              </svg>
            </span>

            <input
              v-model="checkpoint.label"
              type="text"
              maxlength="500"
              placeholder="學員要自我檢核的一件事"
              class="flex-1 rounded-md border-gray-300 text-sm focus:border-brand-teal focus:ring-brand-teal"
              :class="{ 'border-red-400': fieldError(`stages.${index}.checkpoints.${checkpointIndex}.label`) }"
              @keydown.enter.prevent="onCheckpointEnter(checkpointIndex)"
            />

            <span
              v-if="checkpoint.completed_count > 0"
              class="text-xs text-gray-400 whitespace-nowrap"
              :title="`${checkpoint.completed_count} 位學員已勾選`"
            >
              {{ checkpoint.completed_count }} 人
            </span>

            <button
              type="button"
              class="text-gray-400 hover:text-red-600 cursor-pointer"
              title="刪除此項目"
              @click="confirmRemoveCheckpoint(checkpointIndex)"
            >
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </template>
      </draggable>

      <p v-if="!stage.checkpoints.length" class="text-sm text-gray-400 py-2">
        還沒有檢核項目。
      </p>
    </div>
  </div>
</template>
