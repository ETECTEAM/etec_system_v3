<script setup>
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { onMounted, ref, watch } from 'vue'
import Breadcrumbs from '../../../components/ui/breadcrumbs/Breadcrumbs.vue'
import PageHero from '../../../components/ui/page-hero/PageHero.vue'
import { useI18n } from '../../../i18n'

const props = defineProps({
  logs: { type: Object, default: () => ({ data: [], links: [], from: 0, to: 0, total: 0 }) },
  filters: { type: Object, default: () => ({}) },
})

const { t } = useI18n()

const rows = ref(props.logs)
const loading = ref(false)
const action = ref(props.filters.action ?? '')
const search = ref(props.filters.search ?? '')
const dateFrom = ref(props.filters.date_from ?? '')
const dateTo = ref(props.filters.date_to ?? '')
const expanded = ref(null)
let timer = null

async function fetchLogs(page = 1) {
  loading.value = true
  const params = new URLSearchParams()
  if (action.value) params.set('action', action.value)
  if (search.value) params.set('search', search.value)
  if (dateFrom.value) params.set('date_from', dateFrom.value)
  if (dateTo.value) params.set('date_to', dateTo.value)
  params.set('page', page)
  try {
    const res = await fetch(`/dashboard/absence-blocks/audit/data?${params.toString()}`)
    rows.value = await res.json()
  } catch {
    rows.value = { data: [], links: [], from: 0, to: 0, total: 0 }
  } finally {
    loading.value = false
  }
}

function applyFilters() {
  clearTimeout(timer)
  timer = setTimeout(() => fetchLogs(1), 350)
}
watch([action, search, dateFrom, dateTo], applyFilters)
onMounted(() => fetchLogs())

const actionOptions = [
  '', 'attendance_rule', 'absence_block', 'hard_lock', 'attendance_rule_settings',
]

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Absence Block Audit', current: true },
]
</script>

<template>
  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero eyebrow="Attendance" :title="$t('Absence Block Audit')" :description="$t('Every rule and block change, newest first.')" />

      <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-col gap-4 border-b border-slate-200 px-6 py-5 lg:flex-row lg:items-end dark:border-gray-800">
          <div class="flex-1">
            <label class="mb-1 block text-xs font-medium text-slate-500 dark:text-gray-400">{{ $t('User') }}</label>
            <input v-model="search" type="text" :placeholder="$t('Name...')" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-500 dark:text-gray-400">{{ $t('Action') }}</label>
            <select v-model="action" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
              <option v-for="a in actionOptions" :key="a" :value="a">{{ a === '' ? $t('All') : a }}</option>
            </select>
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-500 dark:text-gray-400">{{ $t('From') }}</label>
            <input v-model="dateFrom" type="date" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-500 dark:text-gray-400">{{ $t('To') }}</label>
            <input v-model="dateTo" type="date" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-800">
                <th class="px-6 py-3 text-left text-slate-600 dark:text-gray-300">{{ $t('Time') }}</th>
                <th class="px-6 py-3 text-left text-slate-600 dark:text-gray-300">{{ $t('User') }}</th>
                <th class="px-6 py-3 text-left text-slate-600 dark:text-gray-300">{{ $t('Action') }}</th>
                <th class="px-6 py-3 text-left text-slate-600 dark:text-gray-300">{{ $t('Target') }}</th>
                <th class="px-6 py-3 text-right text-slate-600 dark:text-gray-300"></th>
              </tr>
            </thead>
            <tbody>
              <template v-for="log in rows.data" :key="log.id">
                <tr class="border-t border-slate-200 hover:bg-slate-50 dark:border-gray-800 dark:hover:bg-gray-800">
                  <td class="px-6 py-4 text-xs text-slate-500 dark:text-gray-400">{{ log.created_at }}</td>
                  <td class="px-6 py-4 text-slate-700 dark:text-gray-300">{{ log.user?.name ?? $t('System') }}</td>
                  <td class="px-6 py-4">
                    <span class="rounded bg-slate-100 px-2 py-0.5 font-mono text-xs text-slate-700 dark:bg-gray-800 dark:text-gray-300">{{ log.action }}</span>
                  </td>
                  <td class="px-6 py-4 text-xs text-slate-500 dark:text-gray-400">
                    <span v-if="log.rule_id">rule #{{ log.rule_id }}</span>
                    <span v-else-if="log.block_id">block #{{ log.block_id }}</span>
                    <span v-else>—</span>
                  </td>
                  <td class="px-6 py-4 text-right">
                    <button
                      v-if="log.before || log.after"
                      @click="expanded = expanded === log.id ? null : log.id"
                      class="text-xs font-semibold text-blue-600 hover:underline dark:text-blue-400"
                    >{{ expanded === log.id ? $t('Hide') : $t('Diff') }}</button>
                  </td>
                </tr>
                <tr v-if="expanded === log.id" class="bg-slate-50 dark:bg-gray-800/60">
                  <td colspan="5" class="px-6 py-4">
                    <div class="grid gap-4 md:grid-cols-2">
                      <div>
                        <div class="mb-1 text-xs font-semibold uppercase text-slate-400">{{ $t('Before') }}</div>
                        <pre class="overflow-x-auto rounded bg-white p-3 text-xs text-slate-700 dark:bg-gray-900 dark:text-gray-300">{{ JSON.stringify(log.before, null, 2) }}</pre>
                      </div>
                      <div>
                        <div class="mb-1 text-xs font-semibold uppercase text-slate-400">{{ $t('After') }}</div>
                        <pre class="overflow-x-auto rounded bg-white p-3 text-xs text-slate-700 dark:bg-gray-900 dark:text-gray-300">{{ JSON.stringify(log.after, null, 2) }}</pre>
                      </div>
                    </div>
                  </td>
                </tr>
              </template>
              <tr v-if="!rows.data?.length && !loading">
                <td colspan="5" class="py-10 text-center text-slate-500 dark:text-gray-400">{{ $t('No audit entries.') }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800 dark:bg-gray-800/40">
          <p class="text-sm text-slate-500 dark:text-gray-400">{{ $t('Showing :from-:to of :total', { from: rows.from, to: rows.to, total: rows.total }) }}</p>
          <div class="flex flex-wrap gap-2 text-sm">
            <button
              v-for="link in rows.links"
              :key="link.label"
              @click="link.url && fetchLogs(new URL(link.url).searchParams.get('page') || 1)"
              class="rounded-lg border px-3 py-2 text-sm transition dark:border-gray-700 dark:text-gray-300"
              :class="{ 'bg-blue-600 text-white border-blue-600': link.active, 'hover:bg-gray-100 dark:hover:bg-gray-800': !link.active, 'opacity-40 pointer-events-none': !link.url }"
              v-html="link.label"
            />
          </div>
        </div>
      </div>
    </section>
  </DashboardLayout>
</template>
