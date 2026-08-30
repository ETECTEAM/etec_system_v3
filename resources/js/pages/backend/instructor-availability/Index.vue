<script setup>
import { computed } from 'vue'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { Card } from '@/components/ui/card'
import { PageHero } from '@/components/ui/page-hero'
import { Breadcrumbs } from '@/components/ui/breadcrumbs'
import { EmptyState } from '@/components/ui/empty-state'

const props = defineProps({
  slots: {
    type: Array,
    default: () => [],
  },
  instructors: {
    type: Array,
    default: () => [],
  },
})

const DAY_LABELS = { 1: 'Mon', 2: 'Tue', 3: 'Wed', 4: 'Thu', 5: 'Fri', 6: 'Sat', 7: 'Sun' }
const WEEK_DAYS = [1, 2, 3, 4, 5, 6, 7]

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Instructor Busy Time', current: true },
]

const totalBusySlots = computed(() =>
  props.instructors.reduce(
    (sum, instructor) =>
      sum + instructor.days.reduce((daySum, day) => daySum + day.slots.filter((s) => s.status !== 'free').length, 0),
    0,
  ),
)

const dayLabel = (day) => DAY_LABELS[day] ?? day

const slotTone = (slot) => (slot.status === 'block' ? 'block' : 'class')
</script>

<template>
  <DashboardLayout>
    <div class="w-full">
      <Breadcrumbs :items="breadcrumbItems" class="mb-4" />

      <PageHero
        eyebrow="Instructor"
        :title="$t('Instructor Busy Time')"
        :description="$t('Weekly availability grid: which time slots every instructor is free or busy teaching.')"
        class="mb-6"
      />

      <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
        <Card padding="p-4">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500">{{ $t('Active Instructors') }}</p>
          <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-gray-100">{{ instructors.length }}</p>
        </Card>
        <Card padding="p-4">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-gray-500">{{ $t('Total Busy Slots') }}</p>
          <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-gray-100">{{ totalBusySlots }}</p>
        </Card>
      </div>

      <div class="mb-4 flex flex-wrap items-center gap-4 rounded-2xl border border-slate-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
        <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-gray-400">
          <span class="inline-block h-3 w-3 rounded-sm bg-rose-500"></span>
          {{ $t('Teaching (class)') }}
        </div>
        <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-gray-400">
          <span class="inline-block h-3 w-3 rounded-sm bg-amber-500"></span>
          {{ $t('Blocked') }}
        </div>
        <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-gray-400">
          <span class="inline-block h-3 w-3 rounded-sm bg-emerald-500/30"></span>
          {{ $t('Free') }}
        </div>
      </div>

      <Card padding="p-0">
        <div class="overflow-x-auto">
          <table class="w-full min-w-[1100px] border-collapse">
            <thead>
              <tr class="border-b border-slate-200 dark:border-gray-800">
                <th class="sticky left-0 z-10 bg-slate-50 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-gray-900 dark:text-gray-400">
                  {{ $t('Instructor') }}
                </th>
                <th
                    v-for="day in WEEK_DAYS"
                    :key="day"
                    class="border-l border-slate-100 px-3 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500 dark:border-gray-800 dark:text-gray-400"
                >
                  {{ dayLabel(day) }}
                </th>
              </tr>
            </thead>
            <tbody>
              <tr
                  v-for="instructor in instructors"
                  :key="instructor.id"
                  class="border-b border-slate-100 dark:border-gray-800 last:border-0"
              >
                <td class="sticky left-0 z-10 bg-white px-4 py-3 align-top dark:bg-gray-900">
                  <p class="text-sm font-medium text-slate-900 dark:text-gray-100">{{ instructor.full_name }}</p>
                  <p class="text-xs text-slate-400 dark:text-gray-500">{{ instructor.email }}</p>
                </td>
                <td
                    v-for="day in instructor.days"
                    :key="day.day_of_week"
                    class="border-l border-slate-100 px-1.5 py-2 align-top dark:border-gray-800"
                >
                  <div
                      v-if="day.is_free"
                      class="flex min-h-[2rem] items-center justify-center rounded-lg bg-emerald-50 text-xs font-medium text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400"
                  >
                    {{ $t('Free') }}
                  </div>
                  <div v-else class="flex flex-col gap-1">
                    <span
                        v-for="(slot, index) in day.slots"
                        :key="index"
                        :class="slotTone(slot) === 'block'
                          ? 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300'
                          : 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300'"
                        class="rounded-md px-1.5 py-1 text-[11px] leading-tight"
                        :title="slot.title"
                    >
                      <span class="font-semibold">{{ slot.time_name }}</span>
                      <span v-if="slot.title" class="ml-0.5 opacity-80">{{ slot.title }}</span>
                    </span>
                  </div>
                </td>
              </tr>

              <tr v-if="instructors.length === 0">
                <td colspan="8">
                  <EmptyState
                      class="py-16"
                      :title="$t('No instructors found')"
                      :description="$t('There are no active instructors to display availability for.')"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </Card>
    </div>
  </DashboardLayout>
</template>
