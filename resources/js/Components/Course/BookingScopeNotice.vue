<script setup>
/**
 * Consultant note + scope acknowledgement (011 FR-162 / FR-166).
 *
 * Shared by step 1 of the booking wizard and, on a course with screening off,
 * the commitments step (FR-255) — one copy of the wording so the two cannot
 * drift apart.
 */
defineProps({
  // The wizard's reactive form; `screen_ack` is written in place (FR-165).
  form: { type: Object, required: true },
  error: { type: String, default: '' },
})
</script>

<template>
  <!-- Consultant assignment (FR-166). Plain grey note, not part of the
       tick: what somebody agrees to should be the risk that this may not
       suit them, not how the company staffs its calendar. Bundling the
       two would turn the box into a list of the seller's disclaimers,
       which is what people start defending themselves against. -->
  <p class="text-xs leading-relaxed text-gray-500">
    諮詢由本公司認證顧問提供，每位顧問皆由創辦人親自訓練、使用同一套診斷架構。創辦人親自主持的場次為限量名額，額滿後由認證顧問為您服務。
  </p>

  <!-- Scope acknowledgement (FR-162). Bordered and tinted on purpose:
       this notice exists so that people it does not fit leave before
       finishing, and grey small print is skipped by exactly them. -->
  <div class="rounded-xl border border-brand-gold-dark/40 bg-brand-gold/10 p-4">
    <label class="flex cursor-pointer items-start gap-3">
      <input
        v-model="form.screen_ack"
        type="checkbox"
        class="mt-0.5 h-4 w-4 shrink-0 cursor-pointer rounded border-gray-400 text-brand-teal focus:ring-brand-teal"
      />
      <span class="text-sm leading-relaxed text-gray-800">
        <span class="font-semibold">請知悉：</span>本諮詢不提供美食、旅遊、生活分享、網紅接案或純流量型帳號的定位建議。若您的主要目標是上述方向，本服務可能不適合您。
      </span>
    </label>
    <p v-if="error" class="mt-2 text-sm text-red-600">{{ error }}</p>
  </div>
</template>
