<script setup>
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { router, useForm } from '@inertiajs/vue3'
import { ref, watch, onUnmounted } from 'vue'
import Breadcrumbs from '../../../components/ui/breadcrumbs/Breadcrumbs.vue'
import PageHero from '../../../components/ui/page-hero/PageHero.vue'
import { useI18n } from '../../../i18n'

const { t } = useI18n()

const search = ref('')
const searchResults = ref([])
const searching = ref(false)
const selectedStudent = ref(null)
let searchTimeout = null

const qrData = ref(null)
const qrCountdown = ref(0)
const qrExpired = ref(false)
const showReviewCard = ref(false)
const pendingLeave = ref(null)
let pollInterval = null
let countdownInterval = null

const showApproveConfirm = ref(false)
const approveTarget = ref(null)

const showRejectModal = ref(false)
const rejectForm = useForm({ note: '' })
const rejectTarget = ref(null)

const showRevokeModal = ref(false)
const revokeForm = useForm({ note: '' })
const revokeTarget = ref(null)

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Official Leave Desk', current: true },
]

const statusColors = {
  pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
  approved: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
  rejected: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
  revoked: 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
}

function debounceSearch(value) {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(async () => {
    if (value.length < 2) { searchResults.value = []; return }
    searching.value = true
    try {
      const res = await fetch(`/dashboard/official-leaves/students/search?q=${encodeURIComponent(value)}`)
      searchResults.value = await res.json()
    } catch { searchResults.value = [] }
    finally { searching.value = false }
  }, 400)
}

watch(search, debounceSearch)

function selectStudent(student) {
  selectedStudent.value = student
  searchResults.value = []
  search.value = student.full_name
  qrData.value = null
  pendingLeave.value = null
  showReviewCard.value = false
}

async function generateQr() {
  if (!selectedStudent.value) return
  try {
    const res = await fetch('/dashboard/official-leaves/qr', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-XSRF-TOKEN': decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '') },
      body: JSON.stringify({ student_id: selectedStudent.value.id }),
    })
    const data = await res.json()
    qrData.value = data
    qrExpired.value = false
    showReviewCard.value = false
    pendingLeave.value = null
    startCountdown()
    startPolling(data.session_id)
  } catch (e) { console.error('QR generation failed', e) }
}

function startCountdown() {
  if (countdownInterval) clearInterval(countdownInterval)
  countdownInterval = setInterval(() => {
    if (!qrData.value) return
    const remaining = Math.floor((new Date(qrData.value.expires_at) - new Date()) / 1000)
    qrCountdown.value = Math.max(0, remaining)
    if (qrCountdown.value <= 0) { qrExpired.value = true; clearInterval(countdownInterval) }
  }, 1000)
}

function startPolling(sessionId) {
  if (pollInterval) clearInterval(pollInterval)
  pollInterval = setInterval(async () => {
    try {
      const res = await fetch(`/dashboard/official-leaves/sessions/${sessionId}/poll`)
      const data = await res.json()
      if (data.leave) {
        pendingLeave.value = data.leave
        showReviewCard.value = true
        clearInterval(pollInterval)
      }
    } catch (e) { console.error('Poll failed', e) }
  }, 3000)
}

function approveLeave(leave) { approveTarget.value = leave; showApproveConfirm.value = true }
function confirmApprove() {
  router.post(`/dashboard/official-leaves/leaves/${approveTarget.value.id}/approve`, {}, {
    preserveScroll: true,
    onSuccess: () => { showApproveConfirm.value = false; showReviewCard.value = false; pendingLeave.value = null },
  })
}

function openRejectModal(leave) { rejectTarget.value = leave; rejectForm.note = ''; showRejectModal.value = true }
function confirmReject() {
  rejectForm.post(`/dashboard/official-leaves/leaves/${rejectTarget.value.id}/reject`, {
    preserveScroll: true,
    onSuccess: () => { showRejectModal.value = false; showReviewCard.value = false; pendingLeave.value = null },
  })
}

