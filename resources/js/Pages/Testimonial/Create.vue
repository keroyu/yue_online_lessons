<script setup>
import { computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'
import TestimonialCarousel from '@/Components/Course/TestimonialCarousel.vue'

defineOptions({ layout: AppLayout })

// 011 US40 — the testimonial form behind the follow-up email link.
const props = defineProps({
  course_name: { type: String, default: null },
  identity_label: { type: String, default: '' },
  default_nickname: { type: String, default: '' },
  submitted: { type: Boolean, default: false },
  reward_points: { type: Number, default: 0 },
  awarded_points: { type: Number, default: null },
  // Others' testimonials and the way back to the course — consultation
  // clients only, so a student gets neither (011 FR-251).
  testimonials: { type: Array, default: () => [] },
  course_url: { type: String, default: null },
})

const form = useForm({
  nickname: props.default_nickname || '',
  title: '',
  content: '',
})

const contentLength = computed(() => form.content.trim().length)

const submit = () => {
  // Keep the scroll on validation errors; on success the thank-you card must
  // be in view, not wherever the long form left the page.
  form.post(window.location.pathname, {
    preserveScroll: (page) => Object.keys(page.props.errors || {}).length > 0,
  })
}

const labelClasses = 'block text-sm font-medium text-gray-700 mb-1'
const inputClasses = 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-teal/30 focus:border-brand-teal text-base sm:text-sm'
</script>

<template>
  <Head title="留下評語">
    <meta name="robots" content="noindex, nofollow" />
  </Head>

  <!-- Form page: the band sits above the form; below it, it went unseen
       (FR-251, revised at the owner's request). -->
  <div v-if="!submitted && testimonials.length" class="bg-brand-cream border-b border-gray-200 px-4 py-10 sm:py-12">
    <div class="max-w-4xl mx-auto">
      <TestimonialCarousel :testimonials="testimonials" />
      <div v-if="course_url" class="mt-6 text-center">
        <a
          :href="course_url"
          class="inline-block text-sm font-medium text-brand-teal underline hover:opacity-70 transition"
        >
          看課程介紹
        </a>
      </div>
    </div>
  </div>

  <div class="max-w-lg mx-auto px-4 pt-12 sm:pt-16" :class="submitted && testimonials.length ? 'pb-10' : 'pb-12 sm:pb-16'">
    <!-- Thank-you state -->
    <div v-if="submitted" class="rounded-2xl border border-green-200 bg-green-50 p-8 text-center text-green-800">
      <div class="w-12 h-12 mx-auto rounded-full bg-white/70 flex items-center justify-center text-2xl font-bold">✓</div>
      <h1 class="mt-4 text-xl font-bold">謝謝你留下評語！</h1>
      <template v-if="awarded_points > 0">
        <p class="mt-3 text-sm leading-relaxed">
          已獲得 <strong class="text-base">{{ awarded_points }}</strong> 點積分。
        </p>
        <p class="mt-1 text-sm leading-relaxed">可用這次預約的 Email 登入，在「我的積分」查看。</p>
      </template>
      <p v-else class="mt-3 text-sm leading-relaxed">你的回饋對我們很重要。</p>
      <a
        v-if="course_url"
        :href="course_url"
        class="inline-block mt-6 px-6 py-2.5 rounded-lg text-sm font-semibold bg-brand-teal text-white hover:bg-brand-teal/90 transition-colors"
      >
        看課程介紹
      </a>
      <div>
        <Link
          href="/"
          class="inline-block mt-4 text-sm text-brand-teal underline cursor-pointer hover:opacity-70 transition"
        >
          回首頁
        </Link>
      </div>
    </div>

    <!-- Form -->
    <div v-else class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 sm:p-8">
      <h1 class="text-xl font-bold text-gray-900">留下你的諮詢評語</h1>
      <p class="mt-2 text-sm text-gray-600 leading-relaxed">
        謝謝你撥空和我們聊聊。寫下這次諮詢對你的幫助，讓還在猶豫的人也能參考。
        <span v-if="reward_points > 0" class="block mt-1 font-medium text-brand-teal">
          送出即獲得 {{ reward_points }} 點積分。
        </span>
      </p>

      <div class="mt-5 rounded-xl bg-brand-cream px-4 py-3 text-sm text-gray-700 space-y-1">
        <p v-if="course_name"><span class="text-gray-500">課程：</span>{{ course_name }}</p>
        <!-- Decided by the server from the client's purchases; shown, never chosen (011 FR-247). -->
        <p><span class="text-gray-500">身份：</span>{{ identity_label }}</p>
      </div>

      <form class="mt-6 space-y-5" @submit.prevent="submit">
        <div>
          <label for="nickname" :class="labelClasses">暱稱 <span class="text-red-500">*</span></label>
          <input id="nickname" v-model="form.nickname" type="text" maxlength="20" :class="inputClasses" />
          <p v-if="form.errors.nickname" class="mt-1 text-sm text-red-600">{{ form.errors.nickname }}</p>
        </div>

        <div>
          <label for="title" :class="labelClasses">頭銜 <span class="text-gray-400 font-normal">（選填）</span></label>
          <input
            id="title"
            v-model="form.title"
            type="text"
            maxlength="40"
            placeholder="例如：自由接案設計師"
            :class="inputClasses"
          />
          <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
        </div>

        <div>
          <label for="content" :class="labelClasses">評語 <span class="text-red-500">*</span></label>
          <textarea
            id="content"
            v-model="form.content"
            rows="6"
            maxlength="500"
            placeholder="這次諮詢帶給你什麼收穫？"
            :class="inputClasses"
          />
          <div class="mt-1 flex items-start justify-between gap-3">
            <p v-if="form.errors.content" class="text-sm text-red-600">{{ form.errors.content }}</p>
            <p v-else class="text-xs text-gray-400">至少 10 個字</p>
            <p class="text-xs text-gray-400 tabular-nums shrink-0">{{ contentLength }} / 500</p>
          </div>
        </div>

        <button
          type="submit"
          :disabled="form.processing"
          class="w-full px-6 py-3 rounded-lg font-semibold bg-brand-teal text-white hover:bg-brand-teal/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ form.processing ? '送出中…' : '送出評語' }}
        </button>
      </form>
    </div>
  </div>

  <!-- Thank-you page: the band follows the short thank-you card (FR-251). -->
  <div v-if="submitted && testimonials.length" class="bg-brand-cream border-t border-gray-200 px-4 py-10 sm:py-12">
    <div class="max-w-4xl mx-auto">
      <TestimonialCarousel :testimonials="testimonials" />
      <div v-if="course_url" class="mt-6 text-center">
        <a
          :href="course_url"
          class="inline-block text-sm font-medium text-brand-teal underline hover:opacity-70 transition"
        >
          看課程介紹
        </a>
      </div>
    </div>
  </div>
</template>
