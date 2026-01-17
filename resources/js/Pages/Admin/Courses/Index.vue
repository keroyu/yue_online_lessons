<script setup>
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref } from 'vue'

defineProps({
  courses: {
    type: Array,
    required: true,
  },
})

const deletingCourseId = ref(null)

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
  <AdminLayout>
    <div class="px-4 sm:px-6 lg:px-8">
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
            class="inline-flex items-center justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
          >
            新增課程
          </Link>
        </div>
      </div>

      <div class="mt-8 flex flex-col">
        <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
          <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
            <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
              <table class="min-w-full divide-y divide-gray-300">
                <thead class="bg-gray-50">
                  <tr>
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">
                      課程
                    </th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                      講師
                    </th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                      狀態
                    </th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                      價格
                    </th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                      時長
                    </th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                      <span class="sr-only">操作</span>
                    </th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                  <tr v-for="course in courses" :key="course.id" :class="{ 'bg-gray-50': course.deleted_at }">
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
                      <span
                        class="inline-flex rounded-full px-2 text-xs font-semibold leading-5"
                        :class="statusBadge(course.status).class"
                      >
                        {{ statusBadge(course.status).text }}
                      </span>
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
                      <div class="flex items-center justify-end space-x-2">
                        <Link
                          :href="`/admin/courses/${course.id}/edit`"
                          class="text-indigo-600 hover:text-indigo-900"
                        >
                          編輯
                        </Link>
                        <Link
                          :href="`/admin/courses/${course.id}/chapters`"
                          class="text-green-600 hover:text-green-900"
                        >
                          章節
                        </Link>
                        <Link
                          :href="`/admin/courses/${course.id}/images`"
                          class="text-blue-600 hover:text-blue-900"
                        >
                          相簿
                        </Link>
                        <button
                          type="button"
                          class="text-red-600 hover:text-red-900"
                          @click="confirmDelete(course)"
                        >
                          刪除
                        </button>
                      </div>
                    </td>
                  </tr>
                  <tr v-if="courses.length === 0">
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                      尚無課程，點擊「新增課程」開始建立。
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