function openRevokeModal(leave) { revokeTarget.value = leave; revokeForm.note = ''; showRevokeModal.value = true }
function confirmRevoke() {
  revokeForm.post(`/dashboard/official-leaves/leaves/${revokeTarget.value.id}/revoke`, {
    preserveScroll: true,
    onSuccess: () => { showRevokeModal.value = false },
  })
}

function formatCountdown(s) { return `${Math.floor(s/60)}:${(s%60).toString().padStart(2,'0')}` }

onUnmounted(() => { if (pollInterval) clearInterval(pollInterval); if (countdownInterval) clearInterval(countdownInterval) })
</script>

<template>
  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero eyebrow="Official Leave" :title="$t('Leave Request Desk')" :description="$t('Search students, generate QR codes, and review pending requests.')" />

      <!-- Student Search -->
      <div class="bg-white rounded-xl border border-slate-200 shadow-sm dark:bg-gray-900 dark:border-gray-800 p-6">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-gray-100 mb-4">{{ $t('Student Search') }}</h3>
        <div class="relative">
          <input v-model="search" type="text" :placeholder="$t('Search by name or student ID...')" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200" />
          <div v-if="searching" class="absolute right-3 top-3">
            <svg class="animate-spin h-5 w-5 text-blue-500" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
          </div>
        </div>
        <div v-if="searchResults.length" class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          <button v-for="s in searchResults" :key="s.id" @click="selectStudent(s)" class="text-left p-4 rounded-xl border transition" :class="selectedStudent?.id === s.id ? 'border-blue-500 bg-blue-50 dark:border-blue-400 dark:bg-blue-900/20' : 'border-slate-200 hover:border-blue-300 dark:border-gray-700'">
            <p class="font-semibold text-slate-900 dark:text-gray-100">{{ s.full_name }}</p>
            <p class="text-sm text-slate-500 dark:text-gray-400">ID: {{ s.id }}</p>
          </button>
        </div>
        <p v-else-if="search.length >= 2 && !searching" class="mt-3 text-sm text-slate-500 dark:text-gray-400">{{ $t('No students found.') }}</p>
      </div>

      <!-- Selected Student + QR -->
      <div v-if="selectedStudent" class="bg-white rounded-xl border border-slate-200 shadow-sm dark:bg-gray-900 dark:border-gray-800 p-6">
        <div class="flex flex-col lg:flex-row lg:items-start lg:gap-8">
          <div class="flex-1">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-gray-100 mb-3">{{ $t('Selected Student') }}</h3>
            <div class="space-y-2 text-sm">
              <p><span class="font-medium text-slate-700 dark:text-gray-300">{{ $t('Name:') }}</span> {{ selectedStudent.full_name }}</p>
              <p><span class="font-medium text-slate-700 dark:text-gray-300">{{ $t('ID:') }}</span> {{ selectedStudent.id }}</p>
            </div>
            <div class="mt-4">
              <button @click="generateQr" class="px-5 py-2.5 rounded-xl bg-blue-600 text-sm font-semibold text-white hover:bg-blue-700 transition">
                {{ $t('Generate QR Code') }}
              </button>
            </div>
          </div>

          <div v-if="qrData" class="mt-6 lg:mt-0 flex-shrink-0">
            <div class="bg-white p-4 rounded-xl border border-slate-200 dark:bg-gray-800 dark:border-gray-700">
              <div class="text-center mb-3">
                <p class="text-sm font-medium text-slate-700 dark:text-gray-300">{{ $t('Scan QR Code') }}</p>
                <p v-if="!qrExpired" class="text-xs text-slate-500 dark:text-gray-400">
                  {{ $t('Expires in') }} <span class="font-mono font-bold text-blue-600">{{ formatCountdown(qrCountdown) }}</span>
                </p>
                <p v-else class="text-xs text-red-500 font-semibold">{{ $t('QR Code Expired') }}</p>
              </div>
              <div class="w-48 h-48 mx-auto bg-gray-100 rounded-lg flex items-center justify-center dark:bg-gray-700">
                <img v-if="qrData.url" :src="`https://api.qrserver.com/v1/create-qr-code/?size=192x192&data=${encodeURIComponent(qrData.url)}`" alt="QR Code" class="w-full h-full object-contain p-2" />
              </div>
              <button v-if="qrExpired" @click="generateQr" class="mt-3 w-full px-4 py-2 rounded-lg bg-blue-600 text-sm font-semibold text-white hover:bg-blue-700 transition">
                {{ $t('Regenerate QR') }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Review Card -->
      <div v-if="showReviewCard && pendingLeave" class="bg-white rounded-xl border border-slate-200 shadow-sm dark:bg-gray-900 dark:border-gray-800 p-6">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-gray-100 mb-4">{{ $t('Leave Request Review') }}</h3>
        <div class="grid gap-4 sm:grid-cols-2 text-sm">
          <div><span class="font-medium text-slate-700 dark:text-gray-300">{{ $t('Student:') }}</span> {{ pendingLeave.student?.full_name }}</div>
          <div><span class="font-medium text-slate-700 dark:text-gray-300">{{ $t('Status:') }}</span>
            <span class="ml-2 inline-block px-2 py-0.5 rounded-full text-xs font-semibold" :class="statusColors[pendingLeave.status]">{{ pendingLeave.status }}</span>
          </div>
          <div><span class="font-medium text-slate-700 dark:text-gray-300">{{ $t('Start Date:') }}</span> {{ pendingLeave.start_date }}</div>
          <div><span class="font-medium text-slate-700 dark:text-gray-300">{{ $t('End Date:') }}</span> {{ pendingLeave.end_date }}</div>
          <div class="sm:col-span-2"><span class="font-medium text-slate-700 dark:text-gray-300">{{ $t('Reason:') }}</span> {{ pendingLeave.reason }}</div>
        </div>
        <div v-if="pendingLeave.status === 'pending'" class="mt-6 flex gap-3">
          <button @click="approveLeave(pendingLeave)" class="px-6 py-2.5 rounded-xl bg-green-600 text-sm font-semibold text-white hover:bg-green-700 transition">{{ $t('Approve') }}</button>
          <button @click="openRejectModal(pendingLeave)" class="px-6 py-2.5 rounded-xl bg-red-600 text-sm font-semibold text-white hover:bg-red-700 transition">{{ $t('Reject') }}</button>
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
            <button @click="showApproveConfirm = false" class="px-4 py-2 rounded-lg border border-slate-300 text-sm font-medium hover:bg-slate-50 dark:border-gray-600 dark:text-gray-300">{{ $t('Cancel') }}</button>
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
              <textarea v-model="rejectForm.note" rows="3" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200" />
              <p v-if="rejectForm.errors.note" class="text-red-500 text-xs mt-1">{{ rejectForm.errors.note }}</p>
            </div>
            <div class="flex gap-3 justify-end">
              <button type="button" @click="showRejectModal = false" class="px-4 py-2 rounded-lg border border-slate-300 text-sm dark:border-gray-600 dark:text-gray-300">{{ $t('Cancel') }}</button>
              <button type="submit" :disabled="rejectForm.processing" class="px-4 py-2 rounded-lg bg-red-600 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-50">{{ $t('Confirm Reject') }}</button>
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
              <textarea v-model="revokeForm.note" rows="3" required class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200" />
            </div>
            <div class="flex gap-3 justify-end">
              <button type="button" @click="showRevokeModal = false" class="px-4 py-2 rounded-lg border border-slate-300 text-sm dark:border-gray-600 dark:text-gray-300">{{ $t('Cancel') }}</button>
              <button type="submit" :disabled="revokeForm.processing" class="px-4 py-2 rounded-lg bg-orange-600 text-sm font-semibold text-white hover:bg-orange-700 disabled:opacity-50">{{ $t('Confirm Revoke') }}</button>
            </div>
          </form>
        </div>
      </div>
    </transition>
  </DashboardLayout>
</template>
