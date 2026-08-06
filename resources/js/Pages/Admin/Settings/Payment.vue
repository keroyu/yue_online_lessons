<script setup>
import { useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import HintBox from '@/Components/Admin/HintBox.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  payuni: { type: Object, required: true },
  newebpay: { type: Object, required: true },
  portaly: { type: Object, required: true },
  resend: { type: Object, default: () => ({ webhook_secret: '', webhook_secret_preview: '' }) },
  meta_pixel_id: { type: String, default: '' },
  meta_capi: { type: Object, default: () => ({ access_token: '', access_token_preview: '', test_event_code: '' }) },
  zoom: { type: Object, default: () => ({ account_id: '', client_id: '', client_secret_preview: '', enabled: false }) },
})

const form = useForm({
  payuni_merchant_id: props.payuni.merchant_id,
  payuni_hash_key: '',
  payuni_hash_iv: '',
  newebpay_merchant_id: props.newebpay.merchant_id,
  newebpay_hash_key: '',
  newebpay_hash_iv: '',
  newebpay_env: props.newebpay.env,
  portaly_webhook_key: '',
  resend_webhook_secret: '',
  meta_pixel_id: props.meta_pixel_id,
  meta_capi_access_token: '',
  meta_capi_test_event_code: props.meta_capi.test_event_code,
  zoom_account_id: props.zoom.account_id,
  zoom_client_id: props.zoom.client_id,
  zoom_client_secret: '',
})

// Written out rather than inlined: a literal {{…}} inside a template
// interpolation closes the interpolation early.
const zoomUrlVariable = '{' + '{zoom_join_url}' + '}'

// Read off the browser rather than passed from the server: this is only ever
// shown as copy-paste help, and it is always the host the admin is looking at.
const webhookUrl = `${window.location.origin}/resend/webhook`

const submit = () => {
  form.post('/admin/settings/payment')
}

const labelClasses = 'block text-sm font-medium text-gray-700 mb-1'
const inputClasses = 'w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-brand-teal/30 focus:border-brand-teal text-sm'
const sectionClasses = 'bg-white shadow-sm rounded-lg p-6 space-y-4'
</script>

