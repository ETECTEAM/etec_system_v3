<script setup>
import { computed, ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { BarChart3, CalendarClock, GraduationCap, RefreshCw } from '@lucide/vue'
import { Breadcrumbs } from '../../../components/ui/breadcrumbs'
import { EmptyState } from '../../../components/ui/empty-state'
import { PageHero } from '../../../components/ui/page-hero'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'
import { useI18n } from '@/i18n'

const props = defineProps({
  monthly: {
    type: Array,
    default: () => [],
  },
  perCourse: {
    type: Array,
    default: () => [],
  },
  topPermissionUsers: {
    type: Array,
    default: () => [],
  },
  onLeaveToday: {
    type: Array,
    default: () => [],
  },
})

const { t } = useI18n()

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Official Leave', href: '/dashboard/official-leaves' },
  { label: 'Reports', current: true },
]

const maxMonthly = computed(() => Math.max(1, ...props.monthly.map((m) => m.count)))
const maxCourse = computed(() => Math.max(1, ...props.perCourse.map((c) => c.count)))

// Quota watchlist bar width — capped at quota so over-quota doesn't overflow.
function quotaWidth(used, quota) {
  return `${Math.min(100, Math.round((used / Math.max(1, quota)) * 100))}%`
}

function quotaTone(used, quota) {
  if (used >= quota) return 'bg-rose-500'

  if (used >= Math.ceil(quota / 2)) return 'bg-amber-500'

  return 'bg-emerald-500'
}

const refreshing = ref(false)

async function refresh() {
  refreshing.value = true

  try {
    await router.reload({ only: ['monthly', 'perCourse', 'topPermissionUsers', 'onLeaveToday'] })
  } finally {
    refreshing.value = false
  }
}
</script>

<template>
  <Head :title="$t('Leave Reports')" />

  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />

      <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <PageHero
          eyebrow="Official Leave"
          :title="$t('Reports & Stats')"
          :description="$t('Approved leave trends, quota watchlist, and who is off today.')"
        />

        <button
          type="button"
          :disabled="refreshing"
          class="inline-flex h-10 shrink-0 items-center gap-2 rounded-lg bg-slate-700 px-4 text-sm font-bold text-white transition hover:bg-slate-800 disabled:opacity-60"
          @click="refresh"
        >
          <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': refreshing }" />
          {{ $t('Refresh') }}
        </button>
      </div>

      <!-- On leave today -->
      <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:p-6">
        <h2 class="flex items-center gap-2 text-sm font-black uppercase tracking-[0.14em] text-slate-500 dark:text-gray-400">
          <CalendarClock class="h-4 w-4" />
          {{ $t('On approved leave today') }}
        </h2>

        <EmptyState
          v-if="!onLeaveToday.length"
          class="mt-4"
          :icon="CalendarClock"
          :title="'Nobody on leave today'"
          :description="'No student has an approved official leave covering today.'"
        />

        <ul v-else class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          <li
            v-for="entry in onLeaveToday"
            :key="entry.leave_id"
            class="rounded-xl border border-violet-200 bg-violet-50 p-4 dark:border-violet-500/20 dark:bg-violet-500/10"
          >
            <p class="font-black text-slate-950 dark:text-gray-100">{{ entry.full_name }}</p>
            <p class="mt-0.5 text-xs font-semibold text-slate-500 dark:text-gray-400">{{ entry.class ?? $t('All classes') }}</p>
            <p class="mt-1 text-xs font-bold text-violet-700 dark:text-violet-300">{{ entry.start_date }} → {{ entry.end_date }}</p>
          </li>
        </ul>
      </div>

      <div class="grid gap-6 lg:grid-cols-2">
        <!-- Monthly trend -->
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:p-6">
          <h2 class="flex items-center gap-2 text-sm font-black uppercase tracking-[0.14em] text-slate-500 dark:text-gray-400">
            <BarChart3 class="h-4 w-4" />
            {{ $t('Approved leaves per month') }}
          </h2>

          <EmptyState v-if="!monthly.length" class="mt-4" :icon="BarChart3" :title="'No data yet'" />

          <div v-else class="mt-5 flex h-44 items-end gap-2 overflow-x-auto pb-1">
            <div v-for="month in monthly" :key="month.month" class="flex min-w-9 flex-1 flex-col items-center gap-1">
              <span class="text-[11px] font-black text-slate-500 dark:text-gray-400">{{ month.count }}</span>
              <div
                class="w-full rounded-t-md bg-blue-600 transition-all dark:bg-blue-500"
                :style="{ height: `${Math.max(4, (month.count / maxMonthly) * 130)}px` }"
                :title="`${month.label}: ${month.count}`"
              />
              <span class="whitespace-nowrap text-[10px] font-bold uppercase text-slate-400">{{ month.label.slice(0, 3) }}</span>
            </div>
          </div>
        </div>

        <!-- Per course breakdown -->
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:p-6">
          <h2 class="flex items-center gap-2 text-sm font-black uppercase tracking-[0.14em] text-slate-500 dark:text-gray-400">
            <GraduationCap class="h-4 w-4" />
            {{ $t('Leaves by class / course') }}
          </h2>

          <EmptyState v-if="!perCourse.length" class="mt-4" :icon="GraduationCap" :title="'No data yet'" />

          <ul v-else class="mt-5 space-y-3">
            <li v-for="row in perCourse" :key="row.label">
              <div class="flex items-center justify-between gap-4 text-sm font-semibold text-slate-600 dark:text-gray-300">
                <span class="truncate">{{ row.label }}</span>
                <span class="shrink-0 font-black">{{ row.count }}</span>
              </div>
              <div class="mt-1 h-2.5 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-gray-800">
                <div class="h-full rounded-full bg-indigo-500" :style="{ width: `${(row.count / maxCourse) * 100}%` }" />
              </div>
            </li>
          </ul>
        </div>
      </div>

      <!-- Quota watchlist -->
      <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:p-6">
        <h2 class="flex items-center gap-2 text-sm font-black uppercase tracking-[0.14em] text-slate-500 dark:text-gray-400">
          <BarChart3 class="h-4 w-4" />
          {{ $t('Top permission usage this month') }}
        </h2>

        <EmptyState
          v-if="!topPermissionUsers.length"
          class="mt-4"
          :icon="BarChart3"
          :title="'No permissions used yet this month'"
        />

        <ul v-else class="mt-5 grid gap-3 md:grid-cols-2">
          <li
            v-for="(student, index) in topPermissionUsers"
            :key="student.id"
            class="rounded-xl border border-slate-200 p-4 dark:border-gray-700"
          >
            <div class="flex items-center justify-between gap-3 text-sm">
              <span class="truncate font-bold text-slate-900 dark:text-gray-100">#{{ index + 1 }} · {{ student.full_name }}</span>
              <span class="shrink-0 font-black tabular-nums" :class="student.used >= student.quota ? 'text-rose-600' : 'text-slate-500 dark:text-gray-400'">
                {{ student.used }}/{{ student.quota }}
              </span>
            </div>

            <div class="mt-2 h-2.5 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-gray-800">
              <div class="h-full rounded-full transition-all" :class="quotaTone(student.used, student.quota)" :style="{ width: quotaWidth(student.used, student.quota) }" />
            </div>
          </li>
        </ul>
      </div>
    </section>
  </DashboardLayout>
</template>
