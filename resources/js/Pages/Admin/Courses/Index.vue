<script setup>
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref, computed } from 'vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  courses: {
    type: Array,
    required: true,
  },
  contentCategories: {
    type: Array,
    default: () => [],
  },
})

const deletingCourseId = ref(null)

// Client-side search + filters (the list loads every course, so filtering stays snappy).
const search = ref('')
const activeCategory = ref('') // content_category slug
const activeType = ref('')     // product_type value

// Product types (matches CourseForm 的講座 / 迷你課 / 客製服務).
const productTypes = [
  { value: 'lecture', label: '講座課程' },
  { value: 'mini', label: '迷你課程' },
  { value: 'high_ticket', label: '客製服務' },
]

const filteredCourses = computed(() => {
  const q = search.value.trim().toLowerCase()
  return props.courses.filter((c) => {
    const matchSearch = !q
      || (c.name && c.name.toLowerCase().includes(q))
      || (c.instructor_name && c.instructor_name.toLowerCase().includes(q))
    const matchCategory = !activeCategory.value || c.content_category === activeCategory.value
    const matchType = !activeType.value || c.product_type === activeType.value
    return matchSearch && matchCategory && matchType
  })
})

const toggleCategory = (slug) => {
  activeCategory.value = activeCategory.value === slug ? '' : slug
}
const toggleType = (value) => {
  activeType.value = activeType.value === value ? '' : value
}

const statusBadge = (status) => {
  const badges = {
    draft: { text: '草稿', class: 'bg-gray-100 text-gray-800' },
    preorder: { text: '預購中', class: 'bg-yellow-100 text-yellow-800' },
    selling: { text: '熱賣中', class: 'bg-green-100 text-green-800' },
  }
  return badges[status] || badges.draft
}

const confirmDelete = (course) => {
  if (confirm(`確定要刪除課程「${course.name}」嗎？`)) {
    router.delete(`/admin/courses/${course.id}`)
  }
}

const formatPrice = (price) => {
  return new Intl.NumberFormat('zh-TW', {
    style: 'currency',
    currency: 'TWD',
    minimumFractionDigits: 0,
  }).format(price)
}
</script>

