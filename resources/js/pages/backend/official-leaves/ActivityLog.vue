<script setup>
import { onMounted, ref, watch } from 'vue'
import { Head } from '@inertiajs/vue3'
import axios from 'axios'
import { History } from '@lucide/vue'
import { Breadcrumbs } from '../../../components/ui/breadcrumbs'
import { EmptyState } from '../../../components/ui/empty-state'
import { PageHero } from '../../../components/ui/page-hero'
import { Pagination } from '../../../components/ui/pagination'
import { SelectSearch } from '../../../components/ui/select-search'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '../../../components/ui/table'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'
import { useI18n } from '@/i18n'

const props = defineProps({
  actions: {
    type: Array,
    default: () => [],
  },
})

const { t } = useI18n()

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Official Leave', href: '/dashboard/official-leaves' },
  { label: 'Activity Log', current: true },
]

const actionFilter = ref('')
const dateFrom = ref('')
const dateTo = ref('')

const logs = ref([])
const pagination = ref({ current_page: 1, last_page: 1, per_page: 15, total: 0 })
const isLoading = ref(false)
const hasLoaded = ref(false)
const expandedId = ref(null)

const actionOptions = props.actions.map((action) => ({ value: action, label: action }))

async function fetchLogs(pageNumber = 1) {
  isLoading.value = true

  try {
    const params = { page: pageNumber, per_page: 15 }

    if (actionFilter.value) params.action = actionFilter.value
    if (dateFrom.value) params.date_from = dateFrom.value
    if (dateTo.value) params.date_to = dateTo.value

    const response = await axios.get('/dashboard/official-leaves/activity-log/data', { params })

    logs.value = response.data.data ?? []
    pagination.value = {
      current_page: response.data.current_page ?? 1,
      last_page: response.data.last_page ?? 1,
      per_page: response.data.per_page ?? 15,
      total: response.data.total ?? 0,
    }
  } catch (error) {
    console.error('Failed to fetch activity log', error)
  } finally {
    hasLoaded.value = true
    isLoading.value = false
  }
}

function toggleRow(log) {
  expandedId.value = expandedId.value === log.id ? null : log.id
}

function formatDateTime(value) {
  if (!value) return '-'

  return new Date(value).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' })
}

// Compact labels for the audit action verbs.
function actionLabel(action) {
  const map = {
    'qr.generated': t('QR generated'),
    'leave.submitted': t('Leave submitted'),
    'leave.approved': t('Leave approved'),
    'leave.rejected': t('Leave rejected'),
    'leave.revoked': t('Leave revoked'),
    'leave.deleted': t('Leave deleted'),
    'settings.updated': t('Settings updated'),
  }

  return map[action] ?? action
}

watch([actionFilter, dateFrom, dateTo], () => fetchLogs(1))

onMounted(() => fetchLogs())
</script>

<template>
  <Head :title="$t('Activity Log')" />

  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero
        eyebrow="Official Leave"
        :title="$t('Activity Log')"
        :description="$t('Every decision and change in the official-leave feature, with before/after values.')"
      />

      <!-- Filters -->
      <div class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:grid-cols-3">
        <SelectSearch v-model="actionFilter" :options="actionOptions" :placeholder="$t('All actions')" searchable />

        <div class="flex items-center gap-2 sm:col-span-2">
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

      <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div v-if="isLoading && !hasLoaded" class="space-y-3 p-6">
          <div v-for="i in 6" :key="i" class="h-10 animate-pulse rounded-lg bg-slate-100 dark:bg-gray-800" />
        </div>

        <EmptyState
          v-else-if="hasLoaded && !logs.length"
          :icon="History"
          :title="'No activity yet'"
          :description="'Audit rows appear here as soon as leaves are requested or decided.'"
        />

        <Table v-else>
          <TableHeader>
            <TableRow>
              <TableHead>{{ $t('When') }}</TableHead>
              <TableHead>{{ $t('Action') }}</TableHead>
              <TableHead>{{ $t('By') }}</TableHead>
              <TableHead class="text-center">{{ $t('Leave') }}</TableHead>
              <TableHead>{{ $t('IP address') }}</TableHead>
              <TableHead class="text-right">{{ $t('Details') }}</TableHead>
            </TableRow>
          </TableHeader>

          <TableBody>
            <template v-for="log in logs" :key="log.id">
              <TableRow>
                <TableCell class="whitespace-nowrap text-xs font-semibold text-slate-500 dark:text-gray-400">{{ formatDateTime(log.created_at) }}</TableCell>
                <TableCell>
                  <span class="inline-flex rounded-lg border border-slate-200 bg-slate-50 px-2 py-0.5 font-mono text-[11px] font-bold text-slate-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    {{ actionLabel(log.action) }}
                  </span>
                </TableCell>
                <TableCell class="text-sm font-bold text-slate-900 dark:text-gray-100">{{ log.user }}</TableCell>
                <TableCell class="whitespace-nowrap text-center text-xs font-semibold text-slate-500 dark:text-gray-400">
                  {{ log.leave_id ? `#${log.leave_id}${log.student_name ? ` · ${log.student_name}` : ''}` : '-' }}
                </TableCell>
                <TableCell class="font-mono text-xs font-medium text-slate-400">{{ log.ip ?? '-' }}</TableCell>
                <TableCell class="text-right">
                  <button
                    type="button"
                    class="rounded-lg px-3 py-1.5 text-xs font-bold text-blue-600 transition hover:bg-blue-50 dark:hover:bg-blue-500/10"
                    @click="toggleRow(log)"
                  >
                    {{ expandedId === log.id ? $t('Hide') : $t('View') }}
                  </button>
                </TableCell>
              </TableRow>

              <tr v-if="expandedId === log.id">
                <td colspan="6" class="bg-slate-50 px-4 py-4 dark:bg-gray-950">
                  <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                      <p class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-400">Before</p>
                      <pre class="mt-1 overflow-x-auto rounded-lg border border-slate-200 bg-white p-3 text-[11px] leading-relaxed text-slate-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">{{ JSON.stringify(log.before, null, 2) || '—' }}</pre>
                    </div>
                    <div>
                      <p class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-400">After</p>
                      <pre class="mt-1 overflow-x-auto rounded-lg border border-slate-200 bg-white p-3 text-[11px] leading-relaxed text-slate-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">{{ JSON.stringify(log.after, null, 2) || '—' }}</pre>
                    </div>
                  </div>
                </td>
              </tr>
            </template>
          </TableBody>
        </Table>

        <div v-if="pagination.last_page > 1" class="border-t border-slate-100 py-4 dark:border-gray-800">
          <Pagination
            :current-page="pagination.current_page"
            :last-page="pagination.last_page"
            :disabled="isLoading"
            @page-change="fetchLogs"
          />
        </div>
      </div>
    </section>
  </DashboardLayout>
</template>
