<script setup>
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { router } from '@inertiajs/vue3'
import { onMounted, ref, watch } from 'vue'
import Breadcrumbs from '../../../components/ui/breadcrumbs/Breadcrumbs.vue'
import PageHero from '../../../components/ui/page-hero/PageHero.vue'
import { useI18n } from '../../../i18n'

const props = defineProps({
  filters: { type: Object, default: () => ({}) },
  blocks: { type: Object, default: () => ({ data: [], links: [], from: 0, to: 0, total: 0 }) },
  canUnlock: { type: Boolean, default: false },
})

const { t } = useI18n()

const rows = ref(props.blocks)
const loading = ref(false)
const blockType = ref(props.filters.block_type ?? '')
const status = ref(props.filters.status ?? '')
const search = ref(props.filters.search ?? '')
const dateFrom = ref(props.filters.date_from ?? '')
const dateTo = ref(props.filters.date_to ?? '')
let timer = null

const typeBadge = {
  absence: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
  hard_lock: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
}
const statusBadge = {
  pending: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
  approved: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
  unlocked: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
  rejected: 'bg-slate-100 text-slate-600 line-through dark:bg-gray-800 dark:text-gray-400',
}

async function fetchBlocks(page = 1) {
  loading.value = true
  const params = new URLSearchParams()
  if (blockType.value) params.set('block_type', blockType.value)
  if (status.value) params.set('status', status.value)
  if (search.value) params.set('search', search.value)
  if (dateFrom.value) params.set('date_from', dateFrom.value)
  if (dateTo.value) params.set('date_to', dateTo.value)
  params.set('page', page)
  try {
    const res = await fetch(`/dashboard/absence-blocks/data?${params.toString()}`)
    rows.value = await res.json()
  } catch {
    rows.value = { data: [], links: [], from: 0, to: 0, total: 0 }
  } finally {
    loading.value = false
  }
}

function applyFilters() {
  clearTimeout(timer)
  timer = setTimeout(() => fetchBlocks(1), 350)
}
watch([blockType, status, search, dateFrom, dateTo], applyFilters)
onMounted(() => fetchBlocks())

// --- actions ---
const approveTarget = ref(null)
const rejectTarget = ref(null)
const rejectComment = ref('')
const unlockTarget = ref(null)

function submit(url, body = {}, done = () => {}) {
  router.post(url, body, { preserveScroll: true, onSuccess: () => { done(); fetchBlocks() } })
}

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Absence Blocklist', current: true },
]
</script>

