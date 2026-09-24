<script setup>
import { computed, ref, nextTick } from 'vue'
import { marked } from 'marked'

const props = defineProps({
  board: {
    type: Object,
    required: true,
  },
  courseId: {
    type: [Number, String],
    required: true,
  },
  // The admin modal renders the same board (D33): no ticking, no hover, no fetch.
  readonly: {
    type: Boolean,
    default: false,
  },
})

// Local mirror so a tick paints immediately; the request follows behind and
// rolls this back if it fails (same optimistic model as lesson progress, D2).
const stages = ref(
  props.board.stages.map((stage) => ({
    ...stage,
    checkpoints: stage.checkpoints.map((checkpoint) => ({ ...checkpoint })),
  }))
)

const pending = ref(new Set())
const error = ref(null)
const celebrating = ref(new Set())
const boardCelebrating = ref(false)

const stageDone = (stage) => stage.total > 0 && stage.completed_count === stage.total

const completedCount = computed(() =>
  stages.value.reduce((sum, stage) => sum + stage.completed_count, 0)
)

const totalCount = computed(() =>
  stages.value.reduce((sum, stage) => sum + stage.total, 0)
)

const percent = computed(() =>
  totalCount.value === 0 ? 0 : Math.round((completedCount.value / totalCount.value) * 100)
)

// The first stage that is not finished — "you are here".
const currentStageIndex = computed(() => {
  const index = stages.value.findIndex((stage) => !stageDone(stage))
  return index === -1 ? stages.value.length - 1 : index
})

const stagePercent = (stage) =>
  stage.total === 0 ? 0 : Math.round((stage.completed_count / stage.total) * 100)

const stageState = (stage, index) => {
  if (stageDone(stage)) return 'done'
  if (stage.completed_count > 0 || index === currentStageIndex.value) return 'active'
  return 'todo'
}

const renderedDescription = (stage) =>
  stage.description_md ? marked(stage.description_md, { breaks: true }) : ''

const pulse = async (stageId) => {
  celebrating.value.add(stageId)
  await nextTick()
  window.setTimeout(() => {
    celebrating.value.delete(stageId)
    celebrating.value = new Set(celebrating.value)
  }, 1200)
  celebrating.value = new Set(celebrating.value)
}

const toggle = async (stage, checkpoint) => {
  if (props.readonly || pending.value.has(checkpoint.id)) return

  const next = !checkpoint.completed
  const wasStageDone = stageDone(stage)

  // Optimistic paint.
  checkpoint.completed = next
  stage.completed_count += next ? 1 : -1
  pending.value = new Set(pending.value).add(checkpoint.id)
  error.value = null

  if (next && !wasStageDone && stageDone(stage)) {
    pulse(stage.id)
    if (completedCount.value === totalCount.value) {
      boardCelebrating.value = true
      window.setTimeout(() => { boardCelebrating.value = false }, 1600)
    }
  }

  try {
    const response = await fetch(
      `/member/classroom/${props.courseId}/roadmap/${checkpoint.id}`,
      {
        method: next ? 'POST' : 'DELETE',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        },
      }
    )

    if (!response.ok) throw new Error(`HTTP ${response.status}`)
  } catch (e) {
    // Roll the optimistic paint back rather than leaving a tick the server
    // never recorded.
    checkpoint.completed = !next
    stage.completed_count += next ? -1 : 1
    error.value = '儲存失敗，請檢查網路後再試一次'
  } finally {
    const stillPending = new Set(pending.value)
    stillPending.delete(checkpoint.id)
    pending.value = stillPending
  }
}
</script>