<template>
  <div class="max-w-2xl mx-auto py-8 px-4 space-y-6">
    <h1 class="text-xl font-bold text-gray-900">API 設定</h1>

    <form @submit.prevent="submit" class="space-y-6">
      <!-- Resend（全站寄信） -->
      <div :class="sectionClasses">
        <h2 class="text-base font-semibold text-gray-800 border-b pb-2">Resend（寄信服務）</h2>

        <p class="text-xs text-gray-500">
          全站每一封信都經由 Resend 寄出 —— 登入驗證碼、預約確認、連鎖序列信、電子報、後台批次信。
          <strong class="text-gray-700">寄信設定沒弄好，會員連登入都收不到驗證碼</strong>，等於整站停擺，
          所以請照下面的順序一次做完再上線。
        </p>

        <HintBox title="一、先驗證寄件網域（沒驗證就完全不能寄信）">
          <ol class="space-y-1 list-decimal list-inside">
            <li>
              註冊
              <a
                href="https://resend.com"
                target="_blank"
                rel="noopener"
                class="text-brand-teal underline cursor-pointer hover:opacity-70"
              >resend.com</a>
              後進入 Domains → Add Domain，填入你要用來寄信的網域
            </li>
            <li>
              照畫面給的值，到網域的 DNS 服務商新增三筆記錄：
              <code class="text-gray-700">TXT</code>（SPF，宣告誰有權代你寄信）、
              <code class="text-gray-700">TXT</code>（DKIM，signature 用的公鑰）、
              <code class="text-gray-700">MX</code>
            </li>
            <li>
              <strong>MX 那筆不要省。</strong>它是收件方把「退信」與「垃圾信投訴」回報給你的通道 ——
              少了它，下方的 Webhook 收不到任何事件，後台名單也就永遠看不出誰的信箱已經死了
            </li>
            <li>
              等狀態變成 <code class="text-gray-700">verified</code> 才算完成。Resend 會持續偵測
              <strong>最多 72 小時</strong>，逾時轉為 <code class="text-gray-700">failed</code>，
              得修好 DNS 再重新驗證一次
            </li>
          </ol>
          <p class="mt-2 text-gray-500">DMARC 是選用的，但建議一併設定，對進收件匣（而非垃圾郵件匣）有幫助。</p>
        </HintBox>

        <HintBox title="二、以下三項設在伺服器的 .env，改動需工程師重新部署">
          <dl class="space-y-2">
            <div>
              <dt><code class="text-gray-700 font-semibold">RESEND_API_KEY</code></dt>
              <dd class="mt-0.5">
                Resend 後台 → API Keys → Create API Key，權限選
                <code class="text-gray-700">Sending access</code>（本站只需要寄信；要更保險可再綁定剛才那個網域）。
                <strong>金鑰只會顯示這一次</strong>，關掉視窗就再也看不到，沒存下來只能刪掉重建。
              </dd>
            </div>
            <div>
              <dt><code class="text-gray-700 font-semibold">MAIL_FROM_ADDRESS</code></dt>
              <dd class="mt-0.5">
                收件人看到的寄件位址，例如 <code class="text-gray-700">noreply@你的網域</code>。
                <strong>必須是上一步已驗證網域底下的位址</strong> —— 填了沒驗證過的網域，Resend 會拒收，
                全站一封信都寄不出去。
              </dd>
            </div>
            <div>
              <dt><code class="text-gray-700 font-semibold">MAIL_FROM_NAME</code></dt>
              <dd class="mt-0.5">收件人在信件列表看到的寄件人顯示名稱，通常就是你的品牌名。</dd>
            </div>
          </dl>
        </HintBox>

        <HintBox title="三、最後建立 Webhook（下方欄位就是這一步的產物）">
          <ol class="space-y-1 list-decimal list-inside">
            <li>Resend 後台 → Webhooks → Add Webhook</li>
            <li>
              Endpoint URL 填
              <code class="text-gray-700 break-all">{{ webhookUrl }}</code>
            </li>
            <li>
              事件勾選 <code class="text-gray-700">email.bounced</code> 與
              <code class="text-gray-700">email.complained</code> 兩項即可
            </li>
            <li>建立後點進該 Webhook 詳情頁，複製 Signing Secret（<code class="text-gray-700">whsec_</code> 開頭）貼到下方欄位</li>
          </ol>
          <p class="mt-2 text-gray-500">
            <strong class="text-gray-700">這一步在做什麼</strong>：硬退信（信箱不存在）與垃圾信投訴發生時，
            Resend 會通知本站，系統把該 email 記進封鎖名單，後台的名單頁就會標示「已退信／已投訴」，
            序列信也不再寄給他。Resend 自己本來就會跳過這些地址，所以這一步不影響寄件信譽，
            純粹是讓你在後台看得見、並且不再把死名單算進開信率。
          </p>
        </HintBox>

        <div>
          <label :class="labelClasses">Webhook 簽署金鑰</label>
          <input type="password" v-model="form.resend_webhook_secret" :class="inputClasses" :placeholder="resend.webhook_secret_preview || '尚未設定'" autocomplete="new-password" />
          <p class="mt-1 text-xs text-gray-500">留空表示保留現有金鑰</p>
          <p class="mt-1 text-xs text-red-600 font-medium">⚠️ 未設定時此端點形同無認證，任何人都能偽造退信/投訴事件把任意 email 加進封鎖名單；請盡快設定</p>
          <p v-if="form.errors.resend_webhook_secret" class="mt-1 text-sm text-red-600">{{ form.errors.resend_webhook_secret }}</p>
        </div>
      </div>

      <!-- Zoom（客製服務諮詢會議自動建立） -->
      <div :class="sectionClasses">
        <div class="flex items-center justify-between border-b pb-2">
          <h2 class="text-base font-semibold text-gray-800">Zoom 會議</h2>
          <span
            class="text-xs px-2 py-0.5 rounded-full"
            :class="zoom.enabled ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
          >
            {{ zoom.enabled ? '已啟用' : '未啟用' }}
          </span>
        </div>

        <p class="text-xs text-gray-500">
          預約完成 Email 確認後，系統自動建立該時段的 Zoom 會議，並把連結放進「客製服務預約確認」信的
          <code class="text-gray-600">{{ zoomUrlVariable }}</code> 變數。
          三個欄位任一留空即停用，預約流程照常運作、確認信不含會議連結。
        </p>

        <HintBox title="憑證怎麼拿">
          <ol class="space-y-1 list-decimal list-inside">
            <li>
              前往
              <a
                href="https://marketplace.zoom.us"
                target="_blank"
                rel="noopener"
                class="text-brand-teal underline cursor-pointer hover:opacity-70"
              >marketplace.zoom.us</a>
            </li>
            <li>Develop → Build App → <strong>Server-to-Server OAuth</strong> → Create</li>
            <li>
              <strong>Scopes 分頁要勾滿下面三項</strong>，只勾第一項的話「建立會議」會成功，
              但之後在週曆上改期或取消預約會失敗：
              <ul class="mt-1 space-y-1 list-disc list-inside pl-3">
                <li>
                  <code class="text-gray-700">meeting:write:meeting:admin</code>
                  —— 預約確認後建立會議
                </li>
                <li>
                  <code class="text-gray-700">meeting:update:meeting:admin</code>
                  —— 後台改期時同步更新 Zoom 時間（沿用原連結，對方日曆上的連結不會失效）
                </li>
                <li>
                  <code class="text-gray-700">meeting:delete:meeting:admin</code>
                  —— 後台取消預約時一併撤掉會議
                </li>
              </ul>
              <p class="mt-1 text-gray-500">
                舊版介面（classic scopes）只會看到一個
                <code class="text-gray-700">meeting:write:admin</code>，勾它即可，它本身就涵蓋這三種操作。
                本站不需要任何讀取類權限，不用多勾。
              </p>
            </li>
            <li>
              <strong>最後到 Activation 分頁按「Activate your app」</strong> —— 新建的 app 預設是停用的，
              沒啟用會拿不到 token（Zoom 回
              <code class="text-gray-700">The app has been disabled by the developer</code>）。
              按鈕若是灰的，代表上一步的 Scopes 還沒勾齊
            </li>
            <li>
              以上都完成後，回到 App Credentials 分頁複製
              Account ID / Client ID / Client Secret 貼到下方欄位
            </li>
          </ol>
          <p class="mt-2 text-gray-500">須為付費方案：免費方案有 40 分鐘上限，45 分鐘場（使用預約優惠碼）會被截斷。</p>
          <p class="mt-2 text-gray-500">
            <strong class="text-gray-700">關於顧問主持會議</strong>：時段指派給銷售顧問後，系統會嘗試把會議建在該顧問的
            Zoom 帳號下，前提是<strong>他在你的 Zoom 帳號底下有自己的席次</strong>（Zoom 按席次計費）。
            還沒有席次時會自動改建在擁有者帳號下，預約流程不受影響 ——
            但顧問不會是主持人，不能錄影、結束會議或管理等候室。
          </p>
        </HintBox>

        <div>
          <label :class="labelClasses">Account ID</label>
          <input type="text" v-model="form.zoom_account_id" :class="inputClasses" placeholder="尚未設定" />
          <p v-if="form.errors.zoom_account_id" class="mt-1 text-sm text-red-600">{{ form.errors.zoom_account_id }}</p>
        </div>

        <div>
          <label :class="labelClasses">Client ID</label>
          <input type="text" v-model="form.zoom_client_id" :class="inputClasses" placeholder="尚未設定" />
          <p v-if="form.errors.zoom_client_id" class="mt-1 text-sm text-red-600">{{ form.errors.zoom_client_id }}</p>
        </div>

        <div>
          <label :class="labelClasses">Client Secret</label>
          <input type="password" v-model="form.zoom_client_secret" :class="inputClasses" :placeholder="zoom.client_secret_preview || '尚未設定'" autocomplete="new-password" />
          <p class="mt-1 text-xs text-gray-500">留空 = 不變更</p>
          <p v-if="form.errors.zoom_client_secret" class="mt-1 text-sm text-red-600">{{ form.errors.zoom_client_secret }}</p>
        </div>
      </div>

      <!-- PayUni -->
      <div :class="sectionClasses">
        <h2 class="text-base font-semibold text-gray-800 border-b pb-2">統一金流（PayUni）</h2>

        <div>
          <label :class="labelClasses">商店代號（MerchantID）</label>
          <input type="text" v-model="form.payuni_merchant_id" :class="inputClasses" placeholder="M00001" />
          <p v-if="form.errors.payuni_merchant_id" class="mt-1 text-sm text-red-600">{{ form.errors.payuni_merchant_id }}</p>
        </div>

        <div>
          <label :class="labelClasses">HashKey</label>
          <input type="password" v-model="form.payuni_hash_key" :class="inputClasses" :placeholder="payuni.hash_key_preview || '尚未設定'" autocomplete="new-password" />
          <p v-if="form.errors.payuni_hash_key" class="mt-1 text-sm text-red-600">{{ form.errors.payuni_hash_key }}</p>
        </div>

        <div>
          <label :class="labelClasses">HashIV</label>
          <input type="password" v-model="form.payuni_hash_iv" :class="inputClasses" :placeholder="payuni.hash_iv_preview || '尚未設定'" autocomplete="new-password" />
          <p v-if="form.errors.payuni_hash_iv" class="mt-1 text-sm text-red-600">{{ form.errors.payuni_hash_iv }}</p>
        </div>
      </div>

      <!-- NewebPay -->
      <div :class="sectionClasses">
        <h2 class="text-base font-semibold text-gray-800 border-b pb-2">藍新金流（NewebPay）</h2>

        <div>
          <label :class="labelClasses">商店代號（MerchantID）</label>
          <input type="text" v-model="form.newebpay_merchant_id" :class="inputClasses" placeholder="MS1234567890" />
          <p v-if="form.errors.newebpay_merchant_id" class="mt-1 text-sm text-red-600">{{ form.errors.newebpay_merchant_id }}</p>
        </div>

        <div>
          <label :class="labelClasses">HashKey</label>
          <input type="password" v-model="form.newebpay_hash_key" :class="inputClasses" :placeholder="newebpay.hash_key_preview || '尚未設定'" autocomplete="new-password" />
          <p v-if="form.errors.newebpay_hash_key" class="mt-1 text-sm text-red-600">{{ form.errors.newebpay_hash_key }}</p>
        </div>

        <div>
          <label :class="labelClasses">HashIV</label>
          <input type="password" v-model="form.newebpay_hash_iv" :class="inputClasses" :placeholder="newebpay.hash_iv_preview || '尚未設定'" autocomplete="new-password" />
          <p v-if="form.errors.newebpay_hash_iv" class="mt-1 text-sm text-red-600">{{ form.errors.newebpay_hash_iv }}</p>
        </div>

        <div>
          <label :class="labelClasses">環境</label>
          <select v-model="form.newebpay_env" :class="inputClasses">
            <option value="sandbox">Sandbox（測試）</option>
            <option value="production">Production（正式）</option>
          </select>
          <p v-if="form.errors.newebpay_env" class="mt-1 text-sm text-red-600">{{ form.errors.newebpay_env }}</p>
        </div>
      </div>

      <!-- Portaly -->
      <div :class="sectionClasses">
        <h2 class="text-base font-semibold text-gray-800 border-b pb-2">Portaly（Webhook）</h2>

        <div>
          <label :class="labelClasses">Webhook 金鑰</label>
          <input type="password" v-model="form.portaly_webhook_key" :class="inputClasses" :placeholder="portaly.webhook_key_preview || '尚未設定'" autocomplete="new-password" />
          <p class="mt-1 text-xs text-gray-500">留空表示保留現有金鑰</p>
          <p v-if="form.errors.portaly_webhook_key" class="mt-1 text-sm text-red-600">{{ form.errors.portaly_webhook_key }}</p>
        </div>
      </div>

      <!-- Meta Pixel -->
      <div :class="sectionClasses">
        <h2 class="text-base font-semibold text-gray-800 border-b pb-2">Meta Pixel</h2>

        <div>
          <label :class="labelClasses">Pixel ID</label>
          <input type="text" v-model="form.meta_pixel_id" :class="inputClasses" placeholder="1287511383482442" />
          <p class="mt-1 text-xs text-gray-500">留空表示停用 Meta Pixel（頁面不輸出任何 fbq 代碼）</p>
          <p v-if="form.errors.meta_pixel_id" class="mt-1 text-sm text-red-600">{{ form.errors.meta_pixel_id }}</p>
        </div>

        <div>
          <label :class="labelClasses">Conversions API Access Token</label>
          <input type="password" v-model="form.meta_capi_access_token" :class="inputClasses" :placeholder="meta_capi.access_token_preview || '尚未設定'" autocomplete="new-password" />
          <p class="mt-1 text-xs text-gray-500">在 Meta 事件管理工具 → 設定 → Conversions API 產生；留空 = 不變更。未設定時僅瀏覽器 Pixel 追蹤、不送伺服器端事件</p>
          <p v-if="form.errors.meta_capi_access_token" class="mt-1 text-sm text-red-600">{{ form.errors.meta_capi_access_token }}</p>
        </div>

        <div>
          <label :class="labelClasses">測試事件代碼（test_event_code）</label>
          <input type="text" v-model="form.meta_capi_test_event_code" :class="inputClasses" placeholder="TEST12345" />
          <p class="mt-1 text-xs text-gray-500">填入後伺服器端事件會出現在事件管理工具的「測試事件」頁籤；驗證完請清空，否則事件不會進正式數據</p>
          <p v-if="form.errors.meta_capi_test_event_code" class="mt-1 text-sm text-red-600">{{ form.errors.meta_capi_test_event_code }}</p>
        </div>
      </div>

      <div class="flex justify-end">
        <button
          type="submit"
          :disabled="form.processing"
          class="px-6 py-2 bg-brand-teal text-white font-medium rounded-lg hover:bg-brand-teal/80 disabled:opacity-50 transition-colors"
        >
          {{ form.processing ? '儲存中...' : '儲存設定' }}
        </button>
      </div>
    </form>
  </div>
</template>
