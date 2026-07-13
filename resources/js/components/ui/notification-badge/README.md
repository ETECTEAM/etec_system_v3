# NotificationBadge

## Purpose

A small floating badge component that displays a notification count. Positioned absolutely at the top-right corner of its parent container. Includes entry and increment animations for a polished UX.

## Features

- Displays notification count (shows `99+` when count exceeds 99)
- Hidden when count is zero or falsy
- Scale-in entrance animation
- Pulse animation when count increases
- Absolute positioning (top-right) with border and shadow
- Small, reusable, single-responsibility component

## Props

| Prop | Type | Required | Description |
|------|------|----------|-------------|
| `count` | `Number` | No (default `0`) | The number to display. Hidden when `<= 0`, caps display at `99+` |

## Emits

No Emits.

## Dependencies

- `vue` — `computed`, `ref`, `watch`

## Methods

No user-defined methods.

## Computed Properties

| Name | Description |
|------|-------------|
| `displayCount` | Returns `"99+"` if `count > 99`, otherwise the raw `count` value |
| `isVisible` | Boolean — `true` when `count > 0` |

## Reactive State

| Name | Type | Description |
|------|------|-------------|
| `pulsing` | `ref(false)` | Toggled `true` for 700ms when count increases, enabling the pulse animation class |

## UI Description

- **Positioning** — Absolutely positioned `-top-2 -right-2` relative to the nearest positioned ancestor.
- **Appearance** — Red background, white text, rounded-full, white border, shadow, `z-50`.
- **Entry Animation** — `.animate-scale-in` (200ms ease-out scale from 0.8 to 1.0) plays on mount.
- **Increment Animation** — `.animate-pulse-custom` (600ms ease-out scale bounce) triggers when count rises.

## Navigation

No navigation.

## Future Improvements

- Accept a `max` prop to customise the cap threshold
- Expose a `dot` mode (show red dot without number)
- Click / dismiss handler (emit event)
- Slot for custom content
- Transition group support for list-based notifications

## Example Usage

```vue
<NotificationBadge :count="notificationCount" />
```

Wrap with a relative container:

```vue
<div class="relative">
  <SomeIcon />
  <NotificationBadge :count="3" />
</div>
```
