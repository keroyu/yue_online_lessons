<script setup>
import { computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import WidgetColumn from '@/Components/Layout/WidgetColumn.vue'
import HeroUnit from '@/Components/Home/HeroUnit.vue'

const props = defineProps({
  courses: {
    type: Array,
    required: true,
  },
  featuredCourses: {
    type: Array,
    default: () => [],
  },
  // Ordered, visibility-filtered widget descriptors for each column (002 US23)
  mainWidgets: {
    type: Array,
    default: () => [],
  },
  sideWidgets: {
    type: Array,
    default: () => [],
  },
  contentCategories: {
    type: Array,
    default: () => [],
  },
  hero: {
    type: Object,
    default: () => ({
      title: null,
      subtitle: null,
      description: null,
      banner_url: null,
    }),
  },
  heroPromo: {
    type: Object,
    default: null,
  },
  socialLinks: {
    type: Array,
    default: () => [],
  },
  snsProfile: {
    type: Object,
    default: null,
  },
  popularPosts: {
    type: Array,
    default: () => [],
  },
  blogArticles: {
    type: Array,
    default: () => [],
  },
  isAdmin: {
    type: Boolean,
    default: false,
  },
})

// One bag both columns draw from; WidgetColumn picks what each widget needs.
const widgetPayload = computed(() => ({
  courses: props.courses,
  contentCategories: props.contentCategories,
  isAdmin: props.isAdmin,
  popularPosts: props.popularPosts,
  featuredCourses: props.featuredCourses,
  socialLinks: props.socialLinks,
  snsProfile: props.snsProfile,
  blogArticles: props.blogArticles,
}))
</script>

<template>
  <Head title="首頁" />

  <!-- Hero sits outside the padded wrapper: it is the first block under the
       nav bar and runs the full width on its own white ground (FR-052) -->
  <HeroUnit :hero="hero" :hero-promo="heroPromo" />

  <div class="py-8 sm:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

      <!-- Both columns render admin-ordered widgets (002 US23) -->
      <div class="grid grid-cols-1 lg:grid-cols-[1fr_365px] gap-6">
        <WidgetColumn area="main" :widgets="mainWidgets" :payload="widgetPayload" />
        <WidgetColumn area="side" :widgets="sideWidgets" :payload="widgetPayload" />
      </div>

    </div>
  </div>
</template>
