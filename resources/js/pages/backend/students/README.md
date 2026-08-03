# Student Management Module

## Overview

A full-featured **Student Management Module** built for the ETEC system. This module provides complete administration of classes, students, attendance, deposits/payments, and reports. It leverages a modern tech stack with **Laravel 13** on the backend, **Vue 3** with **Inertia.js** for seamless single-page navigation, and **Tailwind CSS** for a polished, responsive UI.

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| **Backend** | Laravel 13 |
| **Frontend** | Vue 3 (Composition API, `<script setup>`) |
| **SPA Engine** | Inertia.js |
| **Styling** | Tailwind CSS |
| **Icons** | Lucide Vue |
| **Database** | MySQL |
| **Tooling** | Vite, Ziggy |

---

## Folder Structure

```
resources/
└── js/
    ├── components/
    │   └── ui/
    │       ├── card/
    │       │   ├── Card.vue
    │       │   ├── ClassCrad.vue
    │       │   ├── ClassActionMenu.vue
    │       │   └── index.js
    │       ├── table/
    │       │   ├── Table.vue
    │       │   ├── TableHeader.vue
    │       │   ├── TableHead.vue
    │       │   ├── TableBody.vue
    │       │   ├── TableRow.vue
    │       │   └── TableCell.vue
    │       ├── breadcrumbs/
    │       │   └── Breadcrumbs.vue
    │       ├── page-hero/
    │       │   └── PageHero.vue
    │       └── notification-badge/
    │           └── NotificationBadge.vue
    └── pages/
        └── backend/
            └── students/
                ├── ClassList.vue
                ├── CreateClass.vue
                ├── EditClass.vue
                ├── ViewClass.vue
                ├── Form.vue (QR attendance check-in)
                ├── List.vue (attendance list)
                └── components/
                    ├── ClassTable.vue
                    ├── BarClass.vue
                    └── README.md
```

---

## Features

### Class Management
- [x] Create Class
- [x] Edit Class
- [x] View Class
- [x] Delete Class
- [x] Search / Filter Classes
- [x] Card Grid View
- [x] Table View (with sortable columns)
- [x] Capacity tracking with progress bar
- [x] Status badges (Active / Inactive / Completed)
- [ ] Pagination (server-side)

### Student Management
- [ ] Add Student to Class
- [ ] Remove Student from Class
- [ ] Student List per Class
- [ ] Student Attendance Tracking
- [ ] Student Profile

### Deposit & Payment Management
- [ ] Record Deposit
- [ ] Deposit Summary Card
- [ ] Remaining Balance Tracking
- [ ] Payment History

### QR System
- [x] QR Code Generation
- [x] QR Scan Attendance
- [x] Location-based Check-in
- [ ] QR Add Student to Class

### Reports
- [ ] Export to Excel
- [ ] Export to PDF
- [ ] Print Student List
- [ ] Print Deposit Report

### Notifications
- [ ] Telegram Notification
- [ ] Email Notification

### UI & Experience
- [x] Responsive Design (mobile-first)
- [x] View mode toggle (Card / Table)
- [x] Breadcrumb navigation
- [x] Inertia.js client-side navigation
- [x] Dialog action menu (BarClass)
- [x] Search-as-you-type filtering
- [x] Validation error display
- [ ] Loading skeletons
- [ ] Empty state illustrations
- [ ] Toast notifications

---

## Components

### ClassCrad.vue
Display a single class in card format within the grid view.
- Shows title, lesson, building, floor/room, study days, time, and capacity with a gradient progress bar
- Renders a status badge (active / inactive / completed)
- Includes a three-dot action menu to open the BarClass dialog
- Emits `view`, `edit`, `add-student`, `qr`, `switch-teacher`, `attendance`, `export`, `pre-end`, `end`

### ClassTable.vue
Tabular view of all classes, used when the user toggles to table mode.
- Columns: ID, Class (title + term), Lesson, Building, Room, Status, Students/Capacity, Time, Actions
- Action buttons per row: View (blue), Edit (yellow), Delete (red)
- Empty state fallback message
- Wired to Inertia routes for navigation

