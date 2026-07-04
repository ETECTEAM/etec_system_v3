# Student Module — Pages

## ClassList

### Purpose

The main "My Classes" index page. Displays all classes in either a **Card Grid** or **Table View**, toggled by the user. Includes a text search filter, a reset button, and a call-to-action to create a new class.

### Features

- Search-as-you-type filtering across title, lesson, building, and room
- View mode toggle: Card Grid ↔ Table View with active state highlighting
- Reset search button
- "Add Class" button navigating to the create page
- Reactive filtered list via computed property
- Inertia-powered client-side navigation
- Uses `DashboardLayout` for consistent sidebar/header chrome

### Props

No Props.

### Emits

No Emits.

### Dependencies

- `@inertiajs/vue3` — `router`
- `@lucide/vue` — Search, RotateCcw, Plus, LayoutGrid, Table2
- `vue` — `ref`, `computed`
- `DashboardLayout` — `../../../layouts/DashboardLayout.vue`
- `ClassCrad` — `../../../components/ui/card/ClassCrad.vue`
- `ClassTable` — `./components/ClassTable.vue`

### Methods

| Method | Description |
|--------|-------------|
| `refresh()` | Clears the search input to show all classes |
| `goCreateClass()` | Navigates to `/dashboard/students/create` via Inertia router |

### Reactive State

| Name | Type | Description |
|------|------|-------------|
| `search` | `ref("")` | Search query string bound to the input field |
| `viewMode` | `ref("card")` | Current view mode: `"card"` or `"table"` |
| `classes` | `ref([...])` | Hardcoded array of class objects (mock data) |
| `filteredClasses` | `computed` | Filters `classes` by `search` matching title, lesson, building, room (case-insensitive). Returns unfiltered when search is empty |

### UI Description

- **Header** — "My Classes" title with subtitle "Manage all classes" left-aligned; "Add Class" button (indigo with Plus icon) right-aligned.
- **Toolbar** — White rounded shadow bar containing:
  - Search input with search icon
  - "Reset" button (dark grey, RotateCcw icon)
  - "Card" toggle button (LayoutGrid icon)
  - "Table" toggle button (Table2 icon)
  - Active mode button gets indigo background.
- **Card View** — Responsive grid (`1col` / `2col` / `3col` at breakpoints) rendering `ClassCrad` for each item.
- **Table View** — Full-width `ClassTable` component.

### Navigation

| Route | Action |
|-------|--------|
| `/dashboard/students` | Class list page (current) |
| `/dashboard/students/create` | Create class page (via `goCreateClass`) |

### Future Improvements

- Replace hardcoded mock data with Inertia server-side props
- Server-side pagination with Laravel `LengthAwarePaginator`
- Debounced search (300ms) to reduce renders
- Sorting controls (by title, date, capacity)
- Filters dropdown (by status, building, term)
- Loading spinner while data fetches
- Skeleton placeholders during load
- Error state with retry button
- Empty state illustration when no classes exist
- Lazy-load card images / avatars
- Keyboard shortcuts (`/` to focus search, `c` to create)

---

## CreateClass

### Purpose

A form page for creating a new class. Collects all required class metadata and submits via Inertia POST request. Provides back-navigation and cancel functionality.

### Features

- Inertia `useForm` integration with full validation support
- Fields: Class Title, Lesson, Status (select), Building (select), Floor (select), Room, Study Days, Study Time, Capacity
- Status: "Physical Class" / "Online Class"
- Back button and Cancel button both navigate to class list
- Save button submits the form
- Responsive 3-column grid layout on large screens
- Styled card container with shadow and border

### Props

No Props.

### Emits

No Emits.

### Dependencies

- `@inertiajs/vue3` — `useForm`, `router`
- `@lucide/vue` — GraduationCap, ArrowLeft, Save
- `DashboardLayout` — `../../../layouts/DashboardLayout.vue`

### Methods

| Method | Description |
|--------|-------------|
| `back()` | Navigates to `/dashboard/students` via `router.get()` |
| `submit()` | POSTs the form to `/dashboard/students` via Inertia |

### Reactive State

| Name | Type | Description |
|------|------|-------------|
| `form` | `useForm({...})` | Inertia form object with fields: `title`, `lesson`, `building`, `floor`, `room`, `status`, `term`, `time`, `capacity` (default 20), `description` |

### UI Description

- **Header** — Icon (GraduationCap) + "Create New Class" title + "Create and manage class information." subtitle on left; "Back" button (ArrowLeft icon, outlined) on right.
- **Form Card** — White rounded-3xl shadow card with 3-column grid of form fields:
  - **Class Title** — text input (indigo focus ring)
  - **Lesson** — text input
  - **Status** — select dropdown (Physical Class / Online Class)
  - **Building** — select (Building A / B / C)
  - **Floor** — select (Floor 1 / 2 / 3)
  - **Room** — text input
  - **Study Days** — text input (e.g. Mon & Thu)
  - **Study Time** — text input (e.g. 09:00 AM - 10:30 AM)
  - **Capacity** — number input (min 1)
- **Footer** — Right-aligned "Cancel" (outlined) and "Save Class" (indigo, Save icon) buttons.

### Navigation

| Route | Action |
|-------|--------|
| `/dashboard/students` | Back / Cancel navigation |
| `/dashboard/students` | Form POST target (create) |

### Future Improvements

- Server-side validation error display per field
- Inline validation messages under each input
- Date/time picker components
- Image / thumbnail upload
- Autocomplete for building / floor based on API
- Draft auto-save to localStorage
- Confirmation dialog on cancel if form is dirty
- Loading state on submit button
- Success toast after creation
- Redirect to newly created class detail page
- Permission / role check

---

## Example Usage (ClassList)

```vue
<template>
  <ClassList />
</template>
```

## Example Usage (CreateClass)

```vue
<template>
  <CreateClass />
</template>
```
