<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import CourseForm from '@/Components/Admin/CourseForm.vue'
</script>

<template>
  <AdminLayout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="mb-8">
        <h1 class="text-2xl font-semibold text-gray-900">新增課程</h1>
        <p class="mt-2 text-sm text-gray-700">
          填寫課程基本資訊，建立後預設為「草稿」狀態。
        </p>
      </div>

      <CourseForm
        submit-url="/admin/courses"
        method="post"
      />
    </div>
  </AdminLayout>
</template>
