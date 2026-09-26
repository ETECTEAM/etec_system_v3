<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import flatpickr from 'flatpickr'
import 'flatpickr/dist/flatpickr.min.css'
import { CalendarDays } from '@lucide/vue'

// Themed date picker (flatpickr). Takes and gives 'YYYY-MM-DD' strings, the same
// value an <input type="date"> uses, so it is a drop-in replacement for one.
const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  placeholder: {
    type: String,
    default: 'Select date',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  // Classes for the visible text box (flatpickr creates it as a sibling of our <input>).
  inputClass: {
    type: String,
    default: '',
  },
  minDate: {
    type: String,
    default: undefined,
  },
  maxDate: {
    type: String,
    default: undefined,
  },
})

const emit = defineEmits(['update:modelValue'])

const input = ref(null)
let picker = null

// "Today" shortcut under the calendar; flatpickr has no built-in one.
function addFooter(_selectedDates, _dateStr, instance) {
  const footer = document.createElement('div')
  footer.className = 'flatpickr-footer'

  const today = document.createElement('button')
  today.type = 'button'
  today.className = 'flatpickr-today-btn'
  today.textContent = 'Today'
  today.addEventListener('click', () => {
    instance.setDate(new Date(), true)
    instance.close()
  })

  footer.appendChild(today)
  instance.calendarContainer.appendChild(footer)
}

onMounted(() => {
  picker = flatpickr(input.value, {
    defaultDate: props.modelValue || null,
    dateFormat: 'Y-m-d',
    altInput: true,
    altFormat: 'j M Y',
    altInputClass: props.inputClass,
    minDate: props.minDate,
    maxDate: props.maxDate,
    monthSelectorType: 'dropdown',
    disableMobile: true,
    onChange: (_dates, dateStr) => emit('update:modelValue', dateStr),
    onReady: addFooter,
  })

  if (picker.altInput) {
    picker.altInput.placeholder = props.placeholder
    picker.altInput.disabled = props.disabled
  }
})

watch(() => props.modelValue, (value) => {
  if (picker && (value || '') !== picker.input.value) {
    picker.setDate(value || null, false)
  }
})

watch(() => props.disabled, (disabled) => {
  if (picker?.altInput) {
    picker.altInput.disabled = disabled
  }
})

onBeforeUnmount(() => {
  picker?.destroy()
  picker = null
})
</script>

<template>
  <div class="relative w-full">
    <input ref="input" type="text" />
    <CalendarDays class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500 dark:text-gray-400" />
  </div>
</template>

<style>
/* flatpickr renders its calendar in <body>, so this theme is global on purpose. */
.flatpickr-calendar {
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  background: #fff;
  box-shadow: 0 20px 45px rgba(15, 23, 42, .18);
  font-family: inherit;
  overflow: hidden;
}

.flatpickr-calendar.arrowTop::before,
.flatpickr-calendar.arrowTop::after,
.flatpickr-calendar.arrowBottom::before,
.flatpickr-calendar.arrowBottom::after {
  display: none;
}

.flatpickr-months {
  padding: 10px 6px 4px;
}

.flatpickr-months .flatpickr-prev-month,
.flatpickr-months .flatpickr-next-month {
  top: 10px;
  height: 32px;
  width: 32px;
  padding: 0;
  display: grid;
  place-items: center;
  border-radius: 10px;
  color: #475569;
  transition: background-color .15s ease;
}

.flatpickr-months .flatpickr-prev-month { left: 10px; }
.flatpickr-months .flatpickr-next-month { right: 10px; }

.flatpickr-months .flatpickr-prev-month svg,
.flatpickr-months .flatpickr-next-month svg {
  width: 12px;
  height: 12px;
  fill: currentColor;
}

.flatpickr-calendar .flatpickr-months .flatpickr-prev-month:hover,
.flatpickr-calendar .flatpickr-months .flatpickr-next-month:hover {
  background: #eef0ff;
  color: #2d2e83;
}

.flatpickr-calendar .flatpickr-months .flatpickr-prev-month:hover svg,
.flatpickr-calendar .flatpickr-months .flatpickr-next-month:hover svg {
  fill: currentColor;
}

.flatpickr-current-month {
  font-size: 15px;
  font-weight: 700;
  color: #0f172a;
}

.flatpickr-current-month .flatpickr-monthDropdown-months,
.flatpickr-current-month input.cur-year {
  color: inherit;
  font-weight: 700;
}

