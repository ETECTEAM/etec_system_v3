<script setup>
import { ref } from 'vue'
import { Head } from '@inertiajs/vue3'
import { Download, Search, Trash2 } from '@lucide/vue'
import { Breadcrumbs } from '../../../components/ui/breadcrumbs'
import { EmptyState } from '../../../components/ui/empty-state'
import { PageHero } from '../../../components/ui/page-hero'
import { Pagination } from '../../../components/ui/pagination'
import { SelectSearch } from '../../../components/ui/select-search'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../../components/ui/table'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'
import StatusBadge from './components/StatusBadge.vue'
import { useLeaveHistory } from './composables/useLeaveHistory'

const props = defineProps({
  classes: {
    type: Array,
    default: () => [],
  },
  canDelete: {
    type: Boolean,
    default: false,
  },
})

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Official Leave', href: '/dashboard/official-leaves' },
  { label: 'Leave History', current: true },
]

const {
  search,
  statusFilter,
  classFilter,
  dateFrom,
  dateTo,
  leaves,
  pagination,
  isLoading,
  hasLoaded,
  busyId,
  fetchLeaves,
  exportCsv,
  approve,
  reject,
  revoke,
  remove,
  canDecide,
  canRevoke,
} = useLeaveHistory({ canDelete: props.canDelete })

const statusOptions = [
  { value: 'pending', label: 'Pending' },
  { value: 'approved', label: 'Approved' },
  { value: 'rejected', label: 'Rejected' },
  { value: 'revoked', label: 'Revoked' },
]

const classOptions = props.classes.map((c) => ({ value: String(c.id), label: c.title }))

// The one row being rejected keeps its note in here so the table stays stateless.
const rejectingNote = ref('')
const rejectingId = ref(null)

function startReject(leave) {
  rejectingId.value = leave.id
  rejectingNote.value = ''
}

function confirmReject(leave) {
  reject(leave, rejectingNote.value)
  rejectingId.value = null
}

function formatDateTime(value) {
  if (!value) return '-'

  return new Date(value).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' })
}
</script>

