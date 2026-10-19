<script setup>
// A list of 「題目 選了 選項…」 conditions, all of which must hold (011 US46).
// `conditions` is an array of { question, options[] }, edited in place.
const props = defineProps({
  conditions: { type: Array, required: true },
  questions: { type: Array, required: true },
})

const optionsOf = (key) => props.questions.find(q => q.key === key)?.options.filter(o => !o.retired) ?? []

function add() {
  props.conditions.push({ question: props.questions[0]?.key ?? null, options: [] })
}

function toggle(condition, key) {
  const i = condition.options.indexOf(key)
  i === -1 ? condition.options.push(key) : condition.options.splice(i, 1)
}

const selectClass = 'w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand-teal focus:ring-2 focus:ring-brand-teal/30'
</script>

<template>
  <div class="space-y-2">
    <div v-for="(condition, i) in conditions" :key="i" class="rounded-lg bg-gray-50 p-3">
      <div class="flex items-center gap-2">
        <span v-if="i > 0" class="text-xs font-semibold text-gray-500">且</span>
        <select v-model="condition.question" :class="selectClass" @change="condition.options = []">
          <option v-for="q in questions" :key="q.key" :value="q.key">{{ q.title || '（未命名題目）' }}</option>
        </select>
        <button
          type="button"
          class="flex-shrink-0 rounded px-2 py-1 text-xs text-red-500 hover:bg-red-50 hover:text-red-700"
          @click="conditions.splice(i, 1)"
        >移除</button>
      </div>
      <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1">
        <span class="text-xs text-gray-500">選了：</span>
        <label v-for="o in optionsOf(condition.question)" :key="o.key" class="flex cursor-pointer items-center gap-1 text-sm text-gray-700 hover:text-gray-900">
          <input type="checkbox" :checked="condition.options.includes(o.key)" class="rounded text-brand-teal" @change="toggle(condition, o.key)" />
          {{ o.label || '（未命名選項）' }}
        </label>
      </div>
    </div>
    <button type="button" class="rounded px-2 py-1 text-sm text-brand-teal hover:bg-brand-teal/10" @click="add">＋ 新增條件</button>
  </div>
</template>
