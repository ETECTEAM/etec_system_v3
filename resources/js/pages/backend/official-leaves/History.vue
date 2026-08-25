<script setup>
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { router, Link } from '@inertiajs/vue3'
import { ref, watch, onMounted } from 'vue'
import Breadcrumbs from '../../../components/ui/breadcrumbs/Breadcrumbs.vue'
import PageHero from '../../../components/ui/page-hero/PageHero.vue'
import { useI18n } from '../../../i18n'

const { t } = useI18n()

const leaves = ref({ data: [], links: [], from: 0, to: 0, total: 0 })
const loading = ref(true)
const search = ref('')
const statusFilter = ref('')
const startDate = ref('')
const endDate = ref('')
let timeout = null

const statusColors = {
  pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
  approved: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
  rejected: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
  revoked: 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
}

async function fetchLeaves(page = 1) {
  loading.value = true
  const params = new URLSearchParams()
  if (search.value) params.set('search', search.value)
  if (statusFilter.value) params.set('status', statusFilter.value)
  if (startDate.value) params.set('start_date', startDate.value)
  if (endDate.value) params.set('end_date', endDate.value)
  params.set('page', page)
  try {
    const res = await fetch(`/dashboard/official-leaves/history/data?${params.toString()}`)
    leaves.value = await res.json()
  } catch { leaves.value = { data: [], links: [], from: 0, to: 0, total: 0 } }
  finally { loading.value = false }
}

function applyFilters() {
  clearTimeout(timeout)
  timeout = setTimeout(() => fetchLeaves(1), 400)
}

watch([search, statusFilter, startDate, endDate], applyFilters)
onMounted(() => fetchLeaves())

const showApproveConfirm = ref(false)
const approveId = ref(null)
function approveLeave(id) { approveId.value = id; showApproveConfirm.value = true }
function confirmApprove() {
  router.post(`/dashboard/official-leaves/leaves/${approveId.value}/approve`, {}, {
    preserveScroll: true, onSuccess: () => { showApproveConfirm.value = false; fetchLeaves() },
  })
}

const showRejectModal = ref(false)
const rejectId = ref(null)
const rejectNote = ref('')
function openReject(id) { rejectId.value = id; rejectNote.value = ''; showRejectModal.value = true }
function confirmReject() {
  router.post(`/dashboard/official-leaves/leaves/${rejectId.value}/reject`, { note: rejectNote.value }, {
    preserveScroll: true, onSuccess: () => { showRejectModal.value = false; fetchLeaves() },
  })
}

const showRevokeModal = ref(false)
const revokeId = ref(null)
const revokeNote = ref('')
function openRevoke(id) { revokeId.value = id; revokeNote.value = ''; showRevokeModal.value = true }
function confirmRevoke() {
  router.post(`/dashboard/official-leaves/leaves/${revokeId.value}/revoke`, { note: revokeNote.value }, {
    preserveScroll: true, onSuccess: () => { showRevokeModal.value = false; fetchLeaves() },
  })
}

function deleteLeave(id) {
  if (confirm(t('Are you sure you want to permanently delete this leave request?'))) {
    router.delete(`/dashboard/official-leaves/leaves/${id}`, { preserveScroll: true, onSuccess: () => fetchLeaves() })
  }
}

function exportCsv() {
  const params = new URLSearchParams()
  if (statusFilter.value) params.set('status', statusFilter.value)
  if (startDate.value) params.set('start_date', startDate.value)
  if (endDate.value) params.set('end_date', endDate.value)
  window.location.href = `/dashboard/official-leaves/history/export?${params.toString()}`
}

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Leave History', current: true },
]
</script>

