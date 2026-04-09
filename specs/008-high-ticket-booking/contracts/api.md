# API Contracts: 客製服務預約系統

**Phase**: 1 — Design
**Updated**: 2026-04-09 - 預約端點改為 JSON API（非 Inertia redirect）；成功/失敗均回傳 JSON
**Updated**: 2026-04-09

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
- No database record created

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