### BarClass.vue
Slide-over / dialog action panel triggered from the class card.
- Teleported to `<body>` with backdrop blur and ESC-to-close
- Action items: Edit Class, Add Student, QR Add, Switch Teacher, View Details, Attendance, Export Student List
- Danger zone: Pre-End Class (amber) and End Class (red) with confirmations
- Keyboard accessible with focus trap

### ClassActionMenu.vue
Minimal three-dot vertical icon button that emits `open-bar` to trigger the BarClass dialog.
- Used inside `ClassCrad.vue`

### ClassInformationCard.vue *(planned)*
Dedicated card to display full class metadata on the View Class page.
- Shows lesson, building, floor/room, study days, time, status
- Icon-labeled rows for scanability

### DepositSummaryCard.vue *(planned)*
Summary card showing total deposits, remaining balance, and payment status for a class.
- Color-coded indicators for overdue / on-track / paid

### DepositTable.vue *(planned)*
Detailed table of all deposit records per student or per class.
- Columns: Student name, amount, date, payment method, notes, balance

### QuickActions.vue *(planned)*
Card on the View Class sidebar with shortcut buttons.
- Edit Class, Add Student, Take Attendance, End Class

### NotificationBadge.vue
Reusable badge for displaying counts (e.g., unread notifications, pending actions).
- Used in the class card header

---

## Pages

### ClassList.vue
The main index page (`/dashboard/enroll`). Displays all classes in either a **Card Grid** or **Table View**, toggled by the user. Includes search-as-you-type filtering with server-side pagination support. Breadcrumbs + PageHero provide contextual navigation. An "Add Class" button links to the create page.

### CreateClass.vue
A multi-field form page for creating a new class (`/dashboard/enroll/create`). Uses Inertia `useForm` for validation. Fields include title, course, lesson, status, building, floor, room, study days, study time, and capacity. Back and Cancel buttons navigate to the class list. On submit, POSTs to `/dashboard/enroll`.

### EditClass.vue
Pre-filled form for updating an existing class (`/dashboard/enroll/{id}/edit`). Loads existing data via Inertia props and submits a `PUT` request. Includes server-side validation error display per field. Manually wired breadcrumbs and a Back button.

### ViewClass.vue
Detail page for a single class (`/dashboard/enroll/{id}`). Shows full class information (lesson, building, floor/room, study days, time, status), enrolled students list with avatars, capacity progress bar with percentage filled, and a sidebar with quick actions (Edit, Add Student, End Class).

### Form.vue *(QR Check-in)*
Location-based QR attendance check-in page. Uses the browser Geolocation API to verify the student is within 50m of the ETEC Center. Displays distance from the center and allows check-in when within range.

### List.vue *(Attendance)*
Attendance list page with QR code generation for check-in. Displays a table of students with their attendance status (Present / Late). Intended for instructors to mark attendance.

---

## Routes

| Method | URI | Action |
|--------|-----|--------|
| `GET` | `/dashboard/enroll` | `ClassList` — view all classes |
| `GET` | `/dashboard/enroll/create` | `CreateClass` — show create form |
| `POST` | `/dashboard/enroll` | Store new class |
| `GET` | `/dashboard/enroll/{id}` | `ViewClass` — view class details |
| `GET` | `/dashboard/enroll/{id}/edit` | `EditClass` — show edit form |
| `PUT` | `/dashboard/enroll/{id}` | Update class |
| `DELETE` | `/dashboard/enroll/{id}` | Delete class |

---

## Development Status

