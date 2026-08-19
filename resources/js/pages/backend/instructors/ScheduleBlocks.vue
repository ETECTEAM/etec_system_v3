<script setup>
import axios from 'axios'
import { Head } from '@inertiajs/vue3'
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import { Breadcrumbs } from '../../../components/ui/breadcrumbs'
import { PageHero } from '../../../components/ui/page-hero'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'
import { Ban, X, CalendarOff } from '@lucide/vue'
import { useConfirm } from '../../../composables/useConfirm'
import { useI18n } from '@/i18n'

const props = defineProps({
  instructorName: { type: String, default: '' },
  workSchedule: { type: Object, default: null },
  workingDaysLabel: { type: String, default: '' },
  workingHours: { type: String, default: '' },
})

const { t } = useI18n()
const toast = useToast()
const { confirm } = useConfirm()

const schedule = ref([])
const isLoading = ref(false)
const hasLoaded = ref(false)
const savingKey = ref(null)

const blockModal = ref(null)
const blockReason = ref('')
const isBlocking = ref(false)

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'My Availability', current: true },
]

onMounted(() => {
  fetchSchedule()
})

async function fetchSchedule() {
  isLoading.value = true

  try {
    const response = await axios.get('/dashboard/instructor-schedule-blocks/data')
    schedule.value = response.data.schedule ?? []
  } catch (error) {
    console.error('Failed to fetch schedule', error)
    toast.error(t('Failed to load your schedule. Please try again.'))
  } finally {
    hasLoaded.value = true
    isLoading.value = false
  }
}

const maxSlotRows = computed(() => {
  if (schedule.value.length === 0) return 0
  return Math.max(...schedule.value.map((d) => d.slots.length), 1)
})

const statusConfig = {
  available: {
    card: 'border-emerald-200 bg-emerald-50 dark:border-emerald-500/20 dark:bg-emerald-500/10',
    badge: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-400',
    label: 'Available',
  },
  not_working: {
    card: 'border-slate-200 bg-slate-50 dark:border-gray-700 dark:bg-gray-800/50',
    badge: 'bg-slate-100 text-slate-500 dark:bg-gray-700 dark:text-gray-400',
    label: 'Outside Shift',
  },
  blocked: {
    card: 'border-red-200 bg-red-50 dark:border-red-500/20 dark:bg-red-500/10',
    badge: 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-400',
    label: 'Unavailable',
  },
}

function formatTimeRange(timeName) {
  const match = timeName?.match(/\(([^)]+)\)/)
  if (match) return match[1]
  return timeName ?? ''
}

function openBlockModal(day, slot) {
  blockModal.value = {
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
    const response = await axios.post('/dashboard/instructor-schedule-blocks', {
      day_of_week: blockModal.value.day_of_week,
      time_id: blockModal.value.time_id,
      reason: blockReason.value || null,
    })

    applySlotUpdate(blockModal.value.day_of_week, blockModal.value.time_id, {
      status: 'blocked',
      block_id: response.data.id,
      reason: response.data.reason,
    })

    toast.success(t('Slot blocked.'))
    closeBlockModal()
  } catch (error) {
    console.error('Failed to block slot', error)
    toast.error(t(error.response?.data?.message ?? 'Failed to block this slot. Please try again.'))
  } finally {
    isBlocking.value = false
  }
}

async function unblockSlot(day, slot) {
  const ok = await confirm({
    title: t('Remove this block?'),
    message: t('This makes :time on :day available again.', { time: formatTimeRange(slot.time_name), day: day.day_label }),
    confirmText: t('Unblock'),
    danger: true,
  })

  if (!ok) return

  const key = `${day.day_of_week}:${slot.time_id}`
  savingKey.value = key

  try {
    await axios.delete(`/dashboard/instructor-schedule-blocks/block/${slot.block_id}`)

    applySlotUpdate(day.day_of_week, slot.time_id, {
      status: 'available',
      block_id: null,
      reason: null,
    })

    toast.success(t('Slot unblocked.'))
  } catch (error) {
    console.error('Failed to unblock slot', error)
    toast.error(t('Failed to unblock this slot. Please try again.'))
  } finally {
    savingKey.value = null
  }
}

function applySlotUpdate(dayOfWeek, timeId, changes) {
  const day = schedule.value.find((d) => d.day_of_week === dayOfWeek)
  const slot = day?.slots.find((s) => s.time_id === timeId)

  if (slot) {
    Object.assign(slot, changes)
  }
}
</script>

