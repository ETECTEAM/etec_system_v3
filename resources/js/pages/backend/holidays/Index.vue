<script setup>
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Breadcrumbs from '@/components/ui/breadcrumbs/Breadcrumbs.vue'
import PageHero from '@/components/ui/page-hero/PageHero.vue'
import { router, useForm } from '@inertiajs/vue3'
import { CalendarOff, ChevronLeft, ChevronRight, Pencil, Plus, Trash2, X } from '@lucide/vue'
import { computed, ref } from 'vue'
import { useToast } from 'vue-toastification'
import { useConfirm } from '@/composables/useConfirm'
import { useI18n } from '@/i18n'

const props = defineProps({
  month: { type: String, required: true },
  today: { type: String, required: true },
  yearRange: { type: Object, default: () => ({ start: null, end: null }) },
  holidays: { type: Array, default: () => [] },
  holidayDates: { type: Array, default: () => [] },
})

const { t } = useI18n()
const toast = useToast()
const { confirm } = useConfirm()

const showDialog = ref(false)
const editingGroupId = ref(null)
const markedDate = ref(null)
const todayValue = props.today

const form = useForm({
  name: '',
  start_date: '',
  end_date: '',
  description: '',
})

const holidayByDate = computed(() => {
  return props.holidayDates.reduce((dates, holiday) => {
    dates[holiday.date] = holiday
    return dates
  }, {})
})

const monthDate = computed(() => localDate(`${props.month}-01`))
const monthLabel = computed(() => monthDate.value.toLocaleDateString('en-US', { month: 'long', year: 'numeric' }))
const selectedMonth = computed(() => String(monthDate.value.getMonth() + 1).padStart(2, '0'))
const selectedYear = computed(() => String(monthDate.value.getFullYear()))
const monthOptions = [
  { value: '01', label: 'January' },
  { value: '02', label: 'February' },
  { value: '03', label: 'March' },
  { value: '04', label: 'April' },
  { value: '05', label: 'May' },
  { value: '06', label: 'June' },
  { value: '07', label: 'July' },
  { value: '08', label: 'August' },
  { value: '09', label: 'September' },
  { value: '10', label: 'October' },
  { value: '11', label: 'November' },
  { value: '12', label: 'December' },
]
const yearOptions = computed(() => {
  const currentYear = new Date().getFullYear()
  const activeYear = Number(selectedYear.value)
  const rangeStart = Number(props.yearRange?.start)
  const rangeEnd = Number(props.yearRange?.end)
  const start = Number.isFinite(rangeStart) ? rangeStart : Math.min(currentYear, activeYear) - 5
  const end = Number.isFinite(rangeEnd) ? rangeEnd : Math.max(currentYear, activeYear) + 5

  return Array.from({ length: end - start + 1 }, (_, index) => String(start + index))
})

const calendarDays = computed(() => {
  const first = localDate(`${props.month}-01`)
  const start = new Date(first)
  start.setDate(1 - first.getDay())

  return Array.from({ length: 42 }, (_, index) => {
    const date = new Date(start)
    date.setDate(start.getDate() + index)
    const value = formatLocalDate(date)

    return {
      value,
      day: date.getDate(),
      isCurrentMonth: date.getMonth() === first.getMonth(),
      holiday: holidayByDate.value[value] ?? null,
      isMarked: markedDate.value === value,
      isToday: value === todayValue,
    }
  })
})

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Holiday Management', current: true },
]

function localDate(value) {
  const [year, month, day] = value.split('-').map(Number)
  return new Date(year, month - 1, day)
}

function formatLocalDate(date) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

function changeMonth(offset) {
  const next = new Date(monthDate.value)
  next.setMonth(next.getMonth() + offset)
  goToMonth(formatLocalDate(next).slice(0, 7))
}

function goToMonth(month) {
  router.get('/dashboard/holidays', { month }, { preserveState: true, preserveScroll: true })
}

function changeSelectedMonth(month) {
  goToMonth(`${selectedYear.value}-${month}`)
}

function changeSelectedYear(year) {
  goToMonth(`${year}-${selectedMonth.value}`)
}

