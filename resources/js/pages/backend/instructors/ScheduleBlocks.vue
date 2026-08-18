<script setup>
import axios from 'axios'
import { Head } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import { Breadcrumbs } from '../../../components/ui/breadcrumbs'
import { PageHero } from '../../../components/ui/page-hero'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'
import { Ban, X } from '@lucide/vue'
import { useConfirm } from '../../../composables/useConfirm'
import { useI18n } from '@/i18n'

const props = defineProps({
  instructorName: { type: String, default: '' },
})

const { t } = useI18n()
const toast = useToast()
const { confirm } = useConfirm()

const schedule = ref([])
const isLoading = ref(false)
const hasLoaded = ref(false)
const savingKey = ref(null)

// The slot currently open in the "Block this slot" modal, or null. Only ever
// an 'available' slot - blocked/not_working slots use different actions.
const blockModal = ref(null)
const blockReason = ref('')
const isBlocking = ref(false)

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Busy Time', current: true },
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

const statusStyles = {
  available: 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-400',
  not_working: 'border-slate-200 bg-slate-50 text-slate-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400',
  blocked: 'border-red-200 bg-red-50 text-red-700 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-400',
}

const statusLabels = {
  available: 'Available',
  not_working: 'Not Working',
  blocked: 'Manual Blocked',
}

function openBlockModal(day, slot) {
  blockModal.value = { day_of_week: day.day_of_week, day_label: day.day_label, time_id: slot.time_id, time_name: slot.time_name }
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
    message: t('This makes :time on :day available again.', { time: slot.time_name, day: day.day_label }),
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
  <Head :title="$t('Busy Time')" />

  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero eyebrow="My Schedule" :title="$t('Busy Time')" :description="$t('Block specific time slots when you are not available for class assignment.')" />

      <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="mb-4">
          <h2 class="text-sm font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">{{ $t('Instructor') }}</h2>
          <p class="mt-1 text-base font-semibold text-slate-900 dark:text-gray-100">{{ instructorName }}</p>
        </div>

        <div class="mt-6">
          <p v-if="hasLoaded && schedule.length === 0" class="rounded-xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500 dark:border-gray-700 dark:text-gray-400">
            {{ $t('You have no working schedule yet.') }}
          </p>

          <div v-for="(day, dayIndex) in schedule" :key="day.day_of_week" :class="dayIndex > 0 ? 'mt-8' : ''">
            <h3 class="mb-2 text-base font-semibold text-slate-900 dark:text-gray-100">{{ day.day_label }}</h3>

            <div class="divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200 dark:divide-gray-800 dark:border-gray-800">
              <div v-for="slot in day.slots" :key="slot.time_id" class="flex flex-wrap items-center justify-between gap-3 bg-white px-4 py-3 dark:bg-gray-900">
                <div>
                  <p class="text-sm font-medium text-slate-700 dark:text-gray-300">{{ slot.time_name }}</p>
                  <p v-if="slot.status === 'blocked' && slot.reason" class="mt-0.5 text-xs text-slate-400 dark:text-gray-500">{{ slot.reason }}</p>
                </div>

                <div class="flex items-center gap-2">
                  <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold" :class="statusStyles[slot.status]">
                    {{ $t(statusLabels[slot.status]) }}
                  </span>

                  <button
                    v-if="slot.status === 'available'"
                    type="button"
                    class="inline-flex items-center gap-1 rounded-lg border border-dashed border-red-300 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-50 dark:border-red-500/40 dark:text-red-400 dark:hover:bg-red-500/10"
                    @click="openBlockModal(day, slot)"
                  >
                    <Ban class="h-3.5 w-3.5" />
                    {{ $t('Block') }}
                  </button>

                  <button
                    v-else-if="slot.status === 'blocked'"
                    type="button"
                    :disabled="savingKey === `${day.day_of_week}:${slot.time_id}`"
                    class="inline-flex items-center gap-1 rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 disabled:opacity-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
                    @click="unblockSlot(day, slot)"
                  >
                    {{ $t('Unblock') }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <div
      v-if="blockModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 px-4"
      @click.self="closeBlockModal"
    >
      <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900">
        <div class="flex items-start justify-between gap-3">
          <div>
            <h3 class="text-lg font-semibold text-slate-900 dark:text-gray-100">{{ $t('Block this slot') }}</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">{{ blockModal.day_label }} · {{ blockModal.time_name }}</p>
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
