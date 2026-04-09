# Quickstart: 客製服務預約系統

**Phase**: 1 — Design
**Updated**: 2026-04-09

---

## Setup

```bash
php artisan migrate
php artisan db:seed --class=EmailTemplateSeeder
npm run dev
```

---

## Implementation Order

### Step 1: Database Migrations
1. `add_high_ticket_fields_to_courses_table` — alter type enum + add `high_ticket_hide_price`
2. `create_email_templates_table` — new table

### Step 2: Models
1. Update `Course` — add fillable, cast, `isHighTicket` accessor
2. Create `EmailTemplate` — with `renderSubject()` / `renderBody()` methods

### Step 3: Mailable & Controller
1. Create `HighTicketBookingMail` — Markdown mailable (follows BatchEmailMail pattern, CommonMark → HTML)
2. Create `HighTicketBookingService` — validates course, finds template, sends email
3. Create `HighTicketBookingController` (public) — validates form, delegates to service
3. Create `Admin\EmailTemplateController` — CRUD
4. Update `Admin\CourseController` — add `high_ticket_hide_price` to validation + store/update
5. Update `routes/web.php`

### Step 4: Frontend
1. Update `CourseForm.vue` — add high_ticket to courseTypes + conditional hide_price toggle
2. Update `Course/Show.vue` — high_ticket UI branches (info text, booking form, button label)
3. Create `Admin/EmailTemplates/Index.vue`
4. Create `Admin/EmailTemplates/Edit.vue` — textarea + variable insert

---

## Key File Touchpoints

| File | Change | Description |
|------|--------|-------------|
| `app/Models/Course.php` | Modify | +fillable, cast, isHighTicket accessor |
| `app/Models/EmailTemplate.php` | Create | New model |
| `app/Mail/HighTicketBookingMail.php` | Create | Markdown mailable (BatchEmailMail pattern) |
| `app/Services/HighTicketBookingService.php` | Create | Booking logic + email send |
| `app/Http/Controllers/HighTicketBookingController.php` | Create | Public booking submit (delegates to service) |
| `app/Http/Controllers/Admin/EmailTemplateController.php` | Create | Template CRUD |
| `app/Http/Controllers/Admin/CourseController.php` | Modify | +high_ticket_hide_price |
| `app/Http/Requests/Admin/StoreCourseRequest.php` | Modify | +high_ticket to type enum |
| `app/Http/Requests/Admin/UpdateCourseRequest.php` | Modify | +high_ticket + hide_price |
| `routes/web.php` | Modify | +booking route, +template admin routes |
| `resources/js/Components/Admin/CourseForm.vue` | Modify | +high_ticket option + toggle |
| `resources/js/Pages/Course/Show.vue` | Modify | Workshop UI branches |
| `resources/js/Pages/Admin/EmailTemplates/Index.vue` | Create | Template list |
| `resources/js/Pages/Admin/EmailTemplates/Edit.vue` | Create | Template editor |

---

## Removed from original scope

- ~~Image insertion in email templates~~
- ~~HighTicketBooking model / DB table~~
- ~~Admin booking record management~~
- ~~workshop_button_text column~~ (was removed)
