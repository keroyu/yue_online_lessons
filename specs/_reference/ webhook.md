啟用 portaly 的 webhook


有人結帳成功後，即會發送POST通知到指定網址，下為傳遞的JSON資料範例

```json
{
  "data": {
      "amount": 312,
      "commissionAmount": 0,
      "couponCode": "ASF12",
      "createdAt": "2024-01-31T07:42:32.151Z",
      "currency": "TWD",
      "customerData": {
        "customFields": [
          [
            "自訂題目1",
            "回答A"
          ],
          [
            "自訂題目2",
            "回答B"
          ]
        ],
        "email": "test5@example.com",
        "name": "有折扣碼",
        "phone": "0987654321"
      },
      "discount": 188,
      "feeAmount": 19,
      "id": "zG143k1VNVULZxnvz0ee",
      "netTotal": 293,
      "paymentMethod": "tappay",
      "productId": "3MAwq6SFZx6jPUOPnxKH",
      "systemCommissionAmount": 0,
      "taxFeeAmount": 0
  },
  "event": "paid",
  "timestamp": "2024-01-31T07:42:32.151Z"
}
```

**資料欄位說明**

| data      | 結帳資料([下方詳述](https://www.notion.so/Portaly-Webhook-42514a68630346dca528d5b5bc42df3f?pvs=21)) |
| --------- | ------------------------------------------------------------------------------------------- |
| event     | 事件別 ("paid" or "refund")                                                                    |
| timestamp | 時間戳記 (ISO 國際標準時間)                                                                           |

**結帳資料說明**

| amount                 | 結帳金額                                                                                         |
| ---------------------- | -------------------------------------------------------------------------------------------- |
| commissionAmount       | 聯賣費用                                                                                         |
| couponCode             | 折扣碼 (若無則顯示空字串)                                                                               |
| currency               | 交易幣別                                                                                         |
| createdAt              | 訂單建立時間 (ISO 國際標準時間)                                                                          |
| customerData           | 客戶資料 ([下方詳述](https://www.notion.so/Portaly-Webhook-42514a68630346dca528d5b5bc42df3f?pvs=21)) |
| discount               | 折扣金額 (若無則顯示0)                                                                                |
| feeAmount              | 系統抽成                                                                                         |
| id                     | 訂單編號                                                                                         |
| netTotal               | 淨營收 (結帳金額 - 系統抽成 - 發票費用 - 聯賣費用 - 聯賣服務費)                                                      |
| paymentMethod          | 結帳方式 (paypal 服務費會較高)                                                                         |
| productId              | 商品專案ID                                                                                       |
| systemCommissionAmount | 聯賣服務費                                                                                        |
| taxFeeAmount           | 發票費用                                                                                         |

**客戶資料說明**

| customFields | 自訂問題的回覆，回覆格式為陣列`['題目', '回答]` |
| ------------ | ---------------------------- |
| email        | 用戶 E-mail                    |
| name         | 用戶姓名                         |
| phone        | 用戶電話 (若無則顯示空子串)              |

### 建議安全性機制 (可選用)

webhook 傳遞會附加上`X-Portaly-Signature` Header，可以用該 Header 來驗證是否是 Portaly 傳送的通知。

驗證方式為，將「結帳資料」(即 data 這個JSON物件) 轉成 JSON 字串後，搭配 Webhook 跳窗內的加密金鑰，用 HMAC-SHA256 演算法，得到 hex 字串，若與`X-Portaly-Signature`相等，那就可以確定是 Portaly 傳送的通知

範例：若結帳資料為 `{"test":123}` ，加密金鑰為 `abcdef0123`，則可得到 hex 字串為 `c6dddde7ffbf0c651277f40b52cc8a07d80493982eaa6a10b7ab30bd6d9d4fe7`

範例程式碼(Node.js)：

```jsx
const crypto = require('crypto')

const secret = 'abcdef0123'
const data = '{"test":123}'
const signature = crypto
  .createHmac("sha256", secret)
  .update(data)
  .digest("hex")
// signature === 'c6dddde7ffbf0c651277f40b52cc8a07d80493982eaa6a10b7ab30bd6d9d4fe7'
```

---

## 系統處理邏輯

### 不相關產品的處理 (2026-01-31 更新)

Portaly 可能將所有產品的 webhook 發送到同一端點。當收到的 `productId` 不對應到資料庫中任何課程時：

- **靜默忽略**：不記錄 ERROR 日誌，避免日誌噪音
- **不建立用戶**：避免為不相關產品的購買者建立無意義的帳號
- **回傳 200**：告知 Portaly 請求已處理，避免重試

### 相關課程的處理

當 `productId` 對應到資料庫中的課程時：

1. 驗證 `X-Portaly-Signature` 簽章
2. 取得或建立用戶帳號（根據 `customerData.email`）
3. 建立購買紀錄（檢查冪等性，避免重複建立）
4. 回傳 200 成功