.flatpickr-current-month .flatpickr-monthDropdown-months:hover,
.flatpickr-current-month .numInputWrapper:hover {
  background: #f1f5f9;
}

.flatpickr-monthDropdown-months option {
  background: #fff;
  color: #0f172a;
}

span.flatpickr-weekday {
  color: #64748b;
  font-size: 12px;
  font-weight: 700;
}

.flatpickr-day {
  height: 36px;
  /* Keep flatpickr's 39px: 7 cells fill the 307px row, an 8th must not fit. */
  max-width: 39px;
  border: 0;
  border-radius: 10px;
  color: #0f172a;
  font-weight: 500;
  line-height: 36px;
}

.flatpickr-day:hover,
.flatpickr-day:focus {
  border: 0;
  background: #eef0ff;
}

.flatpickr-day.prevMonthDay,
.flatpickr-day.nextMonthDay {
  color: #94a3b8;
}

.flatpickr-day.flatpickr-disabled,
.flatpickr-day.flatpickr-disabled:hover {
  background: transparent;
  color: #cbd5e1;
}

.flatpickr-day.today:not(.selected) {
  border: 0;
  background: transparent;
  box-shadow: inset 0 0 0 1.5px #2d2e83;
}

.flatpickr-day.today:not(.selected):hover {
  background: #eef0ff;
  color: #0f172a;
}

.flatpickr-day.selected,
.flatpickr-day.selected:hover,
.flatpickr-day.selected:focus {
  border: 0;
  background: #2d2e83;
  color: #fff;
  box-shadow: 0 6px 14px rgba(45, 46, 131, .3);
}

.flatpickr-footer {
  display: flex;
  justify-content: flex-end;
  padding: 6px 12px 10px;
  border-top: 1px solid #eef1f6;
}

.flatpickr-today-btn {
  border: 0;
  border-radius: 8px;
  background: transparent;
  color: #2d2e83;
  padding: 6px 12px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
}

.flatpickr-today-btn:hover {
  background: #eef0ff;
}

/* Dark theme */
.dark .flatpickr-calendar {
  border-color: #263244;
  background: #111827;
  box-shadow: 0 24px 55px rgba(0, 0, 0, .55);
}

.dark .flatpickr-months .flatpickr-prev-month,
.dark .flatpickr-months .flatpickr-next-month {
  color: #cbd5e1;
}

.dark .flatpickr-calendar .flatpickr-months .flatpickr-prev-month:hover,
.dark .flatpickr-calendar .flatpickr-months .flatpickr-next-month:hover {
  background: rgba(148, 163, 184, .14);
  color: #fff;
}

.dark .flatpickr-current-month,
.dark .flatpickr-day {
  color: #e5e7eb;
}

.dark .flatpickr-current-month .flatpickr-monthDropdown-months:hover,
.dark .flatpickr-current-month .numInputWrapper:hover {
  background: rgba(148, 163, 184, .14);
}

.dark .flatpickr-monthDropdown-months option {
  background: #111827;
  color: #e5e7eb;
}

.dark .numInputWrapper span.arrowUp:after {
  border-bottom-color: #cbd5e1;
}

.dark .numInputWrapper span.arrowDown:after {
  border-top-color: #cbd5e1;
}

.dark span.flatpickr-weekday {
  color: #94a3b8;
}

.dark .flatpickr-day:hover,
.dark .flatpickr-day:focus {
  background: rgba(148, 163, 184, .16);
}

.dark .flatpickr-day.prevMonthDay,
.dark .flatpickr-day.nextMonthDay {
  color: #64748b;
}

.dark .flatpickr-day.flatpickr-disabled,
.dark .flatpickr-day.flatpickr-disabled:hover {
  background: transparent;
  color: #475569;
}

.dark .flatpickr-day.today:not(.selected) {
  box-shadow: inset 0 0 0 1.5px #60a5fa;
}

.dark .flatpickr-day.today:not(.selected):hover {
  background: rgba(148, 163, 184, .16);
  color: #e5e7eb;
}

.dark .flatpickr-day.selected,
.dark .flatpickr-day.selected:hover,
.dark .flatpickr-day.selected:focus {
  background: #3b82f6;
  color: #fff;
  box-shadow: 0 6px 14px rgba(59, 130, 246, .35);
}

.dark .flatpickr-footer {
  border-top-color: #263244;
}

.dark .flatpickr-today-btn {
  color: #93c5fd;
}

.dark .flatpickr-today-btn:hover {
  background: rgba(148, 163, 184, .14);
}
</style>
