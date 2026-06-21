# IELTS CBT AI — Frontend UI System

## What was added
A premium Laravel 12 Blade/Tailwind/Alpine frontend UI system for an IELTS CBT & LMS SaaS.

## Main routes
- `/` Landing page
- `/ui` Design system preview
- `/login`, `/register`, `/forgot-password`, `/reset-password`, `/email/verify`, `/two-factor-challenge`, `/confirm-password`
- `/student` or `/dashboard` Student dashboard
- `/teacher` Teacher dashboard
- `/admin` Admin dashboard
- `/courses`, `/courses/show`
- `/exam/reading`, `/exam/listening`, `/exam/writing`, `/exam/speaking`

## Folder structure
- `resources/views/components/ui/` reusable UI components
- `resources/views/components/layouts/` anonymous layout components used by the demo pages
- `resources/views/layouts/` same layout files kept for standard Laravel layout structure
- `resources/views/pages/` frontend pages grouped by module
- `resources/views/partials/sidebar.blade.php` shared dashboard sidebar
- `resources/css/app.css` Tailwind v4 design tokens, global utilities and component classes
- `resources/js/app.js` lightweight frontend helper

## Blade components
Buttons, inputs, textarea, select, checkbox, radio, label, badge, card, stat-card, modal, drawer, dropdown, toast, alert, table, pagination, empty state, avatar, progress, tabs, breadcrumb, sidebar-link, timer, question navigator, audio player, waveform and rich editor.

## Design system
- Primary: `#2563EB`
- Secondary: `#1E40AF`
- Success: `#10B981`
- Warning: `#F59E0B`
- Danger: `#EF4444`
- Neutral grayscale
- Inter typography
- Soft shadows, rounded corners, large white space, minimal SaaS layout

## Responsive strategy
Mobile-first Tailwind classes, responsive dashboard sidebar, mobile drawer navigation, stacked exam panels on small screens and split panels on desktop.

## Dark mode strategy
All major components include `dark:` variants. The layouts support `.dark` class toggling through Alpine.

## Accessibility strategy
Semantic structure, visible focus rings, accessible contrast, labelled fields, keyboard-friendly buttons and navigation-ready components.

## Important
No backend business logic, database, migrations, models or controllers were added.
