<script setup>
import axios from 'axios'
import { computed, ref } from 'vue'
import { useToast } from 'vue-toastification'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { Card } from '@/components/ui/card'
import { PageHero } from '@/components/ui/page-hero'
import { Breadcrumbs } from '@/components/ui/breadcrumbs'
import { EmptyState } from '@/components/ui/empty-state'
import { useConfirm } from '@/composables/useConfirm'
import { useI18n } from '@/i18n'
import { Search } from '@lucide/vue'

const props = defineProps({
  instructors: {
    type: Array,
    default: () => [],
  },
})

const { t } = useI18n()
const toast = useToast()
const { confirm } = useConfirm()

const WEEK_DAYS = [
  { day: 1, label: 'Mon' },
  { day: 2, label: 'Tue' },
  { day: 3, label: 'Wed' },
  { day: 4, label: 'Thu' },
  { day: 5, label: 'Fri' },
  { day: 6, label: 'Sat' },
  { day: 7, label: 'Sun' },
]

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Instructor Busy Time', current: true },
]

const instructors = ref(props.instructors ?? [])
const searchQuery = ref('')
// key = `${instructorId}:${day}:${timeId}` while that slot's request is in flight
const pendingSlot = ref(null)
const pendingInstructor = ref(null)

const blockModal = ref(null)
const blockReason = ref('')
const isBlocking = ref(false)

const totalBusySlots = computed(() =>
  filteredInstructors.value.reduce(
    (sum, instructor) =>
      sum +
      instructor.days.reduce(
        (daySum, day) => daySum + day.slots.filter((s) => s.status === 'class' || s.status === 'block').length,
        0,
      ),
    0,
  ),
)

const filteredInstructors = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  if (!q) return instructors.value
  return instructors.value.filter(
    (instructor) =>
      (instructor.full_name ?? '').toLowerCase().includes(q) ||
      (instructor.email ?? '').toLowerCase().includes(q),
  )
})

const slotKey = (instructor, day, slot) => `${instructor.id}:${day.day_of_week}:${slot.time_id}`
const isPending = (instructor, day, slot) => pendingSlot.value === slotKey(instructor, day, slot)

// Full "09:00 am - 10:30 am", squeezed: drop the leading zero and the first
// meridiem when both ends share it, so overlapping slots that share a start
// time ("09:00 am - 10:30 am" vs "09:00 am - 11:00 am") stay distinguishable
// without widening the column.
function slotLabel(slot) {
  const parts = (slot.time_name ?? '').split(' - ')
  if (parts.length !== 2) return slot.time_name ?? ''

  const [a, b] = parts.map((p) => p.trim())
  const am = /\b(am|pm)$/i
  const mA = a.match(am)?.[1]?.toLowerCase()
  const mB = b.match(am)?.[1]?.toLowerCase()
  const strip = (s) => s.replace(/^0/, '').replace(am, '').trim()

  return mA && mA === mB ? `${strip(a)}–${strip(b)} ${mB}` : `${a.replace(/^0/, '')} – ${b.replace(/^0/, '')}`
}
const slotStart = (slot) => (slot.time_name ?? '').split(' - ')[0]?.replace(/^0/, '') ?? slot.time_name

function slotClasses(slot) {
  switch (slot.status) {
    case 'class':
      return 'cursor-not-allowed bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300'
    case 'block':
      return [
        'bg-amber-100 text-amber-800 hover:bg-amber-200 dark:bg-amber-500/15 dark:text-amber-300 dark:hover:bg-amber-500/25',
        slot.blocked_by === 'admin' ? 'ring-1 ring-inset ring-amber-500/60' : '',
      ]
    case 'available':
      return slot.availability_source === 'admin'
        ? 'bg-emerald-100 text-emerald-700 ring-1 ring-inset ring-indigo-400/70 hover:bg-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-300'
        : 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-300 dark:hover:bg-emerald-500/25'
    default:
      return 'bg-slate-100 text-slate-400 hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-500 dark:hover:bg-gray-700'
  }
}