<template>
  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero eyebrow="Official Leave" :title="$t('Leave History')" :description="$t('View and manage all leave requests.')" />

      <div class="bg-white rounded-xl border border-slate-200 shadow-sm dark:bg-gray-900 dark:border-gray-800">
        <div class="border-b border-slate-200 px-6 py-5 dark:border-gray-800">
          <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
            <div class="flex-1">
              <label class="block text-xs font-medium text-slate-500 dark:text-gray-400 mb-1">{{ $t('Search') }}</label>
              <input v-model="search" type="text" :placeholder="$t('Search student name or ID...')" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200" />
            </div>
            <div>
              <label class="block text-xs font-medium text-slate-500 dark:text-gray-400 mb-1">{{ $t('Status') }}</label>
              <select v-model="statusFilter" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                <option value="">{{ $t('All') }}</option>
                <option value="pending">{{ $t('Pending') }}</option>
                <option value="approved">{{ $t('Approved') }}</option>
                <option value="rejected">{{ $t('Rejected') }}</option>
                <option value="revoked">{{ $t('Revoked') }}</option>
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
            <button @click="exportCsv" class="px-4 py-2.5 rounded-xl border border-slate-300 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-gray-600 dark:text-gray-300">{{ $t('Export CSV') }}</button>
          </div>
        </div>

        <div class="relative overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="bg-gray-50 border-b border-gray-200 dark:bg-gray-800 dark:border-gray-800">
                <th class="px-6 py-3 text-left text-slate-600 dark:text-gray-300">{{ $t('Student') }}</th>
                <th class="px-6 py-3 text-left text-slate-600 dark:text-gray-300">{{ $t('Dates') }}</th>
                <th class="px-6 py-3 text-left text-slate-600 dark:text-gray-300">{{ $t('Reason') }}</th>
                <th class="px-6 py-3 text-left text-slate-600 dark:text-gray-300">{{ $t('Status') }}</th>
                <th class="px-6 py-3 text-left text-slate-600 dark:text-gray-300">{{ $t('Approved By') }}</th>
                <th class="px-6 py-3 text-right text-slate-600 dark:text-gray-300">{{ $t('Actions') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="leave in leaves.data" :key="leave.id" class="border-t border-slate-200 hover:bg-slate-50 dark:border-gray-800 dark:hover:bg-gray-800">
                <td class="px-6 py-4 font-medium text-slate-900 dark:text-gray-100">{{ leave.student?.full_name }}</td>
                <td class="px-6 py-4 text-slate-600 dark:text-gray-400">{{ leave.start_date }} - {{ leave.end_date }}</td>
                <td class="px-6 py-4 text-slate-600 dark:text-gray-400 max-w-xs truncate">{{ leave.reason }}</td>
                <td class="px-6 py-4"><span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold" :class="statusColors[leave.status]">{{ leave.status }}</span></td>
                <td class="px-6 py-4 text-slate-500 dark:text-gray-400">{{ leave.approver?.name ?? leave.rejecter?.name ?? leave.revoker?.name ?? '-' }}</td>
                <td class="px-6 py-4 text-right space-x-2">
                  <button v-if="leave.status === 'pending'" @click="approveLeave(leave.id)" class="px-3 py-1.5 text-xs rounded-lg bg-green-50 text-green-700 border border-green-200 hover:bg-green-100 dark:bg-green-900/20 dark:text-green-400">{{ $t('Approve') }}</button>
                  <button v-if="leave.status === 'pending'" @click="openReject(leave.id)" class="px-3 py-1.5 text-xs rounded-lg bg-red-50 text-red-700 border border-red-200 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400">{{ $t('Reject') }}</button>
                  <button v-if="leave.status === 'approved'" @click="openRevoke(leave.id)" class="px-3 py-1.5 text-xs rounded-lg bg-orange-50 text-orange-700 border border-orange-200 hover:bg-orange-100 dark:bg-orange-900/20 dark:text-orange-400">{{ $t('Revoke') }}</button>
                  <button v-if="leave.status === 'rejected' || leave.status === 'pending'" @click="deleteLeave(leave.id)" class="px-3 py-1.5 text-xs rounded-lg bg-red-50 text-red-700 border border-red-200 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400">{{ $t('Delete') }}</button>
                </td>
              </tr>
              <tr v-if="!leaves.data?.length && !loading">
                <td colspan="6" class="py-10 text-center text-slate-500 dark:text-gray-400">{{ $t('No leave requests found.') }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800 dark:bg-gray-800/40">
          <p class="text-sm text-slate-500 dark:text-gray-400">{{ $t('Showing :from-:to of :total', { from: leaves.from, to: leaves.to, total: leaves.total }) }}</p>
          <div class="flex flex-wrap gap-2 text-sm">
            <button v-for="link in leaves.links" :key="link.label" @click="link.url && fetchLeaves(new URL(link.url).searchParams.get('page') || 1)" class="px-3 py-2 rounded-lg border text-sm transition dark:border-gray-700 dark:text-gray-300" :class="{ 'bg-blue-600 text-white border-blue-600': link.active, 'hover:bg-gray-100 dark:hover:bg-gray-800': !link.active, 'opacity-40 pointer-events-none': !link.url }" v-html="link.label" />
          </div>
        </div>
      </div>
    </section>

    <!-- Approve Confirm -->
    <transition name="fade">
      <div v-if="showApproveConfirm" class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50" @click.self="showApproveConfirm = false">
        <div class="bg-white rounded-xl w-full max-w-md p-6 shadow-lg dark:bg-gray-900">
          <h3 class="text-lg font-semibold text-slate-900 dark:text-gray-100 mb-2">{{ $t('Confirm Approval') }}</h3>
          <p class="text-sm text-slate-600 dark:text-gray-400 mb-6">{{ $t('Are you sure you want to approve this leave request?') }}</p>
          <div class="flex gap-3 justify-end">
            <button @click="showApproveConfirm = false" class="px-4 py-2 rounded-lg border border-slate-300 text-sm dark:border-gray-600 dark:text-gray-300">{{ $t('Cancel') }}</button>
            <button @click="confirmApprove" class="px-4 py-2 rounded-lg bg-green-600 text-sm font-semibold text-white hover:bg-green-700">{{ $t('Approve') }}</button>
          </div>
        </div>
      </div>
    </transition>

    <!-- Reject Modal -->
    <transition name="fade">
      <div v-if="showRejectModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50" @click.self="showRejectModal = false">
        <div class="bg-white rounded-xl w-full max-w-md p-6 shadow-lg dark:bg-gray-900">
          <button @click="showRejectModal = false" class="absolute top-3 right-3 text-gray-400 hover:text-black dark:text-gray-500">✖</button>
          <h3 class="text-lg font-semibold text-slate-900 dark:text-gray-100 mb-4">{{ $t('Reject Leave Request') }}</h3>
          <form @submit.prevent="confirmReject" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">{{ $t('Reason for rejection') }}</label>
              <textarea v-model="rejectNote" rows="3" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200" />
            </div>
            <div class="flex gap-3 justify-end">
              <button type="button" @click="showRejectModal = false" class="px-4 py-2 rounded-lg border border-slate-300 text-sm dark:border-gray-600 dark:text-gray-300">{{ $t('Cancel') }}</button>
              <button type="submit" class="px-4 py-2 rounded-lg bg-red-600 text-sm font-semibold text-white hover:bg-red-700">{{ $t('Confirm Reject') }}</button>
            </div>
          </form>
        </div>
      </div>
    </transition>

    <!-- Revoke Modal -->
    <transition name="fade">
      <div v-if="showRevokeModal" class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50" @click.self="showRevokeModal = false">
        <div class="bg-white rounded-xl w-full max-w-md p-6 shadow-lg dark:bg-gray-900">
          <button @click="showRevokeModal = false" class="absolute top-3 right-3 text-gray-400 hover:text-black dark:text-gray-500">✖</button>
          <h3 class="text-lg font-semibold text-slate-900 dark:text-gray-100 mb-4">{{ $t('Revoke Leave') }}</h3>
          <form @submit.prevent="confirmRevoke" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-gray-300 mb-1">{{ $t('Reason for revocation') }}</label>
              <textarea v-model="revokeNote" rows="3" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200" />
            </div>
            <div class="flex gap-3 justify-end">
              <button type="button" @click="showRevokeModal = false" class="px-4 py-2 rounded-lg border border-slate-300 text-sm dark:border-gray-600 dark:text-gray-300">{{ $t('Cancel') }}</button>
              <button type="submit" class="px-4 py-2 rounded-lg bg-orange-600 text-sm font-semibold text-white hover:bg-orange-700">{{ $t('Confirm Revoke') }}</button>
            </div>
          </form>
        </div>
      </div>
    </transition>
  </DashboardLayout>
</template>
