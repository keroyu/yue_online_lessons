# Data Model: 客製服務預約系統

**Phase**: 1 — Design
**Updated**: 2026-04-09

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

## Relationship Map (additions)

```
Course (SoftDeletes)
├── (existing relationships — unchanged)
└── (no new relationships — booking creates no DB record)

EmailTemplate (standalone — no model relationships)
```

## Removed from original design

- ~~`HighTicketBooking` model~~ — removed (no DB record on booking)
- ~~`high_ticket_bookings` table~~ — removed
- ~~`workshop_button_text` column~~ — removed (always "立即預約" when hide_price=true)
