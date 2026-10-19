<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import QuestionEditor from '@/Components/Admin/FunnelSettings/QuestionEditor.vue'
import ComboRuleEditor from '@/Components/Admin/FunnelSettings/ComboRuleEditor.vue'
import RoutingRuleEditor from '@/Components/Admin/FunnelSettings/RoutingRuleEditor.vue'

defineOptions({ layout: AdminLayout })

// 1v1 漏斗設定 (011 US46): the whole screening rubric on one page — questions,
// the rules that refuse, and consultant routing. Saved as one document.
const props = defineProps({
  rubric: { type: Object, required: true },
  consultantCount: { type: Number, required: true },
})

const clone = (v) => JSON.parse(JSON.stringify(v))

// Combination rules are stored as {question: [options]} maps; the editor works
// on arrays of conditions so a rule can be built one row at a time.
const toConditions = (match) => Object.entries(match ?? {}).map(([question, options]) => ({ question, options: [...options] }))
const toMatch = (conditions) => Object.fromEntries(conditions.filter(c => c.question).map(c => [c.question, c.options]))

const form = useForm({
  pass_score: props.rubric.pass_score,
  tiers: clone(props.rubric.tiers),
  questions: clone(props.rubric.questions).map(q => ({
    hint: '',
    ...q,
    retired: q.retired ?? false,
    options: q.options.map(o => ({ cap: null, ...o, retired: o.retired ?? false })),
  })),
  combos: (props.rubric.combos ?? []).map(c => ({
    conditions: toConditions(c.match),
    unless: c.unless ? { conditions: toConditions(c.unless.match), min_score: c.unless.min_score } : null,
  })),
  routing: clone(props.rubric.routing),
})

const activeQuestions = computed(() => form.questions.filter(q => !q.retired))

// Same definition as the server's maxScore(): best active option per active question.
const maxScore = computed(() => activeQuestions.value.reduce((sum, q) => {
  const scores = q.options.filter(o => !o.retired).map(o => Number(o.score) || 0)
  return sum + (scores.length ? Math.max(...scores) : 0)
}, 0))

const randomKey = () => Math.random().toString(36).slice(2, 8).padEnd(6, '0')

function addQuestion() {
  form.questions.push({
    key: `screen_${randomKey()}`,
    title: '',
    hint: '',
    retired: false,
    answered: false,
    options: [
      { key: randomKey(), label: '', score: 0, veto: 'none', cap: null, retired: false, answered: false },
      { key: randomKey(), label: '', score: 0, veto: 'none', cap: null, retired: false, answered: false },
    ],
  })
}

function moveQuestion(question, delta) {
  const list = form.questions
  const from = list.indexOf(question)
  let to = from + delta
  while (to >= 0 && to < list.length && list[to].retired) to += delta
  if (to < 0 || to >= list.length) return
  list.splice(from, 1)
  list.splice(to, 0, question)
}

// Answered questions are retired, never deleted (FR-252 / FR-265).
function removeQuestion(question) {
  if (question.answered) {
    question.retired = true
    return
  }
  form.questions.splice(form.questions.indexOf(question), 1)
}

const addCombo = () => form.combos.push({ conditions: [], unless: null })

function submit() {
  form
    .transform(data => ({
      ...data,
      combos: data.combos.map(c => ({
        match: toMatch(c.conditions),
        unless: c.unless ? { match: toMatch(c.unless.conditions), min_score: c.unless.min_score } : null,
      })),
    }))
    .put('/admin/funnel-settings', { preserveScroll: true })
}

const hasErrors = computed(() => Object.keys(form.errors).length > 0)

const sectionClass = 'space-y-4 rounded-lg bg-white p-6 shadow-sm'
const inputClass = 'w-24 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand-teal focus:ring-2 focus:ring-brand-teal/30'
</script>