function slotTitle(slot) {
  switch (slot.status) {
    case 'class':
      return t('Teaching: :title', { title: slot.title || t('class') })
    case 'block':
      return `${slot.blocked_by === 'admin' ? t('Blocked by admin') : t('Blocked by instructor')}${
        slot.title ? ` — ${slot.title}` : ''
      } · ${t('click to unblock')}`
    case 'available':
      return slot.availability_source === 'admin'
        ? t('Opened by admin · click to close')
        : t('Available · click to block')
    default:
      return t('Not working · click to open')
  }
}

async function refresh() {
  const { data } = await axios.get('/dashboard/instructor-availability/data')
  instructors.value = data.instructors ?? []
}

function readError(error, fallback) {
  return t(error.response?.data?.message ?? fallback)
}

function openBlockModal(instructor, day, slot) {
  blockModal.value = {
    instructor_id: instructor.id,
    instructor_name: instructor.full_name,
    day_of_week: day.day_of_week,
    day_label: day.day_label,
    time_id: slot.time_id,
    time_name: slot.time_name,
  }
  blockReason.value = ''
}

function closeBlockModal() {
  blockModal.value = null
  blockReason.value = ''
}

async function submitBlock() {
  if (!blockModal.value) return
  isBlocking.value = true

  try {
    await axios.post('/dashboard/instructor-availability/block', {
      instructor_id: blockModal.value.instructor_id,
      day_of_week: blockModal.value.day_of_week,
      time_id: blockModal.value.time_id,
      reason: blockReason.value || null,
    })
    await refresh()
    toast.success(t('Slot blocked.'))
    closeBlockModal()
  } catch (error) {
    toast.error(readError(error, 'Failed to block this slot. Please try again.'))
  } finally {
    isBlocking.value = false
  }
}

async function runSlotMutation(instructor, day, slot, request, successMessage, fallbackError) {
  pendingSlot.value = slotKey(instructor, day, slot)

  try {
    await request()
    await refresh()
    toast.success(t(successMessage))
  } catch (error) {
    toast.error(readError(error, fallbackError))
  } finally {
    pendingSlot.value = null
  }
}

async function onSlotClick(instructor, day, slot) {
  if (slot.status === 'class' || isPending(instructor, day, slot)) return

  // Available, from the work schedule → block it (needs a reason).
  if (slot.status === 'available' && slot.availability_source !== 'admin') {
    openBlockModal(instructor, day, slot)
    return
  }

  // Available, opened by an admin → close it again.
  if (slot.status === 'available' && slot.availability_source === 'admin') {
    const ok = await confirm({
      title: t('Close this slot?'),
      message: t(':time on :day goes back to "Not Working" for :name.', {
        time: slotStart(slot),
        day: day.day_label,
        name: instructor.full_name,
      }),
      confirmText: t('Close'),
      danger: true,
    })
    if (!ok) return

    await runSlotMutation(
      instructor,
      day,
      slot,
      () => axios.delete(`/dashboard/instructor-availability/open/${slot.availability_id}`),
      'Slot closed.',
      'Failed to close this slot. Please try again.',
    )
    return
  }

  // Blocked → unblock it.
  if (slot.status === 'block') {
    const ok = await confirm({
      title: t('Unblock this slot?'),
      message: t(':time on :day becomes available again for :name.', {
        time: slotStart(slot),
        day: day.day_label,
        name: instructor.full_name,
      }),
      confirmText: t('Unblock'),
      danger: true,
    })
    if (!ok) return

    await runSlotMutation(
      instructor,
      day,
      slot,
      () => axios.delete(`/dashboard/instructor-availability/block/${slot.block_id}`),
      'Slot unblocked.',
      'Failed to unblock this slot. Please try again.',
    )
    return
  }

  // Not working → open it outside the instructor's normal schedule.
  await runSlotMutation(
    instructor,
    day,
    slot,
    () =>
      axios.post('/dashboard/instructor-availability/open', {
        instructor_id: instructor.id,
        day_of_week: day.day_of_week,
        time_id: slot.time_id,
      }),
    'Slot opened.',
    'Failed to open this slot. Please try again.',
  )
}