| Feature | Status |
|---------|--------|
| Class List (Card + Table) | ✅ Completed |
| View Mode Toggle | ✅ Completed |
| Search / Filter | ✅ Completed |
| Create Class Form | ✅ Completed |
| Edit Class Form | ✅ Completed |
| View Class Detail | ✅ Completed |
| BarClass Action Dialog | ✅ Completed |
| UX (Breadcrumbs, PageHero, responsive) | ✅ Completed |
| QR Code Generation | ✅ Completed |
| Attendance Form (QR Check-in) | ✅ Completed |
| Server-side Validation | ✅ Completed |
| Pagination | ⏳ Planned |
| Delete Class (Confirmation Modal) | ⏳ Planned |
| Add Student to Class | ⏳ Planned |
| Remove Student from Class | ⏳ Planned |
| Student Attendance | ⏳ Planned |
| QR Attendance | ⏳ Planned |
| QR Add Student | ⏳ Planned |
| Deposit Management | ⏳ Planned |
| Payment History | ⏳ Planned |
| Refund System | ⏳ Planned |
| Export Excel | ⏳ Planned |
| Export PDF | ⏳ Planned |
| Print Student List | ⏳ Planned |
| Telegram Notification | ⏳ Planned |
| Email Notification | ⏳ Planned |
| SMS Notification | ⏳ Planned |
| Teacher Management | ⏳ Planned |
| Student Import | ⏳ Planned |
| Dashboard Charts | ⏳ Planned |
| Waiting List | ⏳ Planned |
| Course Schedule | ⏳ Planned |
| Calendar View | ⏳ Planned |
| Multi-language Support | ⏳ Planned |
| Dark Mode | ⏳ Planned |
| Activity Log | ⏳ Planned |
| Audit Log | ⏳ Planned |
| Role & Permission System | ⏳ Planned |
| Backup & Restore | ⏳ Planned |
| Testing (Vitest + Laravel Dusk) | ⏳ Planned |

---

## Future Features

- [ ] Attendance tracking per session
- [ ] QR-based attendance scanning
- [ ] Teacher/instructor assignment & management
- [ ] Bulk student import (CSV / Excel)
- [ ] Export class list to Excel
- [ ] Export attendance & deposit reports to PDF
- [ ] Email notifications (class reminders, payment receipts)
- [ ] Telegram bot notifications
- [ ] SMS notifications
- [ ] Dashboard analytics with charts (enrollment trends, revenue)
- [ ] Payment history with invoice generation
- [ ] Refund processing
- [ ] Waiting list with auto-enrollment
- [ ] Course scheduling & calendar view
- [ ] Multi-language / localization (i18n)
- [ ] Dark mode toggle
- [ ] Full activity log (who did what, when)
- [ ] Audit trail for compliance
- [ ] Role-based access control (admin, teacher, finance)
- [ ] Backup & restore functionality
- [ ] Server-side pagination for large datasets
- [ ] Debounced search with Laravel query scopes
- [ ] Sorting controls (by title, date, capacity)
- [ ] Advanced filters (by status, building, term)
- [ ] Loading skeletons during data fetch
- [ ] Empty state with illustration
- [ ] Success / error toast notifications
- [ ] Confirmation modal for destructive actions
- [ ] Draft auto-save to localStorage
- [ ] Image / thumbnail upload for classes
- [ ] Mobile responsive table fallback
- [ ] Bulk actions (select multiple classes)
- [ ] Unit testing (Vue Test Utils / Vitest)
- [ ] End-to-end testing (Playwright / Laravel Dusk)
- [ ] Keyboard shortcuts (`/` to focus search, `c` to create)

---

## Coding Standards

| Practice | Description |
|----------|-------------|
| **Vue 3 Composition API** | All components use `<script setup>` with `ref`, `computed`, `watch` |
| **Reusable Components** | UI primitives (Table, Card, Badge) are extracted to `components/ui/` |
| **Single Responsibility** | Each component has a clear, focused purpose |
| **Responsive Design** | Mobile-first layouts with Tailwind breakpoints (`sm:`, `md:`, `lg:`, `xl:`) |
| **Tailwind CSS** | Utility-first styling; no custom CSS files unless necessary |
| **Lucide Icons** | Consistent icon set via `@lucide/vue` |
| **Inertia.js** | SPA-style navigation using `@inertiajs/vue3` router; form handling via `useForm` |
| **Clean Code** | Meaningful names, flat structure, no commented-out code |
| **SOLID Principles** | Components are open for extension, closed for modification |
| **Laravel Best Practices** | Route model binding, validation, resource controllers, service classes |
| **Accessibility** | Focus management, aria labels, keyboard navigation, `Teleport` for modals |

---

## Getting Started

```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Build frontend assets
npm run build

# Run database migrations
php artisan migrate

# Start the development server
php artisan serve

# Compile assets in watch mode (separate terminal)
npm run dev
```

---

<p align="center">Built with ❤️ using Laravel & Vue 3</p>
