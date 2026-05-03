# Data Model: 客製服務預約系統

**Phase**: 1 — Design
**Updated**: 2026-04-09
**Updated**: 2026-04-09 - 新增 `high_ticket_leads` 資料表（US5）及 HighTicketLead 模型

---

## Schema Changes

### 1. `courses` table — alter type enum + new column

```sql
-- Extend type enum
ALTER TABLE courses MODIFY COLUMN type ENUM('lecture', 'mini', 'full', 'high_ticket') NOT NULL;

-- New high_ticket configuration column
ALTER TABLE courses
  ADD COLUMN high_ticket_hide_price TINYINT(1) NOT NULL DEFAULT 0 AFTER type;
```

**Fields**:
| Column | Type | Default | Description |
|--------|------|---------|-------------|
| `type` | ENUM('lecture','mini','full','high_ticket') | 'lecture' | Product category (altered) |
| `high_ticket_hide_price` | BOOLEAN | false | When true: hide price block + show "立即預約" button |

---

### 2. `email_templates` table — new

```sql
CREATE TABLE email_templates (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  event_type VARCHAR(50) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  body_md LONGTEXT NOT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  INDEX idx_event_type (event_type)
);
```

**Fields**:
| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT | Primary key |
| `name` | VARCHAR(100) | Human-readable template name |
| `event_type` | VARCHAR(50) | e.g. `high_ticket_booking_confirmation` |
| `subject` | VARCHAR(255) | Email subject (supports variables) |
| `body_md` | LONGTEXT | Markdown body with `{{variable}}` placeholders; converted to HTML via CommonMark before sending |

**Note**: `body_md` follows the same pattern as `BatchEmailMail` — stored as Markdown, rendered to HTML via `CommonMarkConverter` at send time.

**Supported variables**:
| Variable | Value |
|----------|-------|
| `{{user_name}}` | Visitor's name |
| `{{user_email}}` | Visitor's email |
| `{{course_name}}` | Course name |

---

### 3. `high_ticket_leads` table — new

```sql
CREATE TABLE high_ticket_leads (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(255) NOT NULL,
  course_id BIGINT UNSIGNED NOT NULL,
  status ENUM('pending', 'contacted', 'converted', 'closed') NOT NULL DEFAULT 'pending',
  booked_at TIMESTAMP NOT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  INDEX idx_email (email),
  INDEX idx_status (status),
  INDEX idx_course_id (course_id)
);
```

**Fields**:
| Column | Type | Default | Description |
|--------|------|---------|-------------|
| `name` | VARCHAR(100) | — | Visitor's name as entered in booking form |
| `email` | VARCHAR(255) | — | Visitor's email |
| `course_id` | BIGINT UNSIGNED | — | FK to courses (no constraint — soft delete safe) |
| `status` | ENUM | 'pending' | Sales pipeline stage |
| `notified_count` | TINYINT | 0 | Number of "slot available" notifications sent |
| `last_notified_at` | TIMESTAMP | NULL | Timestamp of last slot notification |
| `booked_at` | TIMESTAMP | — | Submission timestamp |

**Status values**:
| Value | Label | Meaning |
|-------|-------|---------|
| `pending` | 待聯繫 | Newly booked, not yet contacted |
| `contacted` | 已聯繫 | Admin has reached out / interview held |
| `converted` | 已成交 | Lead purchased the service |
| `closed` | 已關閉 | No deal, no further action |

**Note**: Duplicate records (same email + course) are allowed — each booking creates a new row for complete history.

---

## Eloquent Models

### `Course` (modified)

Add to `$fillable`: `'high_ticket_hide_price'`

Add to `casts()`: `'high_ticket_hide_price' => 'boolean'`

Add accessor:
```php
protected function isHighTicket(): Attribute
{
    return Attribute::make(
        get: fn () => $this->type === 'high_ticket'
    );
}
```

---

### `EmailTemplate` (new)

```
App\Models\EmailTemplate
Table: email_templates
```

```php
$fillable = ['name', 'event_type', 'subject', 'body_md']

Scopes:
- scopeForEvent(Builder $query, string $eventType): where event_type = $eventType

Methods:
- renderSubject(array $vars): string  — str_replace vars into subject
- renderBody(array $vars): string     — str_replace vars into body_md, then convert to HTML via CommonMarkConverter (same as BatchEmailMail)
```

---

### `HighTicketLead` (new)

```
App\Models\HighTicketLead
Table: high_ticket_leads
```

```php
$fillable = ['name', 'email', 'course_id', 'status', 'notified_count', 'last_notified_at', 'booked_at']

$casts = ['booked_at' => 'datetime', 'last_notified_at' => 'datetime', 'status' => 'string', 'notified_count' => 'integer']

Scopes:
- scopeByStatus(Builder $query, string $status): where status = $status

Relations:
- belongsTo(Course::class)  // for displaying course name in admin list
```

---

## Relationship Map (additions)

```
Course (SoftDeletes)
├── (existing relationships — unchanged)
└── hasMany(HighTicketLead::class)

EmailTemplate (standalone — no model relationships)

HighTicketLead
└── belongsTo(Course::class)
```

## Removed from original design

- ~~`HighTicketBooking` model~~ — removed in original design; **reinstated as `HighTicketLead`** (US5)
- ~~`high_ticket_bookings` table~~ — replaced by `high_ticket_leads` table with status pipeline
- ~~`workshop_button_text` column~~ — removed (always "立即預約" when hide_price=true)