function goToToday() {
  markedDate.value = todayValue
  goToMonth(todayValue.slice(0, 7))
}

function openCreate() {
  if (!markedDate.value) {
    toast.info(t('Select a date first.'))
    return
  }

  editingGroupId.value = null
  form.defaults({
    name: '',
    start_date: markedDate.value,
    end_date: markedDate.value,
    description: '',
  })
  form.reset()
  form.clearErrors()
  showDialog.value = true
}

function openEdit(holiday) {
  editingGroupId.value = holiday.group_id
  markedDate.value = holiday.start_date
  form.name = holiday.name
  form.start_date = holiday.start_date
  form.end_date = holiday.end_date
  form.description = holiday.description ?? ''
  form.clearErrors()
  showDialog.value = true
}

function markDate(day) {
  if (day.holiday) {
    toast.info(t('This date is already a holiday. Use the list to edit it.'))
    return
  }

  markedDate.value = day.value
}

function clearMarkedDate() {
  markedDate.value = null
}

function saveHoliday() {
  const options = {
    preserveScroll: true,
    onSuccess: () => {
      showDialog.value = false
      markedDate.value = null
      toast.success(t(editingGroupId.value ? 'Holiday updated.' : 'Holiday saved.'))
    },
    onError: () => toast.error(t('Please fix the validation errors.')),
  }

  if (editingGroupId.value) {
    form.put(`/dashboard/holidays/${editingGroupId.value}`, options)
    return
  }

  form.post('/dashboard/holidays', options)
}

async function deleteHoliday(holiday) {
  const ok = await confirm({
    title: t('Delete holiday?'),
    message: t('This only removes the holiday setting. Existing attendance records are preserved.'),
    confirmText: t('Delete'),
    cancelText: t('Cancel'),
    danger: true,
  })

  if (!ok) return

  router.delete(`/dashboard/holidays/${holiday.group_id}`, {
    preserveScroll: true,
    onSuccess: () => toast.success(t('Holiday deleted.')),
  })
}

function dateRange(holiday) {
  if ((holiday.dates?.length ?? 0) > 0 && holiday.dates.length <= 3) {
    return holiday.dates.join(', ')
  }

  if ((holiday.dates?.length ?? 0) > 3) {
    return `${holiday.dates[0]} -> ${holiday.dates[holiday.dates.length - 1]} (${holiday.dates.length} dates)`
  }

  if (holiday.start_date === holiday.end_date) return holiday.start_date
  return `${holiday.start_date} -> ${holiday.end_date}`
}
</script>

