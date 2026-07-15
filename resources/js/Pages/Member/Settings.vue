<script setup>
import AppLayout from '@/Components/Layout/AppLayout.vue'
import { useForm, router } from '@inertiajs/vue3'
import { watch } from 'vue'
import { platformIcons, platformLabels, memberPlatforms, detectPlatform } from '@/lib/socialPlatforms'

defineOptions({
  layout: AppLayout,
})

const props = defineProps({
  user: {
    type: Object,
    required: true,
  },
  orders: {
    type: Array,
    required: true,
  },
  completions: {
    type: Array,
    default: () => [],
  },
  socialLinks: {
    type: Array,
    default: () => [],
  },
})

const MAX_SOCIAL_LINKS = 5

const linkForm = useForm({
  platform: 'blog',
  url: '',
})

// Auto-detect platform from the pasted URL; the select stays editable to override.
watch(() => linkForm.url, (url) => {
  linkForm.platform = detectPlatform(url)
})

const submitLink = () => {
  linkForm.post('/member/social-links', {
    preserveScroll: true,
    onSuccess: () => linkForm.reset(),
  })
}

const deleteLink = (id) => {
  router.delete(`/member/social-links/${id}`, { preserveScroll: true })
}

const form = useForm({
  nickname: props.user.nickname || '',
  real_name: props.user.real_name || '',
  phone: props.user.phone || '',
  birth_date: props.user.birth_date || '',
})

const submit = () => {
  form.put('/member/settings')
}

const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('zh-TW', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}

const formatAmount = (amount, currency) => {
  return new Intl.NumberFormat('zh-TW', {
    style: 'currency',
    currency: currency || 'TWD',
    minimumFractionDigits: 0,
  }).format(amount)
}

const getStatusText = (order) => {
  if (order.type === 'system_assigned') return '系統指派'
  if (order.type === 'gift') return '贈送'
  if (order.type === 'lead_conversion') return '顧問轉換'
  return order.status === 'paid' ? '已付款' : '已退款'
}

const getStatusClass = (order) => {
  if (order.type === 'system_assigned') return 'bg-gray-100 text-gray-800'
  if (order.type === 'gift') return 'bg-purple-100 text-purple-800'
  if (order.type === 'lead_conversion') return 'bg-teal-100 text-teal-800'
  return order.status === 'paid'
    ? 'bg-green-100 text-green-800'
    : 'bg-red-100 text-red-800'
}
</script>

