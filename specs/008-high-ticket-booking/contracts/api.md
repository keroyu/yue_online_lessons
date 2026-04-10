# API Contracts: 客製服務預約系統

**Phase**: 1 — Design
**Updated**: 2026-04-09 - 預約端點改為 JSON API（非 Inertia redirect）；成功/失敗均回傳 JSON
**Updated**: 2026-04-09
**Updated**: 2026-04-09 - 新增 Leads 管理後台路由（US6）；booking 端點新增 Lead 記錄儲存副作用（US5）
**Updated**: 2026-04-10 - subscribe-drip 改為非同步（SubscribeDripLeadJob）；response 欄位 subscribed → dispatched；side effects 移至 Job 內執行

---

## Public Routes

### POST `/course/{course}/book`

**Purpose**: Submit a high-ticket service booking (send confirmation email)
**Controller**: `HighTicketBookingController@store`
**Auth**: None (public)
**Rate limit**: `throttle:5,1`

**Request body**:
```json
{
  "name": "string, required, max:100",
  "email": "string, required, email, max:255"
}
```

**Success** `200 OK`:
```json
{ "success": true }
```

**Error** `422 Unprocessable Entity`:
```json
{ "message": "...", "errors": { "name": [...], "email": [...] } }
```
or
```json
{ "message": "預約確認信模板不存在，請聯絡管理員" }
```

> ⚠️ **Implementation note**: This endpoint returns JSON (not Inertia redirect). The frontend uses `axios.post()` and handles success/error inline without page navigation.

**Side effects**:
- Sends confirmation email to visitor (sync) using `high_ticket_booking_confirmation` template
- Creates a `high_ticket_leads` record (name, email, course_id, status='pending', booked_at=now) — independent of email send result

**Guards**:
- Course must be of type `high_ticket`
- `high_ticket_hide_price` must be true

---

## Admin Routes (under `auth + admin` middleware)

### GET `/admin/email-templates`

**Controller**: `Admin\EmailTemplateController@index`
**Returns**: Inertia `Admin/EmailTemplates/Index`

**Props**:
```json
{
  "templates": [
    {
      "id": "integer",
      "name": "string",
      "event_type": "string",
      "subject": "string",
      "updated_at": "datetime string"
    }
  ]
}
```

---

### GET `/admin/email-templates/{template}/edit`

**Controller**: `Admin\EmailTemplateController@edit`
**Returns**: Inertia `Admin/EmailTemplates/Edit`

**Props**:
```json
{
  "template": {
    "id": "integer",
    "name": "string",
    "event_type": "string",
    "subject": "string",
    "body_md": "string"
  },
  "availableVariables": [
    { "key": "{{user_name}}", "label": "收件人姓名" }
  ]
}
```

Note: `availableVariables` differs per `event_type` — controller resolves based on template's event_type.

---

### PUT `/admin/email-templates/{template}`

**Controller**: `Admin\EmailTemplateController@update`

**Request body**:
```json
{
  "name": "string, required, max:100",
  "subject": "string, required, max:255",
  "body_md": "string, required"
}
```

Note: `event_type` is NOT editable — it is fixed at seed time.

**Response**: Redirect to `admin.email-templates.index` with flash `success`

---

## Admin Leads Routes (under `auth + admin` middleware)

### GET `/admin/high-ticket-leads`

**Controller**: `Admin\HighTicketLeadController@index`
**Returns**: Inertia `Admin/HighTicketLeads/Index`

**Query params**:
- `status` (optional): filter by `pending` | `contacted` | `converted` | `closed`

**Props**:
```json
{
  "leads": [
    {
      "id": "integer",
      "name": "string",
      "email": "string",
      "course": { "id": "integer", "title": "string" },
      "status": "string",
      "booked_at": "datetime string"
    }
  ],
  "filters": { "status": "string|null" },
  "dripCourses": [
    { "id": "integer", "title": "string" }
  ]
}
```

---

### PATCH `/admin/high-ticket-leads/{lead}/status`

**Controller**: `Admin\HighTicketLeadController@updateStatus`

**Request body**:
```json
{ "status": "pending | contacted | converted | closed" }
```

**Response**: `200 OK` with updated lead JSON

---

### POST `/admin/high-ticket-leads/notify-slot`

**Controller**: `Admin\HighTicketLeadController@notifySlot`
**Target leads**: `pending` status only

**Request body**:
```json
{ "lead_ids": [1, 2, 3] }
```

**Success** `200 OK`:
```json
{ "dispatched": 3 }
```

> ⚠️ **Implementation note**: `dispatched` = Jobs queued (not yet sent). Actual email send + `notified_count` increment happen async inside `NotifyHighTicketSlotJob`.

**Side effects** (async, per dispatched lead — inside `NotifyHighTicketSlotJob`):
1. Send email using `EmailTemplate::forEvent('high_ticket_slot_available')` with vars `{{user_name}}`, `{{course_name}}`
2. Increment `notified_count` by 1
3. Set `last_notified_at` to now

---

### POST `/admin/high-ticket-leads/subscribe-drip`

**Controller**: `Admin\HighTicketLeadController@subscribeDrip`

**Request body**:
```json
{
  "lead_ids": [1, 2, 3],
  "drip_course_id": "integer"
}
```

**Success** `200 OK`:
```json
{
  "dispatched": 2,
  "skipped": 1
}
```

> ⚠️ **Implementation note**: `dispatched` = Jobs queued (not yet completed). Actual drip enrollment happens async via `SubscribeDripLeadJob`. `skipped` = leads with an existing `status='active'` drip_subscription (checked synchronously before dispatch).

**Side effects** (async, per dispatched lead — inside `SubscribeDripLeadJob`):
1. `User::firstOrCreate(['email' => $lead->email], ['nickname' => $lead->name])`
2. `DripService::subscribe($user, $dripCourse)` — creates subscription + dispatches first lesson email
3. `$lead->update(['status' => 'closed'])`

---

## Course Admin Changes

### PUT `/admin/courses/{course}` (existing — extended)

New field added to existing request:
```json
{
  "high_ticket_hide_price": "boolean"
}
```

Only meaningful when `type = 'high_ticket'`.

---

## Frontend Props: Course/Show (extended)

New computed fields on the `course` prop:

```json
{
  "type": "high_ticket",
  "product_type": "high_ticket",
  "is_high_ticket": true,
  "high_ticket_hide_price": true
}
```

Flash key `high_ticket_booking_success` shown after successful booking submission.

---

## Variables per event_type

| event_type | Variables |
|------------|-----------|
| `high_ticket_booking_confirmation` | `{{user_name}}`, `{{user_email}}`, `{{course_name}}` |
| `course_gifted` | `{{user_name}}`, `{{course_name}}`, `{{course_description}}` |
| `lesson_added` | `{{user_name}}`, `{{course_name}}`, `{{lesson_title}}`, `{{classroom_url}}` |
| `high_ticket_slot_available` | `{{user_name}}`, `{{course_name}}` |
