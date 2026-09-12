<script setup>
import { Link } from '@inertiajs/vue3'
import HeroSubscribeForm from '@/Components/Home/HeroSubscribeForm.vue'

defineProps({
  // { title, subtitle, description, banner_url } — every field renders only
  // when set, and none of them gates any of the others (FR-053).
  hero: {
    type: Object,
    default: () => ({ title: null, subtitle: null, description: null, banner_url: null }),
  },
  // { course_id, name, url } or null. Drives the 📌 line ONLY — the subscribe
  // form is the newsletter's and no longer tied to a product (FR-063).
  heroPromo: {
    type: Object,
    default: null,
  },
})
</script>

<template>
  <section class="w-full bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- 600px is the desktop design height only: on a phone the two-line
           title, subtitle, intro and stacked form already exceed it, and what
           a fixed height would cut off is the claim button (FR-052 / D58). -->
      <div class="flex flex-col gap-8 py-10 lg:h-[600px] lg:flex-row lg:items-center lg:gap-12 lg:py-0">
        <!-- Left column: copy + claim. Grows to full width when there is no image. -->
        <div class="min-w-0 flex-1">
          <h1
            v-if="hero.title"
            class="whitespace-pre-line text-3xl font-bold leading-tight tracking-wide text-brand-navy sm:text-4xl lg:text-5xl lg:leading-[1.25]"
          >
            {{ hero.title }}
          </h1>

          <p
            v-if="hero.subtitle"
            class="mt-5 whitespace-pre-line text-base font-medium text-brand-teal sm:text-lg"
          >
            {{ hero.subtitle }}
          </p>

          <p
            v-if="hero.description"
            class="mt-4 max-w-xl whitespace-pre-line text-sm leading-relaxed text-gray-600 sm:text-base"
          >
            {{ hero.description }}
          </p>

          <!-- Always on: the newsletter is not a campaign that starts and
               stops, so there is no state where hiding this helps (D64) -->
          <div class="mt-7 max-w-xl">
            <HeroSubscribeForm />
          </div>

          <!-- Independent of the form now — this is whatever product is being
               pushed this season, and it links to where it is explained (D59) -->
          <Link
            v-if="heroPromo"
            :href="heroPromo.url"
            class="mt-4 inline-flex cursor-pointer items-center gap-1 text-sm text-gray-600 transition-colors hover:text-brand-teal"
          >
            <span>📌 立刻領取</span>
            <span class="font-medium text-brand-teal underline underline-offset-4">「{{ heroPromo.name }}」</span>
          </Link>
        </div>

        <!-- Right column: portrait image. Absent entirely when unset — no
             placeholder block, no fallback colour panel (FR-054). -->
        <div v-if="hero.banner_url" class="flex shrink-0 justify-center lg:w-[45%]">
          <img
            :src="hero.banner_url"
            alt=""
            class="max-h-[280px] w-auto object-contain lg:max-h-[520px]"
          />
        </div>
      </div>
    </div>
  </section>
</template>