async function toggleInstructor(instructor) {
  const next = !instructor.available_for_class

  if (!next) {
    const ok = await confirm({
      title: t('Take :name off class assignment?', { name: instructor.full_name }),
      message: t('They will not be offered for any new class until this is turned back on.'),
      confirmText: t('Turn off'),
      danger: true,
    })
    if (!ok) return
  }

  pendingInstructor.value = instructor.id

  try {
    const { data } = await axios.patch(`/dashboard/instructor-availability/instructor/${instructor.id}`, {
      available_for_class: next,
    })
    instructor.available_for_class = data.available_for_class
    toast.success(instructor.available_for_class ? t('Instructor is available for class.') : t('Instructor taken off class assignment.'))
  } catch (error) {
    toast.error(readError(error, 'Failed to update the instructor. Please try again.'))
  } finally {
    pendingInstructor.value = null
  }
}
</script>

<template>
  <DashboardLayout>
    <div class="w-full">
      <Breadcrumbs :items="breadcrumbItems" class="mb-4" />

      <PageHero
        eyebrow="Instructor"
        :title="$t('Instructor Busy Time')"
        :description="$t('Weekly grid of every time slot per instructor. Click a slot to block, unblock, open or close it.')"
        class="mb-6"
      />

      <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
        <Card padding="p-4">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500">{{ $t('Active Instructors') }}</p>
          <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-gray-100">{{ filteredInstructors.length }}</p>
        </Card>
        <Card padding="p-4">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500">{{ $t('Total Busy Slots') }}</p>
          <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-gray-100">{{ totalBusySlots }}</p>
        </Card>
      </div>

      <div class="mb-4 flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex flex-wrap items-center gap-4 text-xs text-slate-500 dark:text-gray-400">
        <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-gray-400">
          <span class="inline-block h-3 w-3 rounded-sm bg-rose-500"></span>
          {{ $t('Teaching (class)') }}
        </div>
        <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-gray-400">
          <span class="inline-block h-3 w-3 rounded-sm bg-amber-500"></span>
          {{ $t('Blocked') }}
        </div>
        <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-gray-400">
          <span class="inline-block h-3 w-3 rounded-sm bg-emerald-500/40"></span>
          {{ $t('Available') }}
        </div>
        <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-gray-400">
          <span class="inline-block h-3 w-3 rounded-sm bg-emerald-500/40 ring-1 ring-inset ring-indigo-400"></span>
          {{ $t('Opened by admin') }}
        </div>
        <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-gray-400">
          <span class="inline-block h-3 w-3 rounded-sm bg-slate-300 dark:bg-gray-700"></span>
          {{ $t('Not Working') }}
        </div>
        </div>

        <div class="relative w-full lg:w-72">
          <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-gray-500" />
          <input
            v-model="searchQuery"
            type="text"
            :placeholder="$t('Search instructors...')"
            class="w-full rounded-xl border border-slate-300 py-2.5 pl-9 pr-3 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
          >
        </div>
      </div>

      <Card padding="p-0">
        <div class="overflow-x-auto">
          <table class="w-full min-w-[1360px] border-collapse">
            <thead>
              <tr class="border-b border-slate-200 dark:border-gray-800">
                <th class="sticky left-0 z-10 bg-slate-50 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-gray-900 dark:text-gray-400">
                  {{ $t('Instructor') }}
                </th>
                <th
                    v-for="d in WEEK_DAYS"
                    :key="d.day"
                    class="border-l border-slate-100 px-3 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500 dark:border-gray-800 dark:text-gray-400"
                >
                  {{ $t(d.label) }}
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                  v-for="instructor in filteredInstructors"
                  :key="instructor.id"
                  class="border-b border-slate-100 dark:border-gray-800 last:border-0"
              >
                <td class="sticky left-0 z-10 bg-white px-4 py-3 align-top dark:bg-gray-900">
                  <p class="text-sm font-medium text-slate-900 dark:text-gray-100">{{ instructor.full_name }}</p>
                  <p class="text-xs text-slate-400 dark:text-gray-500">{{ instructor.email }}</p>

                  <button
                      type="button"
                      role="switch"
                      :aria-checked="instructor.available_for_class"
                      :disabled="pendingInstructor === instructor.id"
                      class="mt-2 inline-flex items-center gap-1.5 text-[11px] font-medium disabled:opacity-50"
                      @click="toggleInstructor(instructor)"
                  >
                    <span
                        class="relative inline-flex h-4 w-7 shrink-0 items-center rounded-full transition"
                        :class="instructor.available_for_class ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-gray-600'"
                    >
                      <span
                          class="inline-block h-3 w-3 transform rounded-full bg-white transition"
                          :class="instructor.available_for_class ? 'translate-x-3.5' : 'translate-x-0.5'"
                      ></span>
                    </span>
                    <span :class="instructor.available_for_class ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-gray-500'">
                      {{ instructor.available_for_class ? $t('Available for class') : $t('Not available') }}
                    </span>
                  </button>
                </td>

                <td
                    v-for="day in instructor.days"
                    :key="day.day_of_week"
                    class="border-l border-slate-100 px-1 py-1.5 align-top dark:border-gray-800"
                >
                  <div v-if="day.slots.length === 0" class="px-1.5 py-2 text-center text-[10px] text-slate-300 dark:text-gray-600">
                    {{ $t('No schedule') }}
                  </div>
                  <div v-else class="flex flex-col gap-0.5">
                    <button
                        v-for="slot in day.slots"
                        :key="slot.time_id"
                        type="button"
                        :disabled="slot.status === 'class' || isPending(instructor, day, slot)"
                        :class="slotClasses(slot)"
                        class="flex items-center gap-0.5 whitespace-nowrap rounded px-1.5 py-1 text-left text-[10px] leading-tight transition disabled:opacity-60"
                        :title="slotTitle(slot)"
                        @click="onSlotClick(instructor, day, slot)"
                    >
                      <span class="font-semibold tabular-nums">{{ slotLabel(slot) }}</span>
                      <span v-if="slot.status === 'class' && slot.title" class="truncate opacity-80">{{ slot.title }}</span>
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-if="filteredInstructors.length === 0">
                <td colspan="8">
                  <EmptyState
                      class="py-16"
                      :title="$t('No instructors found')"
                      :description="searchQuery
                        ? $t('No instructors match your search.')
                        : $t('There are no active instructors to display availability for.')"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </Card>
    </div>

    <div
        v-if="blockModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 px-4"
        @click.self="closeBlockModal"
    >
      <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-gray-100">{{ $t('Block this slot') }}</h3>
        <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
          {{ blockModal.instructor_name }} &middot; {{ blockModal.day_label }} &middot; {{ blockModal.time_name }}
        </p>

        <label class="mt-4 block">
          <span class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">{{ $t('Reason') }}</span>
          <textarea
              v-model="blockReason"
              rows="3"
              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
              :placeholder="$t('e.g. Meeting, admin work, personal')"
          ></textarea>
        </label>

        <div class="mt-6 flex justify-end gap-3">
          <button
              type="button"
              class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
              @click="closeBlockModal"
          >
            {{ $t('Cancel') }}
          </button>
          <button
              type="button"
              :disabled="isBlocking"
              class="rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-700 disabled:cursor-not-allowed disabled:opacity-50"
              @click="submitBlock"
          >
            {{ isBlocking ? $t('Blocking...') : $t('Block Slot') }}
          </button>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
