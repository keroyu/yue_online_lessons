<script setup>
import { Link, router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import TransactionRefundModal from '@/Components/Admin/TransactionRefundModal.vue'
import { ref, computed } from 'vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  transaction: {
    type: Object,
    required: true,
  },
  order_info: {
    type: Object,
    default: null,
  },
})

const gatewayLabel = (gateway) => {
  if (gateway === 'payuni')   return '統一金流（PayUni）'
  if (gateway === 'newebpay') return '藍新金流（NewebPay）'
  return gateway || '-'
}

const page = usePage()
const flash = computed(() => page.props.flash)

// Refund modal
const showRefundModal = ref(false)

const formatAmount = (currency, amount) => {
  if (amount === null || amount === undefined) return '-'
  return `${currency} ${Number(amount).toFixed(2)}`
}

const formatDateTime = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleString('zh-TW', { timeZone: 'Asia/Taipei' })
}

const statusLabel = (status, type) => {
  if (status === 'refunded') return type === 'paid' ? '已退款' : '已撤銷'
  return type === 'paid' ? '已付款' : '有效'
}

const statusClass = (status, type) => {
  if (status === 'refunded') {
    return 'inline-flex px-2.5 py-0.5 text-sm font-medium rounded-full bg-red-100 text-red-800'
  }
  return type === 'paid'
    ? 'inline-flex px-2.5 py-0.5 text-sm font-medium rounded-full bg-green-100 text-green-800'
    : 'inline-flex px-2.5 py-0.5 text-sm font-medium rounded-full bg-blue-100 text-blue-800'
}
</script>