<template>
  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero
        eyebrow="Attendance"
        :title="$t('Absence Blocklist')"
        :description="$t('Approve soft locks, or send hard locks to a super admin.')"
      />

      <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-col gap-4 border-b border-slate-200 px-6 py-5 lg:flex-row lg:items-end dark:border-gray-800">
          <div class="flex-1">
            <label class="mb-1 block text-xs font-medium text-slate-500 dark:text-gray-400">{{ $t('Search') }}</label>
            <input v-model="search" type="text" :placeholder="$t('Student name or tel...')" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-500 dark:text-gray-400">{{ $t('Type') }}</label>
            <select v-model="blockType" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
              <option value="">{{ $t('All') }}</option>
              <option value="absence">{{ $t('Soft lock') }}</option>
              <option value="hard_lock">{{ $t('Hard lock') }}</option>
            </select>
          </div>
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-500 dark:text-gray-400">{{ $t('Status') }}</label>
            <select v-model="status" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
              <option value="">{{ $t('All') }}</option>
              <option value="pending">{{ $t('Pending') }}</option>
              <option value="approved">{{ $t('Approved') }}</option>
              <option value="rejected">{{ $t('Rejected') }}</option>
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
                <th class="px-6 py-3 text-left text-slate-600 dark:text-gray-300">{{ $t('Student') }}</th>
                <th class="px-6 py-3 text-left text-slate-600 dark:text-gray-300">{{ $t('Course / Class') }}</th>
                <th class="px-6 py-3 text-left text-slate-600 dark:text-gray-300">{{ $t('Type') }}</th>
                <th class="px-6 py-3 text-left text-slate-600 dark:text-gray-300">{{ $t('Status') }}</th>
                <th class="px-6 py-3 text-left text-slate-600 dark:text-gray-300">{{ $t('Blocked / Resolved') }}</th>
                <th class="px-6 py-3 text-left text-slate-600 dark:text-gray-300">{{ $t('Comment') }}</th>
                <th class="px-6 py-3 text-right text-slate-600 dark:text-gray-300">{{ $t('Actions') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="b in rows.data" :key="b.id" class="border-t border-slate-200 hover:bg-slate-50 dark:border-gray-800 dark:hover:bg-gray-800">
                <td class="px-6 py-4">
                  <div class="font-medium text-slate-900 dark:text-gray-100">{{ b.student.full_name ?? '-' }}</div>
                  <div class="text-xs text-slate-500 dark:text-gray-400">{{ b.student.tel }}</div>
                </td>
                <td class="px-6 py-4 text-slate-600 dark:text-gray-400">
                  <div>{{ b.course ?? '-' }}</div>
                  <div class="text-xs text-slate-400">{{ b.study_class ?? '-' }}</div>
                </td>
                <td class="px-6 py-4">
                  <span class="inline-block rounded-full px-2 py-0.5 text-xs font-semibold" :class="typeBadge[b.block_type]">
                    {{ b.block_type === 'hard_lock' ? $t('Hard lock') : $t('Soft lock') }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <span class="inline-block rounded-full px-2 py-0.5 text-xs font-semibold" :class="statusBadge[b.status]">{{ $t(b.status) }}</span>
                </td>
                <td class="px-6 py-4 text-xs text-slate-500 dark:text-gray-400">
                  <div>{{ b.blocked_at }}</div>
                  <div v-if="b.approved_at" class="text-emerald-600 dark:text-emerald-400">✓ {{ b.approved_at }} · {{ b.approved_by }}</div>
                  <div v-else-if="b.rejected_at" class="text-slate-400">✕ {{ b.rejected_at }}</div>
                </td>
                <td class="px-6 py-4 max-w-xs truncate text-slate-600 dark:text-gray-400">{{ b.admin_comment ?? '-' }}</td>
                <td class="px-6 py-4 space-x-2 text-right">
                  <template v-if="b.block_type === 'absence' && b.status === 'pending'">
                    <button @click="approveTarget = b" class="rounded-lg border border-green-200 bg-green-50 px-3 py-1.5 text-xs text-green-700 hover:bg-green-100 dark:border-green-900/40 dark:bg-green-900/20 dark:text-green-400">{{ $t('Approve') }}</button>
                    <button @click="rejectTarget = b; rejectComment = ''" class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs text-red-700 hover:bg-red-100 dark:border-red-900/40 dark:bg-red-900/20 dark:text-red-400">{{ $t('Reject') }}</button>
                  </template>
                  <button
                    v-else-if="b.block_type === 'hard_lock' && b.status === 'pending' && canUnlock"
                    @click="unlockTarget = b"
                    class="rounded-lg border border-red-300 bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700"
                  >{{ $t('Unlock') }}</button>
                  <span v-else class="text-xs text-slate-400">—</span>
                </td>
              </tr>
              <tr v-if="!rows.data?.length && !loading">
                <td colspan="7" class="py-10 text-center text-slate-500 dark:text-gray-400">{{ $t('No blocks found.') }}</td>
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
              @click="link.url && fetchBlocks(new URL(link.url).searchParams.get('page') || 1)"
              class="rounded-lg border px-3 py-2 text-sm transition dark:border-gray-700 dark:text-gray-300"
              :class="{ 'bg-blue-600 text-white border-blue-600': link.active, 'hover:bg-gray-100 dark:hover:bg-gray-800': !link.active, 'opacity-40 pointer-events-none': !link.url }"
              v-html="link.label"
            />
          </div>
        </div>
      </div>
    </section>

    <!-- Approve -->
    <transition name="fade">
      <div v-if="approveTarget" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm" @click.self="approveTarget = null">
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-lg dark:bg-gray-900">
          <h3 class="mb-2 text-lg font-semibold text-slate-900 dark:text-gray-100">{{ $t('Approve absence block') }}</h3>
          <p class="mb-6 text-sm text-slate-600 dark:text-gray-400">
            {{ $t('The student is unlocked and gets the post-approval allowance. This covers every open block for this student and course.') }}
          </p>
          <div class="flex justify-end gap-3">
            <button @click="approveTarget = null" class="rounded-lg border border-slate-300 px-4 py-2 text-sm dark:border-gray-600 dark:text-gray-300">{{ $t('Cancel') }}</button>
            <button @click="submit(`/dashboard/absence-blocks/blocks/${approveTarget.id}/approve`, {}, () => approveTarget = null)" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">{{ $t('Approve') }}</button>
          </div>
        </div>
      </div>
    </transition>

    <!-- Reject -->
    <transition name="fade">
      <div v-if="rejectTarget" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm" @click.self="rejectTarget = null">
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-lg dark:bg-gray-900">
          <h3 class="mb-4 text-lg font-semibold text-slate-900 dark:text-gray-100">{{ $t('Reject absence block') }}</h3>
          <form @submit.prevent="submit(`/dashboard/absence-blocks/blocks/${rejectTarget.id}/reject`, { admin_comment: rejectComment }, () => rejectTarget = null)" class="space-y-4">
            <div>
              <label class="mb-1 block text-sm font-medium text-slate-700 dark:text-gray-300">{{ $t('Comment (optional)') }}</label>
              <textarea v-model="rejectComment" rows="3" class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200" />
            </div>
            <p class="text-xs text-slate-500 dark:text-gray-400">{{ $t('Rejecting clears the block and unlocks the student. A new block can form if absences continue.') }}</p>
            <div class="flex justify-end gap-3">
              <button type="button" @click="rejectTarget = null" class="rounded-lg border border-slate-300 px-4 py-2 text-sm dark:border-gray-600 dark:text-gray-300">{{ $t('Cancel') }}</button>
              <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">{{ $t('Reject') }}</button>
            </div>
          </form>
        </div>
      </div>
    </transition>

    <!-- Unlock -->
    <transition name="fade">
      <div v-if="unlockTarget" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm" @click.self="unlockTarget = null">
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-lg dark:bg-gray-900">
          <h3 class="mb-2 text-lg font-semibold text-slate-900 dark:text-gray-100">{{ $t('Unlock hard lock') }}</h3>
          <p class="mb-6 text-sm text-slate-600 dark:text-gray-400">
            {{ $t('This clears the hard lock and RESETS the absence cycle for this student and course. Counting starts again from zero.') }}
          </p>
          <div class="flex justify-end gap-3">
            <button @click="unlockTarget = null" class="rounded-lg border border-slate-300 px-4 py-2 text-sm dark:border-gray-600 dark:text-gray-300">{{ $t('Cancel') }}</button>
            <button @click="submit(`/dashboard/absence-blocks/blocks/${unlockTarget.id}/unlock`, {}, () => unlockTarget = null)" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">{{ $t('Unlock & reset') }}</button>
          </div>
        </div>
      </div>
    </transition>
  </DashboardLayout>
</template>
