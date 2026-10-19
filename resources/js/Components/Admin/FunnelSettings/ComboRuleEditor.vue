<script setup>
import ConditionList from '@/Components/Admin/FunnelSettings/ConditionList.vue'

// One combination veto (011 US46 / FR-254): every `match` condition holds →
// refused, unless the optional `unless` conditions hold and the total reaches
// `min_score`. Shape here is the page's editing shape — conditions as arrays.
const props = defineProps({
  rule: { type: Object, required: true },
  index: { type: Number, required: true },
  questions: { type: Array, required: true },
  errors: { type: Object, default: () => ({}) },
})

const emit = defineEmits(['remove'])

const err = (path) => props.errors[`combos.${props.index}.${path}`]

function toggleUnless(on) {
  props.rule.unless = on ? { conditions: [], min_score: 7 } : null
}
</script>

<template>
  <div class="space-y-3 rounded-lg border border-gray-200 bg-white p-4">
    <div class="flex items-center justify-between">
      <h3 class="text-sm font-semibold text-gray-700">規則 {{ index + 1 }}：符合以下全部條件 → 婉拒</h3>
      <button type="button" class="rounded px-2 py-1 text-xs text-red-500 hover:bg-red-50 hover:text-red-700" @click="emit('remove')">刪除規則</button>
    </div>

    <ConditionList :conditions="rule.conditions" :questions="questions" />
    <p v-if="err('match')" class="text-xs text-red-600">{{ err('match') }}</p>

    <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-700 hover:text-gray-900">
      <input type="checkbox" :checked="rule.unless !== null" class="rounded text-brand-teal" @change="toggleUnless($event.target.checked)" />
      加上「除非」例外
    </label>

    <div v-if="rule.unless" class="space-y-2 border-l-2 border-brand-teal/40 pl-3">
      <p class="text-xs text-gray-500">除非符合以下全部條件，且總分達到門檻：</p>
      <ConditionList :conditions="rule.unless.conditions" :questions="questions" />
      <p v-if="err('unless.match')" class="text-xs text-red-600">{{ err('unless.match') }}</p>
      <div class="flex items-center gap-2 text-sm text-gray-700">
        總分 ≥
        <input v-model.number="rule.unless.min_score" type="number" min="0" class="w-20 rounded-lg border border-gray-300 px-2 py-1 text-sm focus:border-brand-teal focus:ring-2 focus:ring-brand-teal/30" />
      </div>
    </div>
  </div>
</template>