<template>
  <div class="roadmap-board">
    <!-- Overall header -->
    <div
      class="rounded-xl bg-white shadow-sm p-5 mb-6"
      :class="{ 'roadmap-board-celebrate': boardCelebrating }"
    >
      <div class="flex flex-wrap items-baseline justify-between gap-2">
        <h2 class="text-xl lg:text-2xl font-bold text-brand-navy">
          {{ board.title }} 階段檢核表
        </h2>
        <span class="text-sm font-medium text-gray-500">
          {{ completedCount }} / {{ totalCount }}
          <span class="ml-1 text-brand-teal font-semibold">{{ percent }}%</span>
        </span>
      </div>

      <div class="mt-3 h-2.5 w-full rounded-full bg-gray-100 overflow-hidden">
        <div
          class="roadmap-bar h-full rounded-full bg-gradient-to-r from-brand-teal to-brand-gold"
          :style="{ width: `${percent}%` }"
        />
      </div>

      <p v-if="!readonly" class="mt-2 text-xs text-gray-400">
        這份清單只有你自己看得到進度，勾選純粹是給自己對照用的。
      </p>
    </div>

    <p v-if="error" class="mb-4 rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">
      {{ error }}
    </p>

    <!-- Vertical path -->
    <div class="relative">
      <div
        v-for="(stage, index) in stages"
        :key="stage.id"
        class="relative pl-10 sm:pl-12 pb-6 last:pb-0"
      >
        <!-- Spine segment (skipped on the last stage) -->
        <span
          v-if="index < stages.length - 1"
          class="roadmap-spine absolute left-[15px] sm:left-[19px] top-9 bottom-0 w-0.5"
          :class="stageDone(stage) ? 'bg-brand-teal' : 'bg-gray-200'"
        />

        <!-- Node -->
        <span
          class="roadmap-node absolute left-0 top-1 flex h-8 w-8 sm:h-10 sm:w-10 items-center justify-center rounded-full border-2 text-sm font-bold"
          :class="{
            'bg-brand-teal border-brand-teal text-white': stageState(stage, index) === 'done',
            'bg-white border-brand-teal text-brand-teal': stageState(stage, index) === 'active',
            'bg-white border-gray-300 text-gray-400': stageState(stage, index) === 'todo',
            'roadmap-node-celebrate': celebrating.has(stage.id),
          }"
        >
          <svg v-if="stageState(stage, index) === 'done'" class="h-4 w-4 sm:h-5 sm:w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
          <template v-else>{{ index + 1 }}</template>
        </span>

        <!-- Stage card -->
        <div
          class="rounded-xl border bg-white p-4 sm:p-5 shadow-sm transition-colors"
          :class="stageState(stage, index) === 'todo'
            ? 'border-gray-200'
            : 'border-brand-teal/40'"
        >
          <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h3 class="text-base sm:text-lg font-semibold text-brand-navy">
              {{ stage.title }}
            </h3>
            <span class="text-xs font-medium text-gray-500">
              {{ stage.completed_count }} / {{ stage.total }}
            </span>
          </div>

          <div
            v-if="stage.description_md"
            class="course-content mt-2 text-sm"
            v-html="renderedDescription(stage)"
          />

          <div class="mt-3 h-1.5 w-full rounded-full bg-gray-100 overflow-hidden">
            <div
              class="roadmap-bar h-full rounded-full bg-brand-teal"
              :style="{ width: `${stagePercent(stage)}%` }"
            />
          </div>

          <!-- Checkpoints -->
          <ul class="mt-3 space-y-1">
            <li
              v-for="checkpoint in stage.checkpoints"
              :key="checkpoint.id"
            >
              <component
                :is="readonly ? 'div' : 'button'"
                :type="readonly ? null : 'button'"
                class="group flex w-full items-start gap-2.5 rounded-lg px-2 py-1.5 text-left transition-colors"
                :class="readonly ? '' : 'cursor-pointer hover:bg-brand-cream/70'"
                :disabled="readonly ? null : pending.has(checkpoint.id)"
                @click="readonly ? null : toggle(stage, checkpoint)"
              >
                <span
                  class="roadmap-check mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-md border-2 transition-all"
                  :class="checkpoint.completed
                    ? 'bg-brand-teal border-brand-teal text-white roadmap-check-on'
                    : 'border-gray-300 bg-white group-hover:border-brand-teal'"
                >
                  <svg v-if="checkpoint.completed" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                  </svg>
                </span>

                <span
                  class="text-sm leading-6 transition-colors"
                  :class="checkpoint.completed
                    ? 'text-gray-400 line-through'
                    : 'text-gray-700'"
                >
                  {{ checkpoint.label }}
                </span>
              </component>
            </li>
          </ul>

          <p v-if="!stage.checkpoints.length" class="mt-3 text-sm text-gray-400">
            這個階段還沒有檢核項目。
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.roadmap-bar {
  transition: width 500ms cubic-bezier(0.22, 1, 0.36, 1);
}

.roadmap-spine,
.roadmap-node {
  transition: background-color 300ms ease, border-color 300ms ease, color 300ms ease;
}

/* Tick: the box pops as it fills. */
.roadmap-check-on {
  animation: roadmap-pop 320ms cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes roadmap-pop {
  0%   { transform: scale(1); }
  45%  { transform: scale(1.35); }
  100% { transform: scale(1); }
}

/* Stage finished: a gold halo rings out from the node. */
.roadmap-node-celebrate {
  animation: roadmap-halo 1.2s ease-out;
}

@keyframes roadmap-halo {
  0%   { box-shadow: 0 0 0 0 color-mix(in oklab, var(--color-brand-gold) 85%, transparent); }
  70%  { box-shadow: 0 0 0 16px color-mix(in oklab, var(--color-brand-gold) 0%, transparent); }
  100% { box-shadow: 0 0 0 0 color-mix(in oklab, var(--color-brand-gold) 0%, transparent); }
}

/* Whole roadmap finished. */
.roadmap-board-celebrate {
  animation: roadmap-board-glow 1.6s ease-out;
}

@keyframes roadmap-board-glow {
  0%   { box-shadow: 0 0 0 0 color-mix(in oklab, var(--color-brand-teal) 50%, transparent); }
  60%  { box-shadow: 0 0 0 14px color-mix(in oklab, var(--color-brand-teal) 0%, transparent); }
  100% { box-shadow: 0 0 0 0 color-mix(in oklab, var(--color-brand-teal) 0%, transparent); }
}

@media (prefers-reduced-motion: reduce) {
  .roadmap-bar,
  .roadmap-spine,
  .roadmap-node,
  .roadmap-check,
  .roadmap-check-on,
  .roadmap-node-celebrate,
  .roadmap-board-celebrate {
    animation: none !important;
    transition: none !important;
  }
}
</style>
