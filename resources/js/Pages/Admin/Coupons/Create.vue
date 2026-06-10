<script setup>
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import CouponForm from '@/Components/Admin/CouponForm.vue'

defineOptions({ layout: AdminLayout })

defineProps({
  courses: { type: Array, default: () => [] },
})
</script>

<template>
  <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-2xl mx-auto">
    <div class="mb-6">
      <Link href="/admin/coupons" class="text-sm text-gray-500 hover:text-gray-700">&larr; 返回折扣碼列表</Link>
      <h1 class="text-2xl font-bold text-gray-900 mt-2">新增折扣碼</h1>
    </div>
    <CouponForm :courses="courses" />
  </div>
</template>
