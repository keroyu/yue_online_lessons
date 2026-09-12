<script setup>
import { ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import CourseCard from '@/Components/CourseCard.vue'
import HomePostList from '@/Components/Newsletter/HomePostList.vue'
import SectionHeader from '@/Components/SectionHeader.vue'
import Sidebar from '@/Components/Layout/Sidebar.vue'
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
  sidebarOrder: {
    type: Array,
    default: () => ['featured_courses', 'social', 'blog'],
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

// Content category filter (null = 全部); categories come from admin settings
const selectedCategory = ref(null)

function toggleCategory(value) {
  // Click the active category again to clear the filter (show 全部)
  selectedCategory.value = selectedCategory.value === value ? null : value
}

// Product-type filter badges (迷你課 / 講座 / 完整課程 / 客製服務 / 電子書)
const typeLabels = {
  lecture: '講座',
  mini: '迷你課',
  full: '完整課程',
  high_ticket: '客製服務',
  ebook: '電子書',
}
const typeOrder = ['lecture', 'mini', 'full', 'high_ticket', 'ebook']
const availableTypes = computed(() =>
  typeOrder.filter(t => props.courses.some(c => c.product_type === t))
)
const selectedType = ref(null)

function toggleType(value) {
  selectedType.value = selectedType.value === value ? null : value
}

// Both filters combine (AND)
const filteredCourses = computed(() =>
  props.courses.filter(c =>
    (!selectedCategory.value || c.content_category === selectedCategory.value) &&
    (!selectedType.value || c.product_type === selectedType.value)
  )
)
</script>

<template>
  <Head title="首頁" />

  <!-- Hero sits outside the padded wrapper: it is the first block under the
       nav bar and runs the full width on its own white ground (FR-052) -->
  <HeroUnit :hero="hero" :hero-promo="heroPromo" />

  <div class="py-8 sm:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

      <!-- Main content with sidebar layout -->
      <div class="grid grid-cols-1 lg:grid-cols-[1fr_365px] gap-6">
        <!-- Main area: Courses -->
        <div class="min-w-0">
          <!-- Popular posts list (above the category buttons) -->
          <HomePostList :posts="popularPosts" />

          <div v-if="courses.length > 0">
            <!-- Category filter buttons (admin-configured): bright label on dark, text scales up on hover -->
            <div v-if="contentCategories.length > 0" class="flex gap-3 mb-6">
              <button
                v-for="cat in contentCategories"
                :key="cat.slug"
                type="button"
                class="group flex-1 min-w-0 max-w-[300px] py-4 text-center font-bold cursor-pointer border-b-4 transition-colors"
                :class="selectedCategory === cat.slug
                  ? 'bg-brand-teal text-white border-brand-gold'
                  : 'bg-brand-navy text-brand-gold hover:bg-brand-navy/90 border-transparent'"
                @click="toggleCategory(cat.slug)"
              >
                <span class="inline-block transition-transform duration-200 group-hover:scale-110">
                  {{ cat.label }}
                </span>
              </button>
            </div>

            <SectionHeader title="所有資源">
              <template v-if="availableTypes.length > 0" #right>
                <div class="flex items-center gap-1.5">
                  <button
                    v-for="t in availableTypes"
                    :key="t"
                    type="button"
                    class="cursor-pointer border px-2.5 py-1 text-sm sm:text-xs font-medium transition-colors"
                    :class="selectedType === t
                      ? 'bg-brand-navy text-white border-brand-navy'
                      : 'bg-white text-gray-600 border-gray-300 hover:border-brand-navy hover:text-brand-navy'"
                    @click="toggleType(t)"
                  >
                    {{ typeLabels[t] }}
                  </button>
                </div>
              </template>
            </SectionHeader>

            <div v-if="filteredCourses.length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <CourseCard
                v-for="course in filteredCourses"
                :key="course.id"
                :course="course"
                :show-status-badge="isAdmin"
              />
            </div>
            <p v-else class="text-sm text-gray-500 py-8">此分類目前沒有課程。</p>
          </div>

          <div v-else class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">目前沒有課程</h3>
            <p class="mt-1 text-sm text-gray-500">敬請期待新課程上架！</p>
          </div>
        </div>

        <!-- Sidebar: widgets rendered in admin-defined order -->
        <Sidebar
          :sidebar-order="sidebarOrder"
          :featured-courses="featuredCourses"
          :social-links="socialLinks"
          :sns-profile="snsProfile"
          :blog-articles="blogArticles"
        />
      </div>

    </div>
  </div>
</template>
