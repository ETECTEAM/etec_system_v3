<script setup>
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Breadcrumbs from '../../../components/ui/breadcrumbs/Breadcrumbs.vue'
import PageHero from '../../../components/ui/page-hero/PageHero.vue'
import { ref, onMounted } from 'vue'
import { useI18n } from '../../../i18n'

const { t } = useI18n()
const data = ref({ leavesPerMonth: [], topStudents: [], currentlyOnLeave: [], classBreakdown: [] })
const loading = ref(true)

onMounted(async () => {
  try {
    const res = await fetch('/dashboard/official-leaves/reports/data')
    data.value = await res.json()
  } catch { /* empty */ }
  finally { loading.value = false }
})

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Reports & Stats', current: true },
]
</script>

<template>
  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero eyebrow="Super Admin" :title="$t('Reports & Statistics')" :description="$t('Official leave analytics and current leave status.')" />

      <!-- Currently On Leave -->
      <div class="bg-white rounded-xl border border-slate-200 shadow-sm dark:bg-gray-900 dark:border-gray-800 p-6">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-gray-100 mb-4">{{ $t('Students Currently On Leave') }}</h3>
        <div v-if="data.currentlyOnLeave.length" class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead><tr class="border-b border-gray-200 dark:border-gray-700">
              <th class="px-4 py-2 text-left text-slate-600 dark:text-gray-300">{{ $t('Student') }}</th>
              <th class="px-4 py-2 text-left text-slate-600 dark:text-gray-300">{{ $t('Dates') }}</th>
              <th class="px-4 py-2 text-left text-slate-600 dark:text-gray-300">{{ $t('Reason') }}</th>
              <th class="px-4 py-2 text-left text-slate-600 dark:text-gray-300">{{ $t('Approved By') }}</th>
            </tr></thead>
            <tbody>
              <tr v-for="leave in data.currentlyOnLeave" :key="leave.id" class="border-t border-slate-200 dark:border-gray-800">
                <td class="px-4 py-3 font-medium text-slate-900 dark:text-gray-100">{{ leave.student?.full_name }}</td>
                <td class="px-4 py-3 text-slate-600 dark:text-gray-400">{{ leave.start_date }} - {{ leave.end_date }}</td>
                <td class="px-4 py-3 text-slate-600 dark:text-gray-400">{{ leave.reason }}</td>
                <td class="px-4 py-3 text-slate-500 dark:text-gray-400">{{ leave.approver?.name ?? '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-else class="text-sm text-slate-500 dark:text-gray-400">{{ $t('No students currently on leave.') }}</p>
      </div>

      <!-- Leaves Per Month -->
      <div class="bg-white rounded-xl border border-slate-200 shadow-sm dark:bg-gray-900 dark:border-gray-800 p-6">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-gray-100 mb-4">{{ $t('Leaves Per Month') }}</h3>
        <div v-if="data.leavesPerMonth.length" class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead><tr class="border-b border-gray-200 dark:border-gray-700">
              <th class="px-4 py-2 text-left text-slate-600 dark:text-gray-300">{{ $t('Month') }}</th>
              <th class="px-4 py-2 text-center text-slate-600 dark:text-gray-300">{{ $t('Total') }}</th>
              <th class="px-4 py-2 text-center text-green-600">{{ $t('Approved') }}</th>
              <th class="px-4 py-2 text-center text-red-600">{{ $t('Rejected') }}</th>
              <th class="px-4 py-2 text-center text-yellow-600">{{ $t('Pending') }}</th>
            </tr></thead>
            <tbody>
              <tr v-for="row in data.leavesPerMonth" :key="row.month" class="border-t border-slate-200 dark:border-gray-800">
                <td class="px-4 py-3 font-medium text-slate-900 dark:text-gray-100">{{ row.month }}</td>
                <td class="px-4 py-3 text-center text-slate-700 dark:text-gray-300">{{ row.total }}</td>
                <td class="px-4 py-3 text-center text-green-600">{{ row.approved }}</td>
                <td class="px-4 py-3 text-center text-red-600">{{ row.rejected }}</td>
                <td class="px-4 py-3 text-center text-yellow-600">{{ row.pending }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-else class="text-sm text-slate-500 dark:text-gray-400">{{ $t('No data available.') }}</p>
      </div>

      <!-- Top Students -->
      <div class="bg-white rounded-xl border border-slate-200 shadow-sm dark:bg-gray-900 dark:border-gray-800 p-6">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-gray-100 mb-4">{{ $t('Top Students by Leave Usage') }}</h3>
        <div v-if="data.topStudents.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <div v-for="s in data.topStudents" :key="s.student_id" class="p-4 rounded-xl border border-slate-200 dark:border-gray-700">
            <p class="font-semibold text-slate-900 dark:text-gray-100">{{ s.name }}</p>
            <div class="mt-2 flex items-center gap-2">
              <div class="flex-1 bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                <div class="h-2 rounded-full transition-all" :class="s.percentage >= 75 ? 'bg-red-500' : s.percentage >= 50 ? 'bg-yellow-500' : 'bg-green-500'" :style="{ width: Math.min(s.percentage, 100) + '%' }" />
              </div>
              <span class="text-xs font-medium text-slate-600 dark:text-gray-400">{{ s.used }}/{{ s.quota }}</span>
            </div>
          </div>
        </div>
        <p v-else class="text-sm text-slate-500 dark:text-gray-400">{{ $t('No data available.') }}</p>
      </div>
    </section>
  </DashboardLayout>
</template>
