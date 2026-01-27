# Online Lesson Platform

數位內容販售平台

## Tech Stack

- **Backend:** Laravel 12 + MySQL (本機從官網 .pkg 安裝，port 3306)
- **Frontend:** Inertia.js + Vue 3 + Tailwind CSS
- **Email:** Resend.com
- **Video:** Vimeo Embed
- **Deploy:** Laravel Forge

## Commands

```bash
# Dev
php artisan serve
npm run dev

# Database
php artisan migrate:fresh --seed

# Test
php artisan test
```

## Coding Conventions

**PHP (Laravel):**
- PSR-12 標準
- Controller 使用 RESTful 命名
- Form Request 處理驗證
- Policy 處理授權
- Eager Loading 避免 N+1

**Vue:**
- Composition API (`<script setup>`)
- 頁面放 `Pages/`，組件放 `Components/`

**CSS:**
- Tailwind utility classes
- Mobile-first RWD

**語言:**
- UI 文案：中文
- Code / Comments：英文

## Development Rules

- 所有頁面需 RWD
- 不過度設計，先完成再優化
- 敏感資料不進 git

## Active Technologies
- PHP 8.2+ / Laravel 12.x + Laravel 12, Inertia.js, Vue 3, Tailwind CSS (001-course-platform-mvp)
- MySQL (Latest stable) (001-course-platform-mvp)
- MySQL (Latest stable), Local filesystem for images (storage/app/public) (002-classroom-admin)
- PHP 8.2+ / Laravel 12.x + Inertia.js, Vue 3, Tailwind CSS, vuedraggable@nex (002-classroom-admin)
- PHP 8.2+ / Laravel 12.x + Inertia.js, Vue 3, Tailwind CSS, Resend (email) (003-member-management)
- MySQL (existing database with users, purchases, lesson_progress tables) (003-member-management)
- Laravel Cache (file-based) for RSS feed caching (004-homepage-enhancement)

## Recent Changes
- 001-course-platform-mvp: Added PHP 8.2+ / Laravel 12.x + Laravel 12, Inertia.js, Vue 3, Tailwind CSS
