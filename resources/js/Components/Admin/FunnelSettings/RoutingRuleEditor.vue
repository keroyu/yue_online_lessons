<script setup>
import { computed } from 'vue'

// One consultant-routing condition (011 US45 / US46): total compared against a
// score, and one question answered with one of the ticked options.
const props = defineProps({
  rule: { type: Object, required: true },
  pool: { type: String, required: true }, // chief | others
  questions: { type: Array, required: true },
  errors: { type: Object, default: () => ({}) },
})

const isChief = computed(() => props.pool === 'chief')
const scoreKey = computed(() => (isChief.value ? 'min_score' : 'max_score'))
const options = computed(() => props.questions.find(q => q.key === props.rule.question)?.options.filter(o => !o.retired) ?? [])

const err = (path) => props.errors[`routing.${props.pool}.${path}`]

function toggle(key) {
  const i = props.rule.options.indexOf(key)
  i === -1 ? props.rule.options.push(key) : props.rule.options.splice(i, 1)
}

const inputClass = 'rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand-teal focus:ring-2 focus:ring-brand-teal/30'
</script>

<template>
  <div class="space-y-3 rounded-lg border border-gray-200 bg-white p-4">
    <label class="flex cursor-pointer items-center gap-2 text-sm font-semibold text-gray-700 hover:text-gray-900">
      <input v-model="rule.enabled" type="checkbox" class="rounded text-brand-teal" />
      {{ isChief ? '只看得到首席顧問（管理員）的時段' : '只看得到其他顧問的時段' }}
    </label>

    <div v-if="rule.enabled" class="space-y-3 pl-6">
      <div class="flex flex-wrap items-center gap-2 text-sm text-gray-700">
        總分 {{ isChief ? '≥' : '≤' }}
        <input v-model.number="rule[scoreKey]" type="number" min="0" :class="[inputClass, 'w-20']" />
        且
        <select v-model="rule.question" :class="[inputClass, 'min-w-0 flex-1']" @change="rule.options = []">
          <option v-for="q in questions" :key="q.key" :value="q.key">{{ q.title || '（未命名題目）' }}</option>
        </select>
      </div>
      <p v-if="err('question')" class="text-xs text-red-600">{{ err('question') }}</p>
      <div class="flex flex-wrap gap-x-4 gap-y-1">
        <span class="text-xs text-gray-500">選了：</span>
        <label v-for="o in options" :key="o.key" class="flex cursor-pointer items-center gap-1 text-sm text-gray-700 hover:text-gray-900">
          <input type="checkbox" :checked="rule.options.includes(o.key)" class="rounded text-brand-teal" @change="toggle(o.key)" />
          {{ o.label || '（未命名選項）' }}
        </label>
      </div>
      <p v-if="err('options')" class="text-xs text-red-600">{{ err('options') }}</p>
    </div>
  </div>
</template>