<template>
  <div class="px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="mb-6">
      <Link
        href="/admin/transactions"
        class="text-sm text-indigo-600 hover:text-indigo-900 flex items-center gap-1"
      >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        返回交易列表
      </Link>
      <h1 class="mt-3 text-2xl font-semibold text-gray-900">交易詳情 #{{ transaction.id }}</h1>
    </div>

    <!-- Flash banners -->
    <div v-if="flash?.success" class="mb-4 bg-green-50 border border-green-200 rounded-lg p-3 flex items-center justify-between">
      <span class="text-sm text-green-800">{{ flash.success }}</span>
      <button type="button" @click="page.props.flash = {}" class="text-green-600 hover:text-green-800">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
      </button>
    </div>
    <div v-if="flash?.error" class="mb-4 bg-red-50 border border-red-200 rounded-lg p-3 flex items-center justify-between">
      <span class="text-sm text-red-800">{{ flash.error }}</span>
      <button type="button" @click="page.props.flash = {}" class="text-red-600 hover:text-red-800">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
      </button>
    </div>

    <!-- Detail card -->
    <div class="bg-white shadow sm:rounded-lg">
      <!-- Status + actions bar -->
      <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-wrap gap-3">
        <div class="flex items-center gap-3">
          <span :class="statusClass(transaction.status, transaction.type)">{{ statusLabel(transaction.status, transaction.type) }}</span>
          <span class="text-sm text-gray-500">{{ transaction.type_label }}</span>
        </div>
        <button
          v-if="transaction.status === 'paid'"
          type="button"
          @click="showRefundModal = true"
          class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 transition-colors"
        >
          標記退款
        </button>
      </div>

      <!-- Fields grid -->
      <div class="px-6 py-6">
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-5">
          <!-- Order ID -->
          <div>
            <dt class="text-sm font-medium text-gray-500">訂單 ID</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ transaction.id }}</dd>
          </div>

          <!-- Portaly Order ID -->
          <div>
            <dt class="text-sm font-medium text-gray-500">Portaly 訂單編號</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ transaction.portaly_order_id || '-' }}</dd>
          </div>

          <!-- Legacy PayUni trade no (single-course direct buy, pre-cart-checkout) -->
          <div v-if="transaction.payuni_trade_no">
            <dt class="text-sm font-medium text-gray-500">PayUni 交易序號</dt>
            <dd class="mt-1 text-sm text-gray-900 font-mono">{{ transaction.payuni_trade_no }}</dd>
          </div>

          <!-- Buyer info -->
          <div>
            <dt class="text-sm font-medium text-gray-500">購買者</dt>
            <dd class="mt-1 text-sm text-gray-900">
              <template v-if="transaction.user">
                <Link
                  :href="`/admin/members?highlight=${transaction.user.id}`"
                  class="text-indigo-600 hover:text-indigo-900"
                >
                  {{ transaction.user.real_name || transaction.user.nickname || transaction.user.email }}
                </Link>
                <div class="text-xs text-gray-500">{{ transaction.user.email }}</div>
              </template>
              <template v-else>
                <span class="text-gray-400">（會員已刪除）</span>
                <div class="text-xs text-gray-500">{{ transaction.buyer_email || '-' }}</div>
              </template>
            </dd>
          </div>

          <!-- Buyer email (raw) -->
          <div>
            <dt class="text-sm font-medium text-gray-500">購買者 Email（記錄值）</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ transaction.buyer_email || '-' }}</dd>
          </div>

          <!-- Course -->
          <div>
            <dt class="text-sm font-medium text-gray-500">課程</dt>
            <dd class="mt-1 text-sm text-gray-900">
              <template v-if="transaction.course">
                <Link
                  :href="`/admin/courses/${transaction.course.id}/edit`"
                  class="text-indigo-600 hover:text-indigo-900"
                >
                  {{ transaction.course.name }}
                </Link>
              </template>
              <template v-else>
                <span class="text-gray-400">（課程已刪除）</span>
              </template>
            </dd>
          </div>

          <!-- Amount -->
          <div>
            <dt class="text-sm font-medium text-gray-500">金額</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ formatAmount(transaction.currency, transaction.amount) }}</dd>
          </div>

          <!-- Discount -->
          <div>
            <dt class="text-sm font-medium text-gray-500">折扣金額</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ formatAmount(transaction.currency, transaction.discount_amount) }}</dd>
          </div>

          <!-- Coupon code -->
          <div>
            <dt class="text-sm font-medium text-gray-500">優惠碼</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ transaction.coupon_code || '-' }}</dd>
          </div>

          <!-- Status -->
          <div>
            <dt class="text-sm font-medium text-gray-500">狀態</dt>
            <dd class="mt-1"><span :class="statusClass(transaction.status)">{{ statusLabel(transaction.status) }}</span></dd>
          </div>

          <!-- Source -->
          <div>
            <dt class="text-sm font-medium text-gray-500">來源</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ transaction.source || '-' }}</dd>
          </div>

          <!-- Type -->
          <div>
            <dt class="text-sm font-medium text-gray-500">類型</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ transaction.type_label }}</dd>
          </div>

          <!-- Webhook received at -->
          <div>
            <dt class="text-sm font-medium text-gray-500">Webhook 接收時間</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ formatDateTime(transaction.webhook_received_at) }}</dd>
          </div>

          <!-- Created at -->
          <div>
            <dt class="text-sm font-medium text-gray-500">建立時間</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ formatDateTime(transaction.created_at) }}</dd>
          </div>

          <!-- Updated at -->
          <div>
            <dt class="text-sm font-medium text-gray-500">最後更新</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ formatDateTime(transaction.updated_at) }}</dd>
          </div>
        </dl>
      </div>
    </div>

    <!-- Cart order info block (only for PayUni / NewebPay purchases) -->
    <div v-if="order_info?.merchant_order_no" class="mt-6 bg-white shadow sm:rounded-lg">
      <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-base font-semibold text-gray-900">購物車訂單資訊</h2>
      </div>
      <div class="px-6 py-6">
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-5">
          <div>
            <dt class="text-sm font-medium text-gray-500">商店訂單編號</dt>
            <dd class="mt-1 text-sm text-gray-900 font-mono">{{ order_info.merchant_order_no }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">金流交易序號</dt>
            <dd class="mt-1 text-sm text-gray-900 font-mono">{{ order_info.gateway_trade_no || '-' }}</dd>
          </div>
          <div>
            <dt class="text-sm font-medium text-gray-500">金流管道</dt>
            <dd class="mt-1 text-sm text-gray-900">{{ gatewayLabel(order_info.payment_gateway) }}</dd>
          </div>
          <div v-if="order_info.tax_id">
            <dt class="text-sm font-medium text-gray-500">公司統編</dt>
            <dd class="mt-1 text-sm text-gray-900 font-mono">{{ order_info.tax_id }}</dd>
          </div>
        </dl>
      </div>
    </div>
  </div>

  <!-- Refund confirm modal -->
  <TransactionRefundModal
    :show="showRefundModal"
    :transaction="transaction"
    @close="showRefundModal = false"
  />
</template>