<template>
  <Head :title="$t('Leave History')" />

  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <PageHero
          eyebrow="Official Leave"
          :title="$t('Leave History')"
          :description="$t('Every official leave request with its decision trail.')"
        />

        <button
          type="button"
          class="inline-flex h-10 shrink-0 items-center gap-2 rounded-lg bg-slate-700 px-4 text-sm font-bold text-white transition hover:bg-slate-800"
          @click="exportCsv"
        >
          <Download class="h-4 w-4" />
          {{ $t('Export CSV') }}
        </button>
      </div>

      <!-- Filters -->
      <div class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:grid-cols-2 lg:grid-cols-5">
        <div class="relative lg:col-span-2">
          <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
          <input
            v-model="search"
            type="text"
            :placeholder="$t('Search by student name or ID...')"
            class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-9 pr-3 text-sm font-semibold outline-none transition focus:border-blue-400 focus:ring-4 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
          />
        </div>

        <SelectSearch v-model="statusFilter" :options="statusOptions" :placeholder="$t('All statuses')" />

        <SelectSearch v-model="classFilter" :options="classOptions" :placeholder="$t('All classes')" searchable />

        <div class="flex items-center gap-2">
          <input
            v-model="dateFrom"
            type="date"
            class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold outline-none transition focus:border-blue-400 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
          />
          <span class="text-xs font-black text-slate-400">–</span>
          <input
            v-model="dateTo"
            type="date"
            class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-xs font-semibold outline-none transition focus:border-blue-400 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
          />
        </div>
      </div>

      <!-- Table -->
      <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div v-if="isLoading && !hasLoaded" class="space-y-3 p-6">
          <div v-for="i in 5" :key="i" class="h-12 animate-pulse rounded-lg bg-slate-100 dark:bg-gray-800" />
        </div>

        <EmptyState
          v-else-if="hasLoaded && !leaves.length"
          :title="'No leave records'"
          :description="'No official leave matches the current filters.'"
        />

        <Table v-else>
          <TableHeader>
            <TableRow>
              <TableHead class="w-56">{{ $t('Student') }}</TableHead>
              <TableHead>{{ $t('Class / Course') }}</TableHead>
              <TableHead class="text-center">{{ $t('Dates') }}</TableHead>
              <TableHead>{{ $t('Reason') }}</TableHead>
              <TableHead class="text-center">{{ $t('Status') }}</TableHead>
              <TableHead class="text-center">{{ $t('Requested at') }}</TableHead>
              <TableHead class="text-center">{{ $t('Approved by') }}</TableHead>
              <TableHead class="text-right">{{ $t('Actions') }}</TableHead>
            </TableRow>
          </TableHeader>

          <TableBody>
            <TableRow v-for="leave in leaves" :key="leave.id">
              <TableCell class="font-bold text-slate-900 dark:text-gray-100">{{ leave.student.full_name }} <span class="font-mono text-xs font-semibold text-slate-400">#{{ leave.student.id }}</span></TableCell>
              <TableCell class="max-w-44 truncate text-sm font-semibold text-slate-600 dark:text-gray-300" :title="(leave.classes ?? []).join(', ') || ''">{{ leave.classes?.length ? leave.classes.join(', ') : (leave.course ?? '-') }}</TableCell>
              <TableCell class="whitespace-nowrap text-center text-sm font-semibold text-slate-600 dark:text-gray-300">{{ leave.start_date }} → {{ leave.end_date }}<span class="block text-[11px] font-medium text-slate-400">{{ leave.days }}d</span></TableCell>
              <TableCell class="max-w-52"><p class="truncate text-sm italic text-slate-600 dark:text-gray-300" :title="leave.reason">“{{ leave.reason }}”</p></TableCell>
              <TableCell class="text-center"><StatusBadge :status="leave.status" size="sm" /></TableCell>
              <TableCell class="whitespace-nowrap text-center text-xs font-semibold text-slate-500 dark:text-gray-400">{{ formatDateTime(leave.requested_at) }}</TableCell>
              <TableCell class="text-center text-sm font-semibold text-slate-600 dark:text-gray-300">{{ leave.approved_by ?? '-' }}</TableCell>
              <TableCell class="text-right">
                <div class="flex flex-wrap items-center justify-end gap-1.5">
                  <template v-if="canDecide(leave)">
                    <button
                      type="button"
                      :disabled="busyId === leave.id"
                      class="h-8 rounded-lg bg-emerald-600 px-3 text-xs font-bold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
                      @click="approve(leave)"
                    >
                      {{ $t('Approve') }}
                    </button>

                    <button
                      v-if="rejectingId !== leave.id"
                      type="button"
                      :disabled="busyId === leave.id"
                      class="h-8 rounded-lg border border-rose-200 bg-rose-50 px-3 text-xs font-bold text-rose-700 transition hover:bg-rose-100 disabled:cursor-not-allowed disabled:opacity-60 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300"
                      @click="startReject(leave)"
                    >
                      {{ $t('Reject') }}
                    </button>
                  </template>

                  <template v-if="rejectingId === leave.id">
                    <input
                      v-model="rejectingNote"
                      type="text"
                      :placeholder="$t('Short rejection note (required)')"
                      class="h-8 w-40 rounded-lg border border-slate-200 bg-white px-2 text-xs font-semibold outline-none focus:border-blue-400 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                      @keydown.enter.prevent="confirmReject(leave)"
                    />
                    <button
                      type="button"
                      class="h-8 rounded-lg bg-rose-600 px-3 text-xs font-bold text-white transition hover:bg-rose-700"
                      @click="confirmReject(leave)"
                    >
                      {{ $t('Confirm Reject') }}
                    </button>
                    <button
                      type="button"
                      class="h-8 rounded-lg bg-slate-100 px-2 text-xs font-bold text-slate-600 transition hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-300"
                      @click="rejectingId = null"
                    >
                      ✕
                    </button>
                  </template>

                  <button
                    v-if="canRevoke(leave)"
                    type="button"
                    :disabled="busyId === leave.id"
                    class="h-8 rounded-lg border border-amber-200 bg-amber-50 px-3 text-xs font-bold text-amber-700 transition hover:bg-amber-100 disabled:cursor-not-allowed disabled:opacity-60 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300"
                    @click="revoke(leave)"
                  >
                    {{ $t('Revoke') }}
                  </button>

                  <button
                    v-if="canDelete && (leave.status === 'pending' || leave.status === 'rejected')"
                    type="button"
                    :disabled="busyId === leave.id"
                    class="inline-flex h-8 items-center gap-1 rounded-lg bg-slate-100 px-2 text-xs font-bold text-slate-600 transition hover:bg-rose-50 hover:text-rose-700 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-gray-800 dark:text-gray-300"
                    @click="remove(leave)"
                  >
                    <Trash2 class="h-3.5 w-3.5" />
                  </button>
                </div>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>

        <div v-if="pagination.last_page > 1" class="border-t border-slate-100 py-4 dark:border-gray-800">
          <Pagination
            :current-page="pagination.current_page"
            :last-page="pagination.last_page"
            :disabled="isLoading"
            @page-change="fetchLeaves"
          />
        </div>
      </div>
    </section>
  </DashboardLayout>
</template>