<template>
  <Head :title="$t('My Availability')" />

  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero
        eyebrow="My Availability"
        :title="$t('My Availability')"
        :description="$t('View your weekly schedule and block time slots when you are not available for class assignment.')"
      />

      <!-- Instructor info card -->
      <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
          <div class="space-y-1">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">{{ $t('Instructor') }}</h2>
            <p class="text-base font-semibold text-slate-900 dark:text-gray-100">{{ instructorName }}</p>
          </div>

          <div v-if="workSchedule" class="space-y-1">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">{{ $t('Work Schedule') }}</h2>
            <p class="text-base font-semibold text-slate-900 dark:text-gray-100">{{ workSchedule.name }}</p>
          </div>

          <div v-if="workingDaysLabel" class="space-y-1">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">{{ $t('Working') }}</h2>
            <p class="text-sm text-slate-700 dark:text-gray-300">
              <span class="font-semibold">{{ workingDaysLabel }}</span>
              <span v-if="workingHours" class="ml-2 text-slate-500 dark:text-gray-400">{{ workingHours }}</span>
            </p>
          </div>
        </div>
      </div>

      <!-- Empty state -->
      <p v-if="hasLoaded && schedule.length === 0" class="rounded-xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500 dark:border-gray-700 dark:text-gray-400">
        {{ $t('You have no working schedule yet.') }}
      </p>

      <!-- Weekly calendar -->
      <div v-else-if="schedule.length > 0" class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="overflow-x-auto">
          <table class="w-full min-w-[900px] border-collapse">
            <thead>
              <tr>
                <th
                  v-for="day in schedule"
                  :key="day.day_of_week"
                  class="border-b border-slate-200 px-3 py-3 text-center dark:border-gray-800"
                >
                  <span class="text-sm font-bold uppercase tracking-wider text-slate-600 dark:text-gray-300">
                    {{ day.day_label }}
                  </span>
                </th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="rowIndex in maxSlotRows" :key="rowIndex">
                <td
                  v-for="day in schedule"
                  :key="`${day.day_of_week}-${rowIndex}`"
                  class="border-b border-slate-100 px-2 py-2 align-top last:border-b-0 dark:border-gray-800"
                >
                  <!-- Non-working day: show OFF -->
                  <div v-if="!day.is_working" class="flex flex-col items-center justify-center py-6">
                    <CalendarOff class="mb-2 h-6 w-6 text-slate-300 dark:text-gray-600" />
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-gray-500">OFF</span>
                    <span class="mt-1 text-[11px] text-slate-400 dark:text-gray-600">{{ $t('No working schedule') }}</span>
                  </div>

                  <!-- Working day: show slot card -->
                  <div
                    v-else-if="day.slots[rowIndex - 1]"
                    class="rounded-lg border p-2.5 transition"
                    :class="statusConfig[day.slots[rowIndex - 1].status]?.card"
                  >
                    <p class="text-xs font-semibold text-slate-700 dark:text-gray-200">
                      {{ formatTimeRange(day.slots[rowIndex - 1].time_name) }}
                    </p>

                    <span
                      class="mt-1.5 inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide"
                      :class="statusConfig[day.slots[rowIndex - 1].status]?.badge"
                    >
                      {{ $t(statusConfig[day.slots[rowIndex - 1].status]?.label) }}
                    </span>

                    <p
                      v-if="day.slots[rowIndex - 1].status === 'blocked' && day.slots[rowIndex - 1].reason"
                      class="mt-1 text-[11px] text-slate-500 dark:text-gray-400"
                    >
                      {{ day.slots[rowIndex - 1].reason }}
                    </p>

                    <div class="mt-2">
                      <button
                        v-if="day.slots[rowIndex - 1].status === 'available'"
                        type="button"
                        class="inline-flex w-full items-center justify-center gap-1 rounded-md border border-dashed border-red-300 px-2 py-1 text-[11px] font-semibold text-red-600 transition hover:bg-red-50 dark:border-red-500/40 dark:text-red-400 dark:hover:bg-red-500/10"
                        @click="openBlockModal(day, day.slots[rowIndex - 1])"
                      >
                        <Ban class="h-3 w-3" />
                        {{ $t('Block') }}
                      </button>

                      <button
                        v-else-if="day.slots[rowIndex - 1].status === 'blocked'"
                        type="button"
                        :disabled="savingKey === `${day.day_of_week}:${day.slots[rowIndex - 1].time_id}`"
                        class="inline-flex w-full items-center justify-center gap-1 rounded-md border border-slate-300 px-2 py-1 text-[11px] font-semibold text-slate-600 transition hover:bg-slate-50 disabled:opacity-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                        @click="unblockSlot(day, day.slots[rowIndex - 1])"
                      >
                        {{ $t('Unblock') }}
                      </button>
                    </div>
                  </div>

                  <!-- Empty cell for non-working days in rows below -->
                  <div v-else-if="!day.is_working" class="py-6" />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- Block modal -->
    <div
      v-if="blockModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 px-4"
      @click.self="closeBlockModal"
    >
      <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900">
        <div class="flex items-start justify-between gap-3">
          <div>
            <h3 class="text-lg font-semibold text-slate-900 dark:text-gray-100">{{ $t('Block this slot') }}</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">{{ blockModal.day_label }} &middot; {{ formatTimeRange(blockModal.time_name) }}</p>
          </div>
          <button
            type="button"
            :title="$t('Close')"
            class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-gray-800 dark:hover:text-gray-300"
            @click="closeBlockModal"
          >
            <X class="h-4 w-4" />
          </button>
        </div>

        <label class="mt-4 block">
          <span class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-gray-300">{{ $t('Reason') }}</span>
          <textarea
            v-model="blockReason"
            rows="3"
            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
            :placeholder="t('e.g. Old system class, Personal schedule, Administrative work')"
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
            class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-50"
            @click="submitBlock"
          >
            {{ isBlocking ? $t('Blocking...') : $t('Block Slot') }}
          </button>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
