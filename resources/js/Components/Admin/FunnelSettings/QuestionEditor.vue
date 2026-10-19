<script setup>
import { computed } from 'vue'

// One screening question on 1v1 漏斗設定 (011 US46). Mutates the question it
// is given — it lives inside the page's form, which owns submit and errors.
const props = defineProps({
  question: { type: Object, required: true },
  index: { type: Number, required: true },
  total: { type: Number, required: true },
  errors: { type: Object, default: () => ({}) },
})

const emit = defineEmits(['move', 'remove', 'restore'])

const VETO_OPTIONS = [
  { value: 'none', label: '不否決' },
  { value: 'strict', label: '僅嚴格審核否決' },
  { value: 'always', label: '一律否決' },
]

const randomKey = () => Math.random().toString(36).slice(2, 8).padEnd(6, '0')

const activeOptions = computed(() => props.question.options.filter(o => !o.retired))
const retiredOptions = computed(() => props.question.options.filter(o => o.retired))

const err = (path) => props.errors[`questions.${props.index}.${path}`]

function addOption() {
  props.question.options.push({ key: randomKey(), label: '', score: 0, veto: 'none', cap: null, retired: false, answered: false })
}

function moveOption(option, delta) {
  const list = props.question.options
  const from = list.indexOf(option)
  // Step over retired neighbours: they are not shown in the active list.
  let to = from + delta
  while (to >= 0 && to < list.length && list[to].retired) to += delta
  if (to < 0 || to >= list.length) return
  list.splice(from, 1)
  list.splice(to, 0, option)
}

// Answered options are retired, never deleted, so old leads keep their wording (FR-252).
function removeOption(option) {
  if (option.answered) {
    option.retired = true
    return
  }
  props.question.options.splice(props.question.options.indexOf(option), 1)
}

const optionIndex = (option) => props.question.options.indexOf(option)

const inputClass = 'w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand-teal focus:ring-2 focus:ring-brand-teal/30'
const smallButton = 'rounded px-2 py-1 text-xs text-gray-500 hover:bg-gray-100 hover:text-gray-800 disabled:opacity-30 disabled:hover:bg-transparent'
</script>

<template>
  <div v-if="question.retired" class="flex items-center justify-between gap-3 rounded-lg border border-dashed border-gray-300 bg-gray-50 px-4 py-3">
    <div class="min-w-0">
      <span class="mr-2 rounded bg-gray-200 px-1.5 py-0.5 text-xs text-gray-600">已停用</span>
      <span class="text-sm text-gray-500">{{ question.title }}</span>
    </div>
    <button type="button" class="flex-shrink-0 rounded px-2 py-1 text-xs text-brand-teal hover:bg-brand-teal/10" @click="emit('restore')">恢復</button>
  </div>

  <div v-else class="space-y-3 rounded-lg border border-gray-200 bg-white p-4">
    <div class="flex items-start gap-2">
      <span class="mt-2 text-sm font-semibold text-gray-400">Q{{ index + 1 }}</span>
      <div class="flex-1 space-y-2">
        <input v-model="question.title" type="text" placeholder="題目" :class="inputClass" />
        <p v-if="err('title')" class="text-xs text-red-600">{{ err('title') }}</p>
        <input v-model="question.hint" type="text" placeholder="提示文字（選填，顯示在題目下方）" :class="inputClass" />
      </div>
      <div class="flex flex-shrink-0 flex-col gap-1">
        <button type="button" :class="smallButton" :disabled="index === 0" title="上移" @click="emit('move', -1)">▲</button>
        <button type="button" :class="smallButton" :disabled="index === total - 1" title="下移" @click="emit('move', 1)">▼</button>
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full min-w-[560px] text-sm">
        <thead>
          <tr class="text-left text-xs text-gray-500">
            <th class="pb-1 font-normal">選項文字</th>
            <th class="w-16 pb-1 font-normal">分數</th>
            <th class="w-36 pb-1 font-normal">否決</th>
            <th class="w-20 pb-1 font-normal" title="選了這個選項時，總分最多只算到這個數字">分數上限</th>
            <th class="w-24 pb-1" />
          </tr>
        </thead>
        <tbody>
          <tr v-for="option in activeOptions" :key="option.key" class="align-top">
            <td class="py-1 pr-2">
              <input v-model="option.label" type="text" :class="inputClass" />
              <p v-if="err(`options.${optionIndex(option)}.label`)" class="mt-1 text-xs text-red-600">{{ err(`options.${optionIndex(option)}.label`) }}</p>
            </td>
            <td class="py-1 pr-2"><input v-model.number="option.score" type="number" min="0" max="10" :class="inputClass" /></td>
            <td class="py-1 pr-2">
              <select v-model="option.veto" :class="inputClass">
                <option v-for="v in VETO_OPTIONS" :key="v.value" :value="v.value">{{ v.label }}</option>
              </select>
            </td>
            <td class="py-1 pr-2">
              <input
                type="number"
                min="0"
                placeholder="—"
                :value="option.cap ?? ''"
                :class="inputClass"
                @input="option.cap = $event.target.value === '' ? null : Number($event.target.value)"
              />
            </td>
            <td class="whitespace-nowrap py-1">
              <button type="button" :class="smallButton" title="上移" @click="moveOption(option, -1)">▲</button>
              <button type="button" :class="smallButton" title="下移" @click="moveOption(option, 1)">▼</button>
              <button
                type="button"
                class="rounded px-2 py-1 text-xs text-red-500 hover:bg-red-50 hover:text-red-700"
                :title="option.answered ? '已有人選過，刪除會改為停用' : '刪除'"
                @click="removeOption(option)"
              >{{ option.answered ? '停用' : '刪除' }}</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <p v-if="err('options')" class="text-xs text-red-600">{{ err('options') }}</p>

    <div v-if="retiredOptions.length" class="flex flex-wrap gap-2 text-xs text-gray-500">
      <span>已停用：</span>
      <button
        v-for="option in retiredOptions"
        :key="option.key"
        type="button"
        class="rounded bg-gray-100 px-2 py-0.5 hover:bg-brand-teal/10 hover:text-brand-teal"
        title="恢復此選項"
        @click="option.retired = false"
      >{{ option.label }} ↺</button>
    </div>

    <div class="flex items-center justify-between">
      <button type="button" class="rounded px-2 py-1 text-sm text-brand-teal hover:bg-brand-teal/10" @click="addOption">＋ 新增選項</button>
      <button
        type="button"
        class="rounded px-2 py-1 text-sm text-red-500 hover:bg-red-50 hover:text-red-700"
        :title="question.answered ? '已有人作答，刪除會改為停用' : '刪除此題'"
        @click="emit('remove')"
      >{{ question.answered ? '停用此題' : '刪除此題' }}</button>
    </div>
  </div>
</template>