<template>
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl sm:text-3xl font-bold text-brand-navy mb-8">
      帳號設定
    </h1>

    <!-- Profile Form -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
      <h2 class="text-lg font-semibold text-brand-navy mb-4">
        個人資料
      </h2>

      <form @submit.prevent="submit" class="space-y-4">
        <!-- Email (read-only) -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Email
          </label>
          <input
            type="email"
            :value="user.email"
            disabled
            class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500 cursor-not-allowed"
          />
          <p class="mt-1 text-xs text-gray-500">Email 無法修改</p>
        </div>

        <!-- Nickname -->
        <div>
          <label for="nickname" class="block text-sm font-medium text-gray-700 mb-1">
            暱稱
          </label>
          <input
            id="nickname"
            v-model="form.nickname"
            type="text"
            maxlength="100"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-teal/50 focus:border-brand-teal"
            :class="{ 'border-red-500': form.errors.nickname }"
          />
          <p v-if="form.errors.nickname" class="mt-1 text-sm text-red-600">
            {{ form.errors.nickname }}
          </p>
        </div>

        <!-- Real Name -->
        <div>
          <label for="real_name" class="block text-sm font-medium text-gray-700 mb-1">
            真實姓名
          </label>
          <input
            id="real_name"
            v-model="form.real_name"
            type="text"
            maxlength="100"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-teal/50 focus:border-brand-teal"
            :class="{ 'border-red-500': form.errors.real_name }"
          />
          <p v-if="form.errors.real_name" class="mt-1 text-sm text-red-600">
            {{ form.errors.real_name }}
          </p>
        </div>

        <!-- Phone -->
        <div>
          <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">
            電話
          </label>
          <input
            id="phone"
            v-model="form.phone"
            type="tel"
            maxlength="20"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-teal/50 focus:border-brand-teal"
            :class="{ 'border-red-500': form.errors.phone }"
          />
          <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600">
            {{ form.errors.phone }}
          </p>
        </div>

        <!-- Birth Date -->
        <div>
          <label for="birth_date" class="block text-sm font-medium text-gray-700 mb-1">
            出生年月日
          </label>
          <input
            id="birth_date"
            v-model="form.birth_date"
            type="date"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-teal/50 focus:border-brand-teal"
            :class="{ 'border-red-500': form.errors.birth_date }"
          />
          <p v-if="form.errors.birth_date" class="mt-1 text-sm text-red-600">
            {{ form.errors.birth_date }}
          </p>
        </div>

        <!-- Submit Button -->
        <div class="pt-4">
          <button
            type="submit"
            :disabled="form.processing"
            class="w-full sm:w-auto px-6 py-2 bg-brand-teal text-white font-medium rounded-lg hover:bg-brand-teal/80 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="form.processing">儲存中...</span>
            <span v-else>儲存變更</span>
          </button>
        </div>
      </form>
    </div>

    <!-- Social Links -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
      <h2 class="text-lg font-semibold text-brand-navy mb-1">
        社群連結
      </h2>
      <p class="text-xs text-gray-500 mb-4">
        分享你的社群帳號（最多 {{ MAX_SOCIAL_LINKS }} 個），讓老師批改作業時更了解你的經營情況。僅老師與管理員可見。
      </p>

      <!-- Existing links -->
      <ul v-if="socialLinks.length > 0" class="space-y-2 mb-4">
        <li
          v-for="link in socialLinks"
          :key="link.id"
          class="flex items-center gap-3 px-3 py-2 border border-gray-200 rounded-lg"
        >
          <span class="w-5 h-5 shrink-0 text-gray-600" v-html="platformIcons[link.platform] ?? ''"></span>
          <span class="text-xs font-medium text-gray-500 w-20 shrink-0">{{ platformLabels[link.platform] ?? link.platform }}</span>
          <a
            :href="link.url"
            target="_blank"
            rel="noopener noreferrer"
            class="text-sm text-brand-teal hover:underline truncate min-w-0 flex-1"
          >{{ link.url }}</a>
          <button
            type="button"
            @click="deleteLink(link.id)"
            class="shrink-0 p-1 text-gray-400 hover:text-red-600 transition-colors"
            aria-label="刪除連結"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </li>
      </ul>

      <!-- Add form -->
      <form
        v-if="socialLinks.length < MAX_SOCIAL_LINKS"
        @submit.prevent="submitLink"
        class="flex flex-col sm:flex-row gap-2"
      >
        <div class="flex items-center gap-2 flex-1 min-w-0">
          <span class="w-5 h-5 shrink-0 text-gray-500" v-html="platformIcons[linkForm.platform] ?? ''"></span>
          <input
            v-model="linkForm.url"
            type="url"
            placeholder="貼上網址（https://...）"
            maxlength="500"
            class="flex-1 min-w-0 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-teal/50 focus:border-brand-teal text-sm"
            :class="{ 'border-red-500': linkForm.errors.url || linkForm.errors.platform }"
          />
        </div>
        <div class="flex gap-2">
          <select
            v-model="linkForm.platform"
            class="px-3 py-2 border border-gray-300 rounded-lg text-sm cursor-pointer hover:border-brand-teal focus:ring-2 focus:ring-brand-teal/50 focus:border-brand-teal"
          >
            <option v-for="p in memberPlatforms" :key="p" :value="p">{{ platformLabels[p] }}</option>
          </select>
          <button
            type="submit"
            :disabled="linkForm.processing || !linkForm.url"
            class="px-4 py-2 bg-brand-teal text-white text-sm font-medium rounded-lg hover:bg-brand-teal/80 transition-colors disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap"
          >
            新增
          </button>
        </div>
      </form>
      <p v-else class="text-sm text-gray-500">
        已達 {{ MAX_SOCIAL_LINKS }} 個上限，刪除後才能再新增。
      </p>
      <p v-if="linkForm.errors.url" class="mt-2 text-sm text-red-600">{{ linkForm.errors.url }}</p>
      <p v-if="linkForm.errors.platform" class="mt-2 text-sm text-red-600">{{ linkForm.errors.platform }}</p>
    </div>

    <!-- Order History -->
    <div class="bg-white rounded-lg shadow-md p-6">
      <h2 class="text-lg font-semibold text-brand-navy mb-4">
        訂單紀錄
      </h2>

      <!-- Orders Table -->
      <div v-if="orders.length > 0" class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-200">
              <th class="text-left py-3 px-2 text-sm font-medium text-gray-500">日期</th>
              <th class="text-left py-3 px-2 text-sm font-medium text-gray-500">課程</th>
              <th class="text-right py-3 px-2 text-sm font-medium text-gray-500">金額</th>
              <th class="text-center py-3 px-2 text-sm font-medium text-gray-500">狀態</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="order in orders"
              :key="order.id"
              class="border-b border-gray-100 hover:bg-gray-50"
            >
              <td class="py-3 px-2 text-sm text-gray-600 whitespace-nowrap">
                {{ formatDate(order.created_at) }}
              </td>
              <td class="py-3 px-2 text-sm text-gray-900">
                {{ order.course_name }}
              </td>
              <td class="py-3 px-2 text-sm text-gray-900 text-right whitespace-nowrap">
                {{ formatAmount(order.amount, order.currency) }}
              </td>
              <td class="py-3 px-2 text-center">
                <span
                  class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                  :class="getStatusClass(order)"
                >
                  {{ getStatusText(order) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-8">
        <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-3">
          <svg
            class="w-8 h-8 text-gray-400"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
            />
          </svg>
        </div>
        <p class="text-gray-500">尚無訂單紀錄</p>
      </div>
    </div>

    <!-- Points & Homework Completions -->
    <div class="bg-white rounded-lg shadow-md p-6 mt-8">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-brand-navy">積分與作業完成記錄</h2>
        <span class="text-2xl font-bold text-green-600">{{ user.points ?? 0 }} 分</span>
      </div>

      <div v-if="completions.length > 0" class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-200">
              <th class="text-left py-3 px-2 text-sm font-medium text-gray-500">完成時間</th>
              <th class="text-left py-3 px-2 text-sm font-medium text-gray-500">課程</th>
              <th class="text-left py-3 px-2 text-sm font-medium text-gray-500">小節</th>
              <th class="text-right py-3 px-2 text-sm font-medium text-gray-500">積分</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(c, idx) in completions"
              :key="idx"
              class="border-b border-gray-100 hover:bg-gray-50"
            >
              <td class="py-3 px-2 text-sm text-gray-600 whitespace-nowrap">
                {{ formatDate(c.completed_at) }}
              </td>
              <td class="py-3 px-2 text-sm text-gray-900">{{ c.course_name }}</td>
              <td class="py-3 px-2 text-sm text-gray-700">{{ c.lesson_title }}</td>
              <td class="py-3 px-2 text-sm text-right font-semibold text-green-600">+{{ c.points_awarded }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-else class="text-center py-8 text-gray-500 text-sm">
        尚無完成記錄
      </div>
    </div>
  </div>
</template>
