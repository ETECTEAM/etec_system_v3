<script setup>
import { computed, reactive } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { Search, CheckCircle2 } from '@lucide/vue'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { Pagination } from '@/components/ui/pagination'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'

const props = defineProps({
  locks: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  summary: {
    type: Object,
    default: () => ({}),
  },
  settings: {
    type: Object,
    default: () => ({}),
  },
})

const form = reactive({
  search: props.filters.search ?? '',
})

const rows = computed(() => props.locks?.data ?? [])

function submit(page = 1) {
  router.get('/dashboard/student-management/locks', { ...form, page }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}

function clearSearch() {
  form.search = ''
  submit(1)
}

function rowNumber(index) {
  return (((props.locks.current_page ?? 1) - 1) * (props.locks.per_page ?? 15)) + index + 1
}

function formatDate(value) {
  if (!value) return '—'
  return new Date(value).toLocaleDateString(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

function studentMeta(student) {
  return `Abs: ${student?.absence_count ?? 0} | Per: ${student?.permission_count ?? 0}`
}

function permissionBadgeClass(type) {
  return type === 'hard_lock'
    ? 'bg-slate-900 text-white dark:bg-slate-700'
    : 'bg-rose-500 text-white'
}

function statusBadgeClass(status, blockType) {
  if (blockType === 'hard_lock') {
    return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300'
  }

  if (status === 'approved' || status === 'unlocked') {
    return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300'
  }

  if (status === 'rejected') {
    return 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-300'
  }

  return 'bg-amber-100 text-amber-800 dark:bg-amber-500/10 dark:text-amber-300'
}
</script>

<template>
  <Head :title="$t('Student Locks')" />

  <DashboardLayout>
    <section class="space-y-6">
      <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-col gap-4 border-b border-slate-200 pb-4 dark:border-gray-800 lg:flex-row lg:items-start lg:justify-between">
          <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-gray-100">Permission Requests</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
              Review and approve student permission requests
            </p>
          </div>

          <div class="flex flex-wrap gap-2">
            <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
              Absence Approved: {{ summary.absenceApproved ?? 0 }}
            </span>
            <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">
              Permission Approved: {{ summary.permissionApproved ?? 0 }}
            </span>
            <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 dark:bg-gray-800 dark:text-gray-300">
              Total Approved: {{ summary.totalApproved ?? 0 }}
            </span>
          </div>
        </div>

        <div class="mt-4 flex items-center gap-2">
          <div class="relative w-full max-w-sm">
            <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-gray-500" />
            <input
              v-model="form.search"
              type="text"
              placeholder="Search student name..."
              class="w-full rounded-md border border-slate-300 bg-white px-3 py-2.5 pl-9 text-sm text-slate-700 outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
              @keyup.enter="submit(1)"
            >
          </div>
          <button
            type="button"
            class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-300 dark:hover:bg-gray-800"
            @click="clearSearch"
          >
            Clear
          </button>
        </div>

        <div class="mt-4 overflow-hidden rounded-md border border-slate-200 dark:border-gray-800">
          <Table>
            <TableHeader>
              <TableRow class="bg-slate-50 dark:bg-gray-950/40">
                <TableHead class="w-12">#</TableHead>
                <TableHead>Student</TableHead>
                <TableHead>Course</TableHead>
                <TableHead>Permission Period</TableHead>
                <TableHead>Reason</TableHead>
                <TableHead>Status</TableHead>
                <TableHead class="text-right">Action</TableHead>
              </TableRow>
            </TableHeader>

            <TableBody>
              <TableRow v-if="!rows.length">
                <TableCell colspan="7" class="py-10 text-center text-sm text-slate-500 dark:text-gray-400">
                  No permission requests found.
                </TableCell>
              </TableRow>

              <TableRow v-for="(lock, index) in rows" :key="lock.id" class="border-t border-slate-200 hover:bg-slate-50 dark:border-gray-800 dark:hover:bg-gray-800/50">
                <TableCell class="text-slate-500 dark:text-gray-400">{{ rowNumber(index) }}</TableCell>
                <TableCell>
                  <div class="font-semibold text-slate-900 dark:text-gray-100">{{ lock.student?.full_name }}</div>
                  <div class="text-xs text-slate-500 dark:text-gray-400">{{ studentMeta(lock.student) }}</div>
                </TableCell>
                <TableCell class="font-medium text-blue-600 dark:text-blue-400">
                  {{ lock.course ?? '—' }}
                </TableCell>
                <TableCell>
                  <span
                    class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold uppercase tracking-wide"
                    :class="permissionBadgeClass(lock.block_type)"
                  >
                    {{ lock.permission_period }}
                  </span>
                </TableCell>
                <TableCell class="max-w-[360px] text-sm text-slate-600 dark:text-gray-300">
                  {{ lock.reason }}
                </TableCell>
                <TableCell>
                  <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold uppercase tracking-wide" :class="statusBadgeClass(lock.status, lock.block_type)">
                    {{ lock.block_type === 'hard_lock' ? 'Hard Lock' : (lock.status === 'pending' ? 'Pending' : 'Approved') }}
                  </span>
                </TableCell>
                <TableCell>
                  <div class="flex items-center justify-end gap-2">
                    <button
                      v-if="lock.status === 'pending'"
                      type="button"
                      class="inline-flex items-center gap-1 rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700"
                    >
                      Approve
                    </button>
                    <span
                      v-else
                      class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-600 text-white"
                      title="Approved"
                    >
                      <CheckCircle2 class="h-4 w-4" />
                    </span>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>

        <div class="mt-5">
          <Pagination
            :current-page="locks.current_page"
            :last-page="locks.last_page"
            :disabled="locks.last_page <= 1"
            @page-change="submit"
          />
        </div>
      </div>
    </section>
  </DashboardLayout>
</template>
