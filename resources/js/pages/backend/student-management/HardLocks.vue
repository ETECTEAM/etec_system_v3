<script setup>
import { computed, reactive } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { Search } from '@lucide/vue'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Breadcrumbs from '../../../components/ui/breadcrumbs/Breadcrumbs.vue'
import PageHero from '../../../components/ui/page-hero/PageHero.vue'
import { Pagination } from '@/components/ui/pagination'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'

const props = defineProps({
  hardLocks: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  courses: {
    type: Array,
    default: () => [],
  },
  statusOptions: {
    type: Array,
    default: () => [],
  },
  settings: {
    type: Object,
    default: () => ({}),
  },
})

const form = reactive({
  search: props.filters.search ?? '',
  course_id: props.filters.course_id ?? '',
  status: props.filters.status ?? '',
  date_from: props.filters.date_from ?? '',
  date_to: props.filters.date_to ?? '',
})

const rows = computed(() => props.hardLocks?.data ?? [])

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Student Management', current: false },
  { label: 'Student Hard Locks', current: true },
]

function submit(page = 1) {
  router.get('/dashboard/student-management/hard-locks', { ...form, page }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  })
}

function resetFilters() {
  form.search = ''
  form.course_id = ''
  form.status = ''
  form.date_from = ''
  form.date_to = ''
  submit(1)
}

function rowNumber(index) {
  return (((props.hardLocks.current_page ?? 1) - 1) * (props.hardLocks.per_page ?? 15)) + index + 1
}

function formatDateTime(value) {
  if (!value) return '—'
  return new Date(value).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' })
}
</script>

<template>
  <Head :title="$t('Student Hard Locks')" />

  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero
        eyebrow="Student Management"
        :title="$t('Student Hard Locks')"
        :description="$t('Review serious attendance blocks. Only Super Admin should unlock these records.')"
      />

      <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <form class="grid gap-4 lg:grid-cols-4" @submit.prevent="submit(1)">
          <div class="lg:col-span-2">
            <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-gray-500">{{ $t('Search') }}</label>
            <div class="relative">
              <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-gray-500" />
              <input v-model="form.search" type="text" :placeholder="$t('Search student, ID, or phone')" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 pl-9 text-sm text-slate-700 outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20">
            </div>
          </div>

          <div>
            <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-gray-500">{{ $t('Course') }}</label>
            <select v-model="form.course_id" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20">
              <option value="">{{ $t('All Courses') }}</option>
              <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.title }}</option>
            </select>
          </div>

          <div>
            <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-gray-500">{{ $t('Status') }}</label>
            <select v-model="form.status" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20">
              <option value="">{{ $t('All Statuses') }}</option>
              <option v-for="status in statusOptions" :key="status.value" :value="status.value">{{ status.label }}</option>
            </select>
          </div>

          <div>
            <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-gray-500">{{ $t('From') }}</label>
            <input v-model="form.date_from" type="date" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20">
          </div>

          <div>
            <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-gray-500">{{ $t('To') }}</label>
            <input v-model="form.date_to" type="date" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20">
          </div>

          <div class="flex items-end gap-3 lg:col-span-4">
            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-500">
              {{ $t('Filter') }}
            </button>
            <button type="button" class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800" @click="resetFilters">
              {{ $t('Reset') }}
            </button>
            <div class="ml-auto text-sm text-slate-500 dark:text-gray-400">
              {{ $t('Hard lock enabled') }}: <span class="font-semibold text-slate-900 dark:text-gray-100">{{ settings.hardLockEnabled ? $t('Yes') : $t('No') }}</span>
            </div>
          </div>
        </form>
      </div>

      <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead class="w-16">{{ $t('No') }}</TableHead>
              <TableHead>{{ $t('Student') }}</TableHead>
              <TableHead>{{ $t('Student ID') }}</TableHead>
              <TableHead>{{ $t('Phone') }}</TableHead>
              <TableHead>{{ $t('Course') }}</TableHead>
              <TableHead>{{ $t('Initial Absences') }}</TableHead>
              <TableHead>{{ $t('Post Approval Absences') }}</TableHead>
              <TableHead>{{ $t('Current Rule') }}</TableHead>
              <TableHead>{{ $t('Hard Locked At') }}</TableHead>
              <TableHead>{{ $t('Status') }}</TableHead>
              <TableHead>{{ $t('Unlock Information') }}</TableHead>
              <TableHead class="text-right">{{ $t('Actions') }}</TableHead>
            </TableRow>
          </TableHeader>

          <TableBody>
            <TableRow v-if="!rows.length">
              <TableCell colspan="12" class="py-10 text-center text-sm text-slate-500 dark:text-gray-400">
                {{ $t('No hard locks found.') }}
              </TableCell>
            </TableRow>

            <TableRow v-for="(lock, index) in rows" :key="lock.id" class="hover:bg-slate-50 dark:hover:bg-gray-800/60">
              <TableCell class="text-slate-500 dark:text-gray-400">{{ rowNumber(index) }}</TableCell>
              <TableCell class="font-medium text-slate-900 dark:text-gray-100">{{ lock.student?.full_name }}</TableCell>
              <TableCell class="text-slate-600 dark:text-gray-300">{{ lock.student?.student_code ?? lock.student?.id ?? '—' }}</TableCell>
              <TableCell class="text-slate-600 dark:text-gray-300">{{ lock.student?.phone ?? '—' }}</TableCell>
              <TableCell class="text-slate-600 dark:text-gray-300">{{ lock.course ?? '—' }}</TableCell>
              <TableCell class="text-slate-600 dark:text-gray-300">{{ lock.student?.absence_count ?? 0 }}</TableCell>
              <TableCell class="text-slate-600 dark:text-gray-300">{{ settings.postApprovalAbsenceLimit }}</TableCell>
              <TableCell class="text-slate-600 dark:text-gray-300">{{ $t('Hard lock rule') }}</TableCell>
              <TableCell class="text-slate-600 dark:text-gray-300">{{ formatDateTime(lock.blocked_at) }}</TableCell>
              <TableCell class="text-slate-600 dark:text-gray-300">{{ lock.status }}</TableCell>
              <TableCell class="text-slate-600 dark:text-gray-300">{{ lock.comment ?? '—' }}</TableCell>
              <TableCell>
                <div class="flex justify-end gap-2 text-xs">
                  <button type="button" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-2.5 py-1.5 text-slate-700 transition hover:bg-slate-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                    {{ $t('View Details') }}
                  </button>
                  <button type="button" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-2.5 py-1.5 text-slate-700 transition hover:bg-slate-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                    {{ $t('Unlock') }}
                  </button>
                </div>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>

        <div class="border-t border-slate-200 px-6 py-5 dark:border-gray-800">
          <Pagination
            :current-page="hardLocks.current_page"
            :last-page="hardLocks.last_page"
            :disabled="hardLocks.last_page <= 1"
            @page-change="submit"
          />
        </div>
      </div>
    </section>
  </DashboardLayout>
</template>