<template>
  <div class="mx-auto max-w-4xl space-y-6 px-4 py-8">
    <div>
      <h1 class="text-xl font-bold text-gray-900">1v1 漏斗設定</h1>
      <p class="mt-1 text-sm text-gray-500">
        預約 1v1 諮詢前的資格審核題目、婉拒規則與顧問分流。全站共用；課程上的「嚴格資格審核」開關決定該課程用嚴格或寬鬆規則。
        修改只影響之後的作答，已存下的分數不會重算。
      </p>
    </div>

    <div v-if="form.recentlySuccessful" class="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700">1v1 漏斗設定已儲存</div>
    <div v-if="hasErrors" class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">有欄位需要修正，請查看下方紅字。</div>

    <form class="space-y-6" @submit.prevent="submit">
      <!-- 題目與選項 -->
      <section :class="sectionClass">
        <div class="flex items-baseline justify-between border-b pb-2">
          <h2 class="text-base font-semibold text-gray-800">題目與選項</h2>
          <span class="text-sm text-gray-500">目前滿分 <strong class="text-gray-800">{{ maxScore }}</strong></span>
        </div>
        <p class="text-xs text-gray-500">
          否決：「一律否決」在嚴格與寬鬆審核都會婉拒；「僅嚴格審核否決」只在課程開啟嚴格審核時婉拒。
          已有人作答的題目或選項，刪除時會改為「停用」——前台不再顯示，後台舊名單仍保留原本的文字。
        </p>
        <p v-if="form.errors.questions" class="text-xs text-red-600">{{ form.errors.questions }}</p>

        <QuestionEditor
          v-for="(question, i) in form.questions"
          :key="question.key"
          :question="question"
          :index="i"
          :total="form.questions.length"
          :errors="form.errors"
          @move="moveQuestion(question, $event)"
          @remove="removeQuestion(question)"
          @restore="question.retired = false"
        />

        <button type="button" class="rounded-lg border border-dashed border-gray-300 px-4 py-2 text-sm text-brand-teal hover:border-brand-teal hover:bg-brand-teal/5" @click="addQuestion">＋ 新增題目</button>
      </section>

      <!-- 審核門檻 -->
      <section :class="sectionClass">
        <h2 class="border-b pb-2 text-base font-semibold text-gray-800">資格審核規則</h2>
        <div class="grid gap-4 sm:grid-cols-3">
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">通過分數（≥）</label>
            <input v-model.number="form.pass_score" type="number" min="0" :class="inputClass" />
            <p class="mt-1 text-xs text-gray-400">總分未達此分數即婉拒（嚴格與寬鬆相同）。</p>
            <p v-if="form.errors.pass_score" class="mt-1 text-xs text-red-600">{{ form.errors.pass_score }}</p>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">「高購買意願」標籤（≥）</label>
            <input v-model.number="form.tiers.hot" type="number" min="0" :class="inputClass" />
            <p v-if="form.errors['tiers.hot']" class="mt-1 text-xs text-red-600">{{ form.errors['tiers.hot'] }}</p>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">「值得談」標籤（≥）</label>
            <input v-model.number="form.tiers.warm" type="number" min="0" :class="inputClass" />
            <p class="mt-1 text-xs text-gray-400">低於此分數標為「培育名單」。只影響後台標籤。</p>
          </div>
        </div>
      </section>

      <!-- 組合否決 -->
      <section :class="sectionClass">
        <h2 class="border-b pb-2 text-base font-semibold text-gray-800">組合否決規則</h2>
        <p class="text-xs text-gray-500">多題答案的組合才構成婉拒時使用。僅在嚴格審核時生效。</p>
        <ComboRuleEditor
          v-for="(rule, i) in form.combos"
          :key="i"
          :rule="rule"
          :index="i"
          :questions="activeQuestions"
          :errors="form.errors"
          @remove="form.combos.splice(i, 1)"
        />
        <button type="button" class="rounded-lg border border-dashed border-gray-300 px-4 py-2 text-sm text-brand-teal hover:border-brand-teal hover:bg-brand-teal/5" @click="addCombo">＋ 新增組合規則</button>
      </section>

      <!-- 顧問分流 -->
      <section :class="sectionClass">
        <h2 class="border-b pb-2 text-base font-semibold text-gray-800">顧問分流規則</h2>
        <p class="text-xs text-gray-500">
          通過審核後，選時段步驟依以下條件決定看得到誰的時段；兩條都不符合時看得到全部。未指派顧問的時段算首席的。
          分到的那組沒有時段時，申請人會進候補名單。
        </p>
        <p
          class="rounded-lg px-3 py-2 text-xs"
          :class="consultantCount > 1 ? 'bg-brand-teal/10 text-brand-teal' : 'bg-amber-50 text-amber-700'"
        >
          分流只在顧問多於 1 位時生效（目前 {{ consultantCount }} 位）。{{ consultantCount > 1 ? '目前已生效。' : '目前所有人都會看到全部時段。' }}
        </p>
        <RoutingRuleEditor :rule="form.routing.chief" pool="chief" :questions="activeQuestions" :errors="form.errors" />
        <RoutingRuleEditor :rule="form.routing.others" pool="others" :questions="activeQuestions" :errors="form.errors" />
      </section>

      <div class="flex justify-end">
        <button
          type="submit"
          :disabled="form.processing"
          class="rounded-lg bg-brand-teal px-5 py-2 text-sm font-semibold text-white transition-colors hover:bg-brand-teal/90 disabled:opacity-50"
        >{{ form.processing ? '儲存中…' : '儲存設定' }}</button>
      </div>
    </form>
  </div>
</template>