<template>
  <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
      <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
          <h1 class="text-2xl font-semibold text-gray-900">課程管理</h1>
          <p class="mt-2 text-sm text-gray-700">
            管理所有課程，包含課程資訊、章節編輯、相簿管理。
          </p>
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
          <Link
            href="/admin/courses/create"
            class="inline-flex items-center justify-center rounded-md border border-transparent bg-brand-teal px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-brand-teal/90 focus:outline-none focus:ring-2 focus:ring-brand-teal focus:ring-offset-2"
          >
            新增課程
          </Link>
        </div>
      </div>

      <!-- Search (left) + filters (right) -->
      <div class="mt-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <input
          v-model="search"
          type="text"
          placeholder="搜尋課程名稱 / 講師"
          class="w-full lg:w-72 lg:flex-shrink-0 rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-brand-teal focus:ring-brand-teal"
        />

        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 lg:justify-end">
          <div v-if="contentCategories.length" class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-medium text-gray-500">內容分類：</span>
            <button
              v-for="cat in contentCategories"
              :key="cat.slug"
              type="button"
              class="px-3 py-1 rounded-full text-sm font-medium border cursor-pointer transition-colors"
              :class="activeCategory === cat.slug
                ? 'bg-brand-teal text-white border-brand-teal'
                : 'bg-white text-gray-600 border-gray-300 hover:border-brand-teal hover:text-brand-teal'"
              @click="toggleCategory(cat.slug)"
            >{{ cat.label }}</button>
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-medium text-gray-500">課程類型：</span>
            <button
              v-for="t in productTypes"
              :key="t.value"
              type="button"
              class="px-3 py-1 rounded-full text-sm font-medium border cursor-pointer transition-colors"
              :class="activeType === t.value
                ? 'bg-brand-navy text-white border-brand-navy'
                : 'bg-white text-gray-600 border-gray-300 hover:border-brand-navy hover:text-brand-navy'"
              @click="toggleType(t.value)"
            >{{ t.label }}</button>
          </div>

          <!-- Always rendered to reserve width, so the chips don't jump when a filter is active. -->
          <button
            type="button"
            class="text-xs text-gray-400 hover:text-gray-600 underline cursor-pointer"
            :class="(search || activeCategory || activeType) ? '' : 'invisible pointer-events-none'"
            @click="search = ''; activeCategory = ''; activeType = ''"
          >清除篩選</button>
        </div>
      </div>

      <div class="mt-8 flex flex-col">
        <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
          <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
            <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  <tr>
                    <th scope="col" class="px-4 py-3 text-left">
                      課程
                    </th>
                    <th scope="col" class="px-4 py-3 text-left">
                      講師
                    </th>
                    <th scope="col" class="px-4 py-3 text-left">
                      狀態
                    </th>
                    <th scope="col" class="px-4 py-3 text-left">
                      價格
                    </th>
                    <th scope="col" class="px-4 py-3 text-left">
                      時長
                    </th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                      <span class="sr-only">操作</span>
                    </th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                  <tr v-for="course in filteredCourses" :key="course.id" :class="{ 'bg-gray-50': course.deleted_at }">
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 sm:pl-6">
                      <div class="flex items-center">
                        <div class="h-10 w-10 flex-shrink-0">
                          <img
                            v-if="course.thumbnail"
                            class="h-10 w-10 rounded object-cover"
                            :src="`/storage/${course.thumbnail}`"
                            :alt="course.name"
                          />
                          <div v-else class="h-10 w-10 rounded bg-gray-200 flex items-center justify-center">
                            <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                          </div>
                        </div>
                        <div class="ml-4">
                          <div class="font-medium text-gray-900">{{ course.name }}</div>
                          <div v-if="course.sale_at" class="text-sm text-gray-500">
                            開賣: {{ course.sale_at }}
                          </div>
                        </div>
                      </div>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                      {{ course.instructor_name }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                      <div class="flex items-center gap-2">
                        <span
                          class="inline-flex rounded-full px-2 text-xs font-semibold leading-5"
                          :class="statusBadge(course.status).class"
                        >
                          {{ statusBadge(course.status).text }}
                        </span>
                        <span
                          v-if="!course.is_visible"
                          class="inline-flex rounded-full px-2 text-xs font-semibold leading-5 bg-purple-100 text-purple-800"
                          title="此課程已隱藏，不會顯示於首頁"
                        >
                          隱藏
                        </span>
                      </div>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                      <div class="text-gray-900">{{ formatPrice(course.price) }}</div>
                      <div v-if="course.original_price" class="text-xs text-gray-400 line-through">
                        {{ formatPrice(course.original_price) }}
                      </div>
                      <div v-if="course.is_promo_active" class="text-xs text-red-600">
                        優惠中
                      </div>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                      {{ course.duration_formatted || '-' }}
                    </td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                      <div class="flex items-center justify-end space-x-3">
                        <!-- 瀏覽 -->
                        <a
                          :href="`/course/${course.id}`"
                          target="_blank"
                          rel="noopener noreferrer"
                          class="text-gray-500 hover:text-gray-800"
                        >
                          銷售頁
                        </a>
                        <!-- 內容管理 -->
                        <Link
                          :href="`/admin/courses/${course.id}/edit`"
                          class="text-brand-teal hover:text-brand-navy"
                        >
                          編輯
                        </Link>
                        <Link
                          :href="`/admin/courses/${course.id}/chapters`"
                          class="text-brand-teal hover:text-brand-navy"
                        >
                          章節
                        </Link>
                        <Link
                          :href="`/admin/courses/${course.id}/images`"
                          class="text-brand-teal hover:text-brand-navy"
                        >
                          相簿
                        </Link>
                        <!-- 數據分析 -->
                        <Link
                          v-if="!course.portaly_product_id"
                          :href="`/admin/courses/${course.id}/traffic`"
                          class="text-violet-600 hover:text-violet-900"
                        >
                          來源
                        </Link>
                        <!-- 危險操作 -->
                        <button
                          type="button"
                          class="text-red-500 hover:text-red-700"
                          @click="confirmDelete(course)"
                        >
                          刪除
                        </button>
                      </div>
                    </td>
                  </tr>
                  <tr v-if="filteredCourses.length === 0">
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                      {{ courses.length === 0 ? '尚無課程，點擊「新增課程」開始建立。' : '沒有符合篩選條件的課程。' }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
  </div>
</template>
