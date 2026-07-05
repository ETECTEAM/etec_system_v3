# ClassCard

## Purpose

Displays a single class entity in a visually rich card layout. Used within the Class List page to present class details (title, lesson, building, room, schedule, capacity, and student occupancy) in a compact, scannable format. Includes a notification badge for unread alerts and a capacity-progress bar.

## Features

- Renders full class metadata (ID, title, lesson, building, floor, room, status, schedule, capacity)
- Student occupancy indicator with a dynamic progress bar
- Online / Physical class status with an icon indicator
- Notification badge overlay for unread count
- "View Class" action button
- Capacity utilisation percentage calculation
- Rounded shadow card container

## Props

| Prop | Type | Required | Description |
|------|------|----------|-------------|
| `classData` | `Object` | Yes | Class entity data containing `id`, `title`, `lesson`, `building`, `floor`, `room`, `status`, `term`, `time`, `students`, `capacity` |
| `count` | `Number` | No (default `0`) | Unread notification count passed to `NotificationBadge` |

## Emits

No Emits.

## Dependencies

- `@lucide/vue` — GraduationCap, EllipsisVertical, Building2, DoorOpen, CalendarDays, Clock3, Users, BookOpen, MonitorSmartphone
- `vue` — `computed`
- `NotificationBadge` — `../notification-badge/NotificationBadge.vue`

## Methods

No user-defined methods (all logic is in computed properties).

## Computed Properties

| Name | Description |
|------|-------------|
| `capacity` | Returns `classData.capacity` |
| `fill` | Calculates the percentage of capacity filled: `(students / capacity) * 100` |
| `online` | Boolean — `true` when `classData.status === "Online Class"` |

## Reactive State

No reactive refs or state (all logic derived from props via computed).

## UI Description

- **Card Container** — white rounded shadow wrapper with relative positioning for the badge overlay.
- **Header Row** — icon (GraduationCap) + title + class ID badge + vertical ellipsis menu trigger.
- **Details List** — rows for Lesson, Building, Room, Status (with conditional monitor icon), Days, Time.
- **Students Row** — count / capacity text label and a grey progress bar filled with indigo according to utilisation.
- **Action Button** — full-width "View Class" blue button at the bottom.

## Navigation

No internal navigation. The "View Class" button is presentational (no handler wired).

## Future Improvements

- Wire "View Class" button to route `/dashboard/students/{id}`
- Add `EllipsisVertical` dropdown menu (edit, delete)
- Emit events for parent consumption
- Skeleton loading placeholder
- Tooltip on capacity bar
- Responsive font scaling on small cards
- Drag-to-reorder support
- Inline editing of capacity

## Example Usage

```vue
<ClassCard
  :classData="{
    id: 101,
    title: 'Web Design + React.js',
    lesson: 'Bootstrap',
    building: 'Building B',
    floor: 'Floor 1',
    room: 'ETEC B102',
    status: 'Physical Class',
    term: 'Mon & Thu',
    time: '09:00 am - 10:30 am',
    students: 8,
    capacity: 20,
  }"
  :count="5"
/>
```
