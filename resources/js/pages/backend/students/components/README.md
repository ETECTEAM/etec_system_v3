# ClassTable

## Purpose

A tabular data view for the Class List page. Renders an array of class objects in a structured HTML table with action buttons (view, edit, delete). Replaces the card grid view when the user toggles to table mode.

## Features

- Column display: ID, Class title + term, Lesson, Building, Room, Status badge, Students / Capacity, Time, Actions
- Three action buttons per row (view, edit, delete) with colour-coded icon buttons
- Status rendered as an indigo pill badge
- Student occupancy shown as `{students}/{capacity}`
- Empty state message when the items array is empty
- Responsive wrapper with overflow handling
- Reuses generic Table / TableHeader / TableBody / TableRow / TableCell components

## Props

| Prop | Type | Required | Description |
|------|------|----------|-------------|
| `items` | `Array` | No (default `[]`) | Array of class objects to render. Each object should contain `id`, `title`, `term`, `lesson`, `building`, `floor`, `room`, `status`, `students`, `capacity`, `time` |

## Emits

No Emits.

## Dependencies

- `@lucide/vue` — Eye, Pencil, Trash2
- `Table` — `../../../../components/ui/table/Table.vue`
- `TableHeader` — `../../../../components/ui/table/TableHeader.vue`
- `TableHead` — `../../../../components/ui/table/TableHead.vue`
- `TableBody` — `../../../../components/ui/table/TableBody.vue`
- `TableRow` — `../../../../components/ui/table/TableRow.vue`
- `TableCell` — `../../../../components/ui/table/TableCell.vue`

## Methods

No methods defined.

## Reactive State

No reactive state (stateless component driven entirely by props).

## UI Description

- **Table Container** — white background, rounded shadow, overflow hidden.
- **Header Row** — column labels: ID, Class, Lesson, Building, Room, Status, Students, Time, Action (centered).
- **Data Rows** — one per `items` entry. Room column combines floor + room. Class column stacks title + term. Status uses an indigo pill badge. Action group centred with three icon buttons:
  - Blue (`Eye`) — view
  - Yellow (`Pencil`) — edit
  - Red (`Trash2`) — delete
- **Empty State** — a single row spanning all 9 columns displays "No classes found." centred with muted text.

## Navigation

No internal navigation. Action buttons are presentational (no handlers wired).

## Future Improvements

- Wire view / edit / delete buttons to routes
  - View: `/dashboard/students/{id}`
  - Edit: `/dashboard/students/{id}/edit`
  - Delete: confirmation modal + DELETE request
- Emit row-click event
- Sortable columns (click header to sort)
- Checkbox column for batch selection
- Pagination footer
- Column visibility toggle
- Responsive card fallback on mobile
- Loading skeleton rows
- Row hover highlight

## Example Usage

```vue
<ClassTable :items="classList" />
```
