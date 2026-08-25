<script setup>
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { router } from '@inertiajs/vue3'
import { ref, watch, onMounted } from 'vue'
import Breadcrumbs from '../../../components/ui/breadcrumbs/Breadcrumbs.vue'
import PageHero from '../../../components/ui/page-hero/PageHero.vue'
import { useI18n } from '../../../i18n'

const { t } = useI18n()
const logs = ref({ data: [], links: [], from: 0, to: 0, total: 0 })
const loading = ref(true)
const actionFilter = ref('')
const startDate = ref('')
const endDate = ref('')
let timeout = null

const actionColors = {
  approved: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
  rejected: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
  revoked: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
  setting_updated: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
}

async function fetchLogs(page = 1) {
  loading.value = true
  const params = new URLSearchParams()
  if (actionFilter.value) params.set('action', actionFilter.value)
  if (startDate.value) params.set('start_date', startDate.value)
  if (endDate.value) params.set('end_date', endDate.value)
  params.set('page', page)
  try {
    const res = await fetch(`/dashboard/official-leaves/activity-log/data?${params.toString()}`)
    const data = await res.json()
    logs.value = data.logs
  } catch { logs.value = { data: [], links: [], from: 0, to: 0, total: 0 } }
  finally { loading.value = false }
}

function applyFilters() { clearTimeout(timeout); timeout = setTimeout(() => fetchLogs(1), 400) }
watch([actionFilter, startDate, endDate], applyFilters)
onMounted(() => fetchLogs())

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Activity Log', current: true },
]
</script>

<template>
  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero eyebrow="Super Admin" :title="$t('Activity Log')" :description="$t('Audit trail of all leave-related actions.')" />

      <div class="bg-white rounded-xl border border-slate-200 shadow-sm dark:bg-gray-900 dark:border-gray-800">
        <div class="border-b border-slate-200 px-6 py-5 dark:border-gray-800">
          <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
            <div>
              <label class="block text-xs font-medium text-slate-500 dark:text-gray-400 mb-1">{{ $t('Action') }}</label>
              <select v-model="actionFilter" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                <option value="">{{ $t('All') }}</option>
                <option value="approved">{{ $t('Approved') }}</option>
                <option value="rejected">{{ $t('Rejected') }}</option>
                <option value="revoked">{{ $t('Revoked') }}</option>
                <option value="setting_updated">{{ $t('Setting Updated') }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-medium text-slate-500 dark:text-gray-400 mb-1">{{ $t('From') }}</label>
              <input v-model="startDate" type="date" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200" />
            </div>
            <div>
              <label class="block text-xs font-medium text-slate-500 dark:text-gray-400 mb-1">{{ $t('To') }}</label>
              <input v-model="endDate" type="date" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200" />
            </div>
          </div>
        </div>

        <div class="relative overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="bg-gray-50 border-b border-gray-200 dark:bg-gray-800 dark:border-gray-800">
                <th class="px-6 py-3 text-left text-slate-600 dark:text-gray-300">{{ $t('User') }}</th>
                <th class="px-6 py-3 text-left text-slate-600 dark:text-gray-300">{{ $t('Action') }}</th>
                <th class="px-6 py-3 text-left text-slate-600 dark:text-gray-300">{{ $t('Student') }}</th>
                <th class="px-6 py-3 text-left text-slate-600 dark:text-gray-300">{{ $t('Details') }}</th>
                <th class="px-6 py-3 text-left text-slate-600 dark:text-gray-300">{{ $t('Date') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="log in logs.data" :key="log.id" class="border-t border-slate-200 hover:bg-slate-50 dark:border-gray-800 dark:hover:bg-gray-800">
                <td class="px-6 py-4 font-medium text-slate-900 dark:text-gray-100">{{ log.user?.name ?? 'System' }}</td>
                <td class="px-6 py-4"><span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold" :class="actionColors[log.action] ?? 'bg-gray-100 text-gray-800'">{{ log.action }}</span></td>
                <td class="px-6 py-4 text-slate-600 dark:text-gray-400">{{ log.leave?.student?.full_name ?? '-' }}</td>
                <td class="px-6 py-4 text-xs text-slate-600 dark:text-gray-400 max-w-xs">
                  <div v-if="log.before"><span class="font-medium">{{ $t('Before:') }}</span> <pre class="whitespace-pre-wrap text-xs">{{ JSON.stringify(log.before) }}</pre></div>
                  <div v-if="log.after" class="mt-1"><span class="font-medium">{{ $t('After:') }}</span> <pre class="whitespace-pre-wrap text-xs">{{ JSON.stringify(log.after) }}</pre></div>
                </td>
                <td class="px-6 py-4 text-slate-500 dark:text-gray-400 text-xs">{{ log.created_at }}</td>
              </tr>
              <tr v-if="!logs.data?.length && !loading">
                <td colspan="5" class="py-10 text-center text-slate-500 dark:text-gray-400">{{ $t('No activity logs found.') }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800 dark:bg-gray-800/40">
          <p class="text-sm text-slate-500 dark:text-gray-400">{{ $t('Showing :from-:to of :total', { from: logs.from, to: logs.to, total: logs.total }) }}</p>
          <div class="flex flex-wrap gap-2 text-sm">
            <button v-for="link in logs.links" :key="link.label" @click="link.url && fetchLogs(new URL(link.url).searchParams.get('page') || 1)" class="px-3 py-2 rounded-lg border text-sm transition dark:border-gray-700 dark:text-gray-300" :class="{ 'bg-blue-600 text-white border-blue-600': link.active, 'hover:bg-gray-100 dark:hover:bg-gray-800': !link.active, 'opacity-40 pointer-events-none': !link.url }" v-html="link.label" />
          </div>
        </div>
      </div>
    </section>
  </DashboardLayout>
</template>
