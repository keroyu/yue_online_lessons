<script setup>
</script>

<template>
  <footer class="bg-gray-50 border-t border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="flex flex-col md:flex-row md:justify-between md:items-center">
        <div class="text-center md:text-left mb-4 md:mb-0">
          <p class="text-gray-600 text-sm">
            &copy; {{ new Date().getFullYear() }} 經營者時間銀行. All rights reserved.
          </p>
        </div>
        <div class="flex justify-center md:justify-end space-x-6">
          <a href="#" class="text-gray-400 hover:text-gray-600 text-sm">
            服務條款
          </a>
          <a href="#" class="text-gray-400 hover:text-gray-600 text-sm">
            隱私政策
          </a>
          <a href="#" class="text-gray-400 hover:text-gray-600 text-sm">
            聯絡我們
          </a>
        </div>
      </div>
    </div>
  </footer>
</template>
