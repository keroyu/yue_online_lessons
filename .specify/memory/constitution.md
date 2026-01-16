<!--
  Sync Impact Report
  ==================
  Version change: 1.0.0 → 1.1.0 (Laravel version upgrade)

  Modified principles:
  - I. Laravel Conventions: Laravel 11 → Laravel 12

  Added sections: None

  Removed sections: None

  Templates requiring updates:
  - .specify/templates/plan-template.md ✅ (no changes needed - generic structure)
  - .specify/templates/spec-template.md ✅ (no changes needed - generic structure)
  - .specify/templates/tasks-template.md ✅ (no changes needed - generic structure)

  Related files updated:
  - CLAUDE.md ✅ (Laravel 11 → Laravel 12)

  Follow-up TODOs: None
-->

# Online Lesson Platform Constitution

## Core Principles

### I. Laravel Conventions

All backend code MUST follow Laravel 12 best practices and PSR-12 standards:

- Controllers MUST use RESTful naming conventions
- Validation MUST be handled through Form Request classes
- Authorization MUST be implemented via Policy classes
- Database queries MUST use Eager Loading to prevent N+1 problems
- Models MUST define relationships explicitly

**Rationale**: Consistent Laravel patterns ensure maintainability and enable any Laravel
developer to contribute effectively without learning project-specific conventions.

### II. Vue & Frontend Standards

All frontend code MUST use Vue 3 with Composition API:

- Components MUST use `<script setup>` syntax
- Page components MUST reside in `Pages/` directory
- Reusable components MUST reside in `Components/` directory
- Inertia.js MUST be used for page routing and data passing
- Tailwind CSS utility classes MUST be used for styling

**Rationale**: Modern Vue 3 patterns with Composition API provide better TypeScript support,
cleaner code organization, and improved developer experience.

### III. Responsive Design First

All user-facing pages MUST be mobile-first responsive:

- Tailwind CSS mobile-first breakpoints MUST be used
- All pages MUST be tested on mobile, tablet, and desktop viewports
- Touch-friendly interactions MUST be considered for mobile users

**Rationale**: Users access content from various devices; mobile-first ensures accessibility
and optimal user experience across all screen sizes.

### IV. Simplicity Over Complexity

Development MUST follow pragmatic simplicity:

- Features MUST be completed before optimization
- Over-engineering MUST be avoided - implement what is needed now
- Code SHOULD be refactored only when necessary for current requirements
- YAGNI (You Aren't Gonna Need It) principle MUST be applied

**Rationale**: Premature optimization and over-engineering waste development time and
introduce unnecessary complexity that becomes technical debt.

### V. Security & Sensitive Data

Sensitive data MUST never be committed to version control:

- Environment files (.env) MUST be in .gitignore
- API keys, secrets, and credentials MUST NOT appear in code
- Database credentials MUST be managed through environment variables
- Video content delivery MUST use Vimeo embed (secure streaming)

**Rationale**: Security breaches from exposed credentials cause significant damage;
prevention through strict policies is non-negotiable.

## Technology Stack

This project uses a specific, curated technology stack that MUST NOT be changed without
explicit justification and constitution amendment:

| Layer | Technology | Version/Notes |
|-------|------------|---------------|
| Backend | Laravel | 12.x |
| Database | MySQL | Latest stable |
| Frontend Framework | Vue | 3.x |
| Frontend Routing | Inertia.js | Latest stable |
| CSS Framework | Tailwind CSS | Latest stable |
| Video Platform | Vimeo | Embed integration |
| Deployment | Laravel Forge | Managed hosting |

**Language Standards**:
- UI text and user-facing content: 中文 (Traditional Chinese)
- Code, comments, and documentation: English

## Development Workflow

### Local Development

```bash
# Start backend server
php artisan serve

# Start frontend build with hot reload
npm run dev
```

### Database Operations

```bash
# Fresh migration with seed data (DESTRUCTIVE - development only)
php artisan migrate:fresh --seed
```

### Testing

```bash
# Run all tests
php artisan test
```

### Code Quality Gates

Before committing code, developers MUST ensure:

1. Code follows PSR-12 standards (PHP)
2. Vue components use Composition API with `<script setup>`
3. All pages are responsive (tested on mobile viewport)
4. No sensitive data is included in commits
5. Tests pass (`php artisan test`)

## Governance

This constitution establishes binding development standards for the Online Lesson Platform.

**Amendment Process**:
1. Proposed changes MUST be documented with rationale
2. Changes MUST be reviewed for impact on existing code
3. Migration plan MUST be provided for breaking changes
4. Version MUST be incremented according to semantic versioning

**Versioning Policy**:
- MAJOR: Backward-incompatible changes (principle removals, tech stack changes)
- MINOR: New principles or expanded guidance
- PATCH: Clarifications and non-semantic refinements

**Compliance**:
- All pull requests MUST verify compliance with these principles
- Code reviews MUST check adherence to technology stack requirements
- Deviations MUST be justified and documented

**Runtime Guidance**: See `CLAUDE.md` for development commands and quick reference.

**Version**: 1.1.0 | **Ratified**: 2026-01-16 | **Last Amended**: 2026-01-16
