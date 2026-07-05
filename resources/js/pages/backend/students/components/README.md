# View Class – Page & Components

## Purpose

The View Class page (`ViewClass.vue`) is a dedicated page for viewing a single class record and managing its enrolled student deposits. It is **not** a re-use of the Class List table — it focuses on class details, deposit tracking, and student management.

---

## Page: `ViewClass.vue`

**Path:** `resources/js/pages/backend/students/ViewClass.vue`

### Route

| Method | URI | Name |
|--------|-----|------|
| GET | `/dashboard/students/view/{id}` | `students.show` |

### Props (from Laravel/Inertia)

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `classData` | `Object` | `null` | Single class record |
| `students` | `Array` | `[]` | Enrolled students with deposit info |
| `depositSummary` | `Object` | `null` | Aggregated deposit statistics |

### Layout Sections

1. **Breadcrumbs** – Dashboard > Class List > View Class
2. **PageHero** – eyebrow "Enroll Management", title "View Class"
3. **Back Button + Quick Actions** – responsive row
4. **ClassInformationCard** – full class detail card with progress bar
5. **DepositSummaryCard** – four stat cards
6. **DepositTable** – searchable student deposit table

### Defensive Handling

- If `classData` is `null`, a centered loading fallback is shown.
- All child components use optional chaining (`?.`) and fallback values.

---

## Components

### 1. `ClassInformationCard.vue`

**Path:** `resources/js/pages/backend/students/components/ClassInformationCard.vue`

Displays a single class record in a card with icon-labeled fields and an enrollment progress bar.

#### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `classData` | `Object` | `null` | The class record |

#### Fields Displayed

- Class Name, Lesson, Teacher, Building, Floor, Room, Study Day, Study Time, Status (pill badge), Capacity, Current Students
- Enrollment Progress Bar (gradient, animated width)

#### Icons Used

`GraduationCap`, `BookOpen`, `Presentation`, `User`, `Building2`, `Layers`, `DoorOpen`, `Calendar`, `Clock`, `BadgeCheck`, `Users`

---

### 2. `DepositTable.vue`

**Path:** `resources/js/pages/backend/students/components/DepositTable.vue`

Searchable table of enrolled students with deposit and payment details.

#### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `students` | `Array` | `[]` | Array of student deposit objects |

#### Expected Student Object Shape

```json
{
  "id": 1,
  "name": "Sok Dara",
  "gender": "Male",
  "phone": "012 345 678",
  "deposit_amount": 150.00,
  "payment_date": "2025-01-15",
  "payment_status": "Paid",
  "remaining_balance": 0.00
}
```

#### Columns

| Column | Detail |
|--------|--------|
| Student ID | `#` prefixed |
| Student Name | Bold, nowrap |
| Gender | — |
| Phone Number | — |
| Deposit Amount | Currency formatted |
| Payment Date | — |
| Payment Status | Colored pill badge |
| Remaining Balance | Currency formatted |
| Action | 3 icon buttons |

#### Payment Status Badges

| Status | Color |
|--------|-------|
| Paid | `bg-emerald-100 text-emerald-700` |
| Partial | `bg-amber-100 text-amber-700` |
| Unpaid | `bg-red-100 text-red-700` |

#### Action Buttons

| Button | Color | Icon |
|--------|-------|------|
| View Payment | Blue | `Eye` |
| Edit Deposit | Amber | `Pencil` |
| Print Receipt | Slate | `Printer` |

#### Features

- Client-side search filter (by name, ID, phone)
- Empty state with contextual message (search vs no data)

---

### 3. `DepositSummaryCard.vue`

**Path:** `resources/js/pages/backend/students/components/DepositSummaryCard.vue`

Four responsive stat cards showing deposit overview.

#### Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `depositSummary` | `Object` | `null` | Aggregated deposit statistics |

#### Expected Object Shape

```json
{
  "total_students": 5,
  "paid_students": 2,
  "partial_students": 2,
  "unpaid_students": 1,
  "total_deposit_collected": 425.00
}
```

#### Cards

| Card | Icon | Icon Background |
|------|------|-----------------|
| Total Students | `Users` | Indigo |
| Paid Students | `CheckCircle2` | Emerald |
| Unpaid Students (partial + unpaid) | `XCircle` | Red |
| Total Deposit Collected | `DollarSign` | Amber |

---

### 4. `QuickActions.vue`

**Path:** `resources/js/pages/backend/students/components/QuickActions.vue`

Row of action buttons for common tasks.

#### Props

None.

#### Buttons

| Button | Style | Icon |
|--------|-------|------|
| Add Student | Solid indigo | `UserPlus` |
| Record Deposit | White border | `Banknote` |
| Export Deposit Report | White border | `Download` |
| Print Student List | White border | `Printer` |

---

## Routing

**File:** `routes/web/backend/student.php`

```php
Route::prefix('/dashboard/students')->group(function () {
    Route::get('/', ...)                    // ClassList
    Route::get('/create', ...)              // CreateClass
    Route::get('/view/{id}', ...)           // ViewClass (students.show)
});
```

The `/view/{id}` route returns three props to Inertia:

- `classData` – single class object
- `students` – array of enrolled students
- `depositSummary` – aggregated stats

---

## Dependencies

- `@lucide/vue` – all icons
- `@inertiajs/vue3` – `router` for navigation
- `Table` / `TableHeader` / `TableHead` / `TableBody` / `TableRow` / `TableCell` – reusable table components from `components/ui/table/`
- `DashboardLayout` – main layout wrapper
- `Breadcrumbs` – breadcrumb navigation
- `PageHero` – page title/eyebrow component

---

## Example Usage

```vue
<!-- Inertia renders this automatically from the route -->
<ViewClass
  :classData="classData"
  :students="students"
  :depositSummary="depositSummary"
/>
```

```vue
<!-- Standalone component usage -->
<ClassInformationCard :classData="classData" />
<DepositSummaryCard :depositSummary="depositSummary" />
<DepositTable :students="students" />
<QuickActions />
```