<template>
  <DashboardLayout>
    <section class="space-y-4 sm:space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />

      <div class="flex flex-col items-stretch gap-3 sm:flex-row sm:flex-wrap sm:items-start sm:justify-between">
        <PageHero
          eyebrow="Attendance"
          :title="$t('Holiday Management')"
          :description="$t('Manage non-teaching days so attendance automation skips those dates.')"
        />
        <button type="button" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 sm:w-auto" @click="openCreate">
          <Plus class="h-4 w-4 shrink-0" />
          {{ $t('Add Holiday') }}
        </button>
      </div>

      <div class="flex flex-col gap-2 rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm font-semibold text-blue-800 dark:border-blue-900/40 dark:bg-blue-900/20 dark:text-blue-200 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
          <span v-if="markedDate">{{ $t('Marked date') }}: {{ markedDate }}</span>
          <span v-else>{{ $t('Click a date on the calendar, then click Add Holiday.') }}</span>
        </div>
        <button v-if="markedDate" type="button" class="shrink-0 rounded-lg border border-blue-200 bg-white px-3 py-1.5 text-xs font-bold text-blue-700 hover:bg-blue-100 dark:border-blue-800 dark:bg-blue-950/40 dark:text-blue-200" @click="clearMarkedDate">
          {{ $t('Clear mark') }}
        </button>
      </div>

      <div class="grid gap-4 sm:gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
        <div class="min-w-0 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
          <div class="flex flex-col gap-3 border-b border-slate-200 px-3 py-3 dark:border-gray-800 sm:px-4">
            <div class="flex items-center justify-between gap-2">
              <button type="button" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800" :aria-label="$t('Previous')" @click="changeMonth(-1)">
                <ChevronLeft class="h-4 w-4" />
              </button>
              <h2 class="min-w-0 flex-1 truncate text-center text-sm font-semibold text-slate-900 dark:text-gray-100 sm:text-base">{{ monthLabel }}</h2>
              <button type="button" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800" :aria-label="$t('Next')" @click="changeMonth(1)">
                <ChevronRight class="h-4 w-4" />
              </button>
            </div>
            <div class="flex flex-wrap items-center justify-center gap-2">
              <select :value="selectedMonth" class="h-9 min-w-0 flex-1 rounded-lg border border-slate-300 bg-white px-2 text-xs text-slate-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 sm:flex-none sm:px-3 sm:text-sm" @change="changeSelectedMonth($event.target.value)">
                <option v-for="option in monthOptions" :key="option.value" :value="option.value">{{ $t(option.label) }}</option>
              </select>
              <select :value="selectedYear" class="h-9 min-w-0 flex-1 rounded-lg border border-slate-300 bg-white px-2 text-xs text-slate-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 sm:flex-none sm:px-3 sm:text-sm" @change="changeSelectedYear($event.target.value)">
                <option v-for="year in yearOptions" :key="year" :value="year">{{ year }}</option>
              </select>
              <button type="button" class="h-9 shrink-0 rounded-lg border border-blue-200 bg-blue-50 px-3 text-xs font-semibold text-blue-700 hover:bg-blue-100 dark:border-blue-900/40 dark:bg-blue-900/20 dark:text-blue-300 sm:text-sm" @click="goToToday">
                {{ $t('Today') }}
              </button>
            </div>
          </div>

          <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-50 text-center text-[10px] font-semibold uppercase text-slate-500 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-400 sm:text-xs">
            <div v-for="dayName in ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']" :key="dayName" class="px-1 py-2 sm:px-2">{{ $t(dayName) }}</div>
          </div>

          <div class="grid grid-cols-7">
            <button
              v-for="day in calendarDays"
              :key="day.value"
              type="button"
              class="min-h-16 border-b border-r border-slate-100 p-1 text-left transition hover:bg-blue-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-slate-500 dark:border-gray-800 dark:hover:bg-gray-800 sm:min-h-20 sm:p-2 md:min-h-24"
              :class="[
                !day.isCurrentMonth ? 'bg-slate-50 text-slate-400 dark:bg-gray-950 dark:text-gray-600' : 'text-slate-800 dark:text-gray-200',
                day.holiday ? 'bg-rose-50 dark:bg-rose-950/20' : '',
                day.isMarked ? 'ring-2 ring-inset ring-blue-500' : '',
                day.isToday ? 'ring-2 ring-inset ring-emerald-500' : '',
              ]"
              @click="markDate(day)"
            >
              <span class="flex items-center justify-between gap-1">
                <span
                  class="inline-flex h-6 min-w-6 items-center justify-center rounded-full px-1.5 text-xs font-semibold sm:h-7 sm:min-w-7 sm:px-2 sm:text-sm"
                  :class="[
                    day.holiday ? 'bg-rose-600 text-white shadow-sm dark:bg-rose-500' : '',
                    day.isMarked && !day.holiday ? 'bg-blue-600 text-white shadow-sm dark:bg-blue-500' : '',
                  ]"
                >
                  {{ day.day }}
                </span>
                <span v-if="day.isToday" class="hidden rounded-full bg-emerald-100 px-1.5 py-0.5 text-[9px] font-bold uppercase text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 sm:inline-flex sm:px-2 sm:text-[10px]">{{ $t('Today') }}</span>
              </span>
              <span v-if="day.holiday" class="mt-1 flex max-w-full items-center gap-1 rounded bg-rose-100 px-1 py-0.5 text-[10px] font-medium text-rose-700 dark:bg-rose-900/40 dark:text-rose-200 sm:mt-2 sm:px-2 sm:py-1 sm:text-xs">
                <CalendarOff class="h-3 w-3 shrink-0" />
                <span class="truncate">{{ day.holiday.name }}</span>
              </span>
            </button>
          </div>
        </div>

        <div class="min-w-0 rounded-lg border border-slate-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
          <div class="border-b border-slate-200 px-4 py-3 dark:border-gray-800">
            <h2 class="text-sm font-semibold text-slate-900 dark:text-gray-100">{{ $t('Holidays') }}</h2>
          </div>
          <div class="divide-y divide-slate-100 dark:divide-gray-800">
            <div v-for="holiday in holidays" :key="holiday.group_id" class="p-3 sm:p-4">
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <p class="truncate text-sm font-semibold text-slate-900 dark:text-gray-100">{{ holiday.name }}</p>
                  <p class="mt-1 break-words text-xs text-slate-500 dark:text-gray-400">{{ dateRange(holiday) }}</p>
                  <p v-if="holiday.description" class="mt-2 line-clamp-2 text-xs text-slate-600 dark:text-gray-300">{{ holiday.description }}</p>
                </div>
                <div class="flex shrink-0 gap-1">
                  <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800" :aria-label="$t('Edit')" @click="openEdit(holiday)">
                    <Pencil class="h-4 w-4" />
                  </button>
                  <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-red-200 bg-red-50 text-red-600 hover:bg-red-100 dark:border-red-900/40 dark:bg-red-900/20 dark:text-red-300" :aria-label="$t('Delete')" @click="deleteHoliday(holiday)">
                    <Trash2 class="h-4 w-4" />
                  </button>
                </div>
              </div>
            </div>
            <div v-if="!holidays.length" class="px-4 py-10 text-center text-sm text-slate-500 dark:text-gray-400">
              {{ $t('No holidays in this month.') }}
            </div>
          </div>
        </div>
      </div>
    </section>

    <transition name="fade">
      <div v-if="showDialog" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-3 backdrop-blur-sm sm:p-4" @click.self="showDialog = false">
        <div class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-lg bg-white shadow-lg dark:bg-gray-900">
          <div class="sticky top-0 flex items-center justify-between border-b border-slate-200 bg-white px-4 py-3 dark:border-gray-800 dark:bg-gray-900 sm:px-5 sm:py-4">
            <h3 class="text-base font-semibold text-slate-900 dark:text-gray-100">{{ editingGroupId ? $t('Edit Holiday') : $t('Add Holiday') }}</h3>
            <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 dark:text-gray-400 dark:hover:bg-gray-800" :aria-label="$t('Cancel')" @click="showDialog = false">
              <X class="h-4 w-4" />
            </button>
          </div>

          <form class="space-y-4 p-4 sm:p-5" @submit.prevent="saveHoliday">
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-gray-300">{{ $t('Holiday name') }}</label>
              <input v-model="form.name" type="text" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
              <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-gray-300">{{ $t('Start date') }}</label>
                <input v-model="form.start_date" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                <p v-if="form.errors.start_date" class="mt-1 text-xs text-red-600">{{ form.errors.start_date }}</p>
                <p v-if="form.errors.dates" class="mt-1 text-xs text-red-600">{{ form.errors.dates }}</p>
              </div>
              <div>
                <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-gray-300">{{ $t('End date') }}</label>
                <input v-model="form.end_date" type="date" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100">
                <p v-if="form.errors.end_date" class="mt-1 text-xs text-red-600">{{ form.errors.end_date }}</p>
              </div>
            </div>

            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-gray-300">{{ $t('Description') }}</label>
              <textarea v-model="form.description" rows="3" class="w-full resize-none rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100" />
              <p v-if="form.errors.description" class="mt-1 text-xs text-red-600">{{ form.errors.description }}</p>
            </div>

            <div class="flex flex-col-reverse justify-end gap-2 pt-2 sm:flex-row sm:gap-3">
              <button type="button" class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm dark:border-gray-700 dark:text-gray-300 sm:w-auto" @click="showDialog = false">{{ $t('Cancel') }}</button>
              <button type="submit" :disabled="form.processing" class="w-full rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60 sm:w-auto">{{ form.processing ? $t('Saving...') : $t('Save') }}</button>
            </div>
          </form>
        </div>
      </div>
    </transition>
  </DashboardLayout>
</template>
