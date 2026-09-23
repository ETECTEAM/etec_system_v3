<script setup>
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { TriangleAlert } from '@lucide/vue'
import { router } from '@inertiajs/vue3'
import { onMounted, ref, watch } from 'vue'
import Breadcrumbs from '../../../components/ui/breadcrumbs/Breadcrumbs.vue'
import PageHero from '../../../components/ui/page-hero/PageHero.vue'
import { useI18n } from '../../../i18n'

const props = defineProps({
  filters: { type: Object, default: () => ({}) },
  blocks: { type: Object, default: () => ({ data: [], links: [], from: 0, to: 0, total: 0 }) },
})

const { t } = useI18n()

const rows = ref(props.blocks)
const loading = ref(false)
const status = ref(props.filters.status ?? '')
let timer = null

// Every status except approved_unblock still blocks the instructor (see
// App\Models\InstructorAttendanceBlock::BLOCKING_STATUSES) — those are the
// rows this page's Approve action applies to. There's no reject action: an
// admin who doesn't want to unblock someone just doesn't click Approve.
const statusBadge = {
  active: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
  pending_review: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
  approved_unblock: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
}
const statusLabel = {
  active: 'Blocked',
  pending_review: 'Pending review',
  approved_unblock: 'Unblocked',
}

async function fetchBlocks(page = 1) {
  loading.value = true
  const params = new URLSearchParams()
  if (status.value) params.set('status', status.value)
  params.set('page', page)
  try {
    const res = await fetch(`/dashboard/instructor-attendance-blocks/data?${params.toString()}`)
    rows.value = await res.json()
  } catch {
    rows.value = { data: [], links: [], from: 0, to: 0, total: 0 }
  } finally {
    loading.value = false
  }
}

function applyFilters() {
  clearTimeout(timer)
  timer = setTimeout(() => fetchBlocks(1), 250)
}
watch(status, applyFilters)
onMounted(() => fetchBlocks())

const approveTarget = ref(null)
const approvePermissionTarget = ref(null)

function submit(url, body = {}, done = () => {}) {
  router.post(url, body, { preserveScroll: true, onSuccess: () => { done(); fetchBlocks() } })
}

const reasonBadge = {
  general: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
  permission: 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300',
}
const reasonLabel = {
  general: 'General claim',
  permission: 'Permission claim',
}

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Instructor Attendance Blocks', current: true },
]
</script>

<template>
  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero
        eyebrow="Attendance"
        :title="$t('Instructor Attendance Blocks')"
        :description="$t('Review instructors blocked after missing attendance, and restore their access on every class at once.')"
      />

      <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-col gap-4 border-b border-slate-200 px-6 py-5 lg:flex-row lg:items-end dark:border-gray-800">
          <div>
            <label class="mb-1 block text-xs font-medium text-slate-500 dark:text-gray-400">{{ $t('Status') }}</label>
            <select v-model="status" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
              <option value="">{{ $t('All') }}</option>
              <option value="active">{{ $t('Blocked') }}</option>
              <option value="pending_review">{{ $t('Pending review') }}</option>
              <option value="approved_unblock">{{ $t('Unblocked') }}</option>
            </select>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-800">
                <th class="px-6 py-3 text-left text-slate-600 dark:text-gray-300">{{ $t('Instructor') }}</th>
                <th class="px-6 py-3 text-left text-slate-600 dark:text-gray-300">{{ $t('Reason') }}</th>
                <th class="px-6 py-3 text-left text-slate-600 dark:text-gray-300">{{ $t('Status') }}</th>
                <th class="px-6 py-3 text-left text-slate-600 dark:text-gray-300">{{ $t('Blocked At') }}</th>
                <th class="px-6 py-3 text-left text-slate-600 dark:text-gray-300">{{ $t('Reviewed') }}</th>
                <th class="px-6 py-3 text-right text-slate-600 dark:text-gray-300">{{ $t('Actions') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="b in rows.data" :key="b.id" class="border-t border-slate-200 hover:bg-slate-50 dark:border-gray-800 dark:hover:bg-gray-800">
                <td class="px-6 py-4">
                  <div class="font-medium text-slate-900 dark:text-gray-100">{{ b.instructor?.name ?? '-' }}</div>
                  <div class="text-xs text-slate-500 dark:text-gray-400">{{ b.instructor?.instructor_data?.phone ?? '-' }}</div>
                </td>
                <td class="px-6 py-4 max-w-sm text-slate-600 dark:text-gray-400">
                  <div>{{ b.reason }}</div>
                  <span
                    v-if="b.unblock_reason_type"
                    class="mt-1.5 inline-block rounded-full px-2 py-0.5 text-[11px] font-semibold"
                    :class="reasonBadge[b.unblock_reason_type]"
                  >
                    {{ $t(reasonLabel[b.unblock_reason_type]) }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <span class="inline-block rounded-full px-2 py-0.5 text-xs font-semibold" :class="statusBadge[b.status]">
                    {{ $t(statusLabel[b.status] ?? b.status) }}
                  </span>
                </td>
                <td class="px-6 py-4 text-xs text-slate-500 dark:text-gray-400">{{ b.blocked_at ? new Date(b.blocked_at).toLocaleString() : '-' }}</td>
                <td class="px-6 py-4 text-xs text-slate-500 dark:text-gray-400">
                  <template v-if="b.reviewed_at">
                    <div>{{ new Date(b.reviewed_at).toLocaleString() }}</div>
                    <div>{{ b.reviewer?.name ?? '-' }}</div>
                  </template>
                  <span v-else>—</span>
                </td>
                <td class="px-6 py-4 text-right">
                  <div v-if="b.status !== 'approved_unblock'" class="flex flex-wrap items-center justify-end gap-2">
                    <button
                      @click="approveTarget = b"
                      class="rounded-lg border border-green-200 bg-green-50 px-3 py-1.5 text-xs text-green-700 hover:bg-green-100 dark:border-green-900/40 dark:bg-green-900/20 dark:text-green-400"
                    >{{ $t('Approve') }}</button>
                    <button
                      @click="approvePermissionTarget = b"
                      class="rounded-lg border border-violet-200 bg-violet-50 px-3 py-1.5 text-xs text-violet-700 hover:bg-violet-100 dark:border-violet-900/40 dark:bg-violet-900/20 dark:text-violet-400"
                    >{{ $t('Approve After Permission') }}</button>
                  </div>
                  <span v-else class="text-xs text-slate-400">—</span>
                </td>
              </tr>
              <tr v-if="!rows.data?.length && !loading">
                <td colspan="6" class="py-10 text-center text-slate-500 dark:text-gray-400">{{ $t('No attendance blocks found.') }}</td>
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
          <h3 class="mb-2 text-lg font-semibold text-slate-900 dark:text-gray-100">{{ $t('Unblock instructor') }}</h3>
          <p class="mb-6 text-sm text-slate-600 dark:text-gray-400">
            {{ $t('This restores attendance tracking for :name on every class they teach, immediately.', { name: approveTarget?.instructor?.name ?? '' }) }}
          </p>
          <div class="flex justify-end gap-3">
            <button @click="approveTarget = null" class="rounded-lg border border-slate-300 px-4 py-2 text-sm dark:border-gray-600 dark:text-gray-300">{{ $t('Cancel') }}</button>
            <button @click="submit(`/dashboard/instructor-attendance-blocks/${approveTarget.id}/approve`, {}, () => approveTarget = null)" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">{{ $t('Approve') }}</button>
          </div>
        </div>
      </div>
    </transition>

    <!-- Approve After Permission -->
    <transition name="fade">
      <div v-if="approvePermissionTarget" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm" @click.self="approvePermissionTarget = null">
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-lg dark:bg-gray-900">
          <h3 class="mb-2 text-lg font-semibold text-slate-900 dark:text-gray-100">{{ $t('Approve after permission') }}</h3>
          <p class="mb-2 text-sm text-slate-600 dark:text-gray-400">
            {{ $t(':name said they missed this session because they had approved permission/leave. Approving restores attendance tracking and backfills EVERY stuck session across all their classes from last week\'s attendance — not just the one shown here.', { name: approvePermissionTarget?.instructor?.name ?? '' }) }}
          </p>
          <div class="mb-6 flex items-start gap-2 rounded-lg bg-slate-50 px-3 py-2.5 text-xs text-slate-600 dark:bg-gray-800 dark:text-gray-300">
            <TriangleAlert class="mt-0.5 h-4 w-4 shrink-0 text-amber-500" />
            <span>
              {{ $t('Per student, per class: attendance is copied from 7 days earlier. A student who ends up absent may still trigger a separate student absence block.') }}
            </span>
          </div>
          <p v-if="approvePermissionTarget?.unblock_reason_type" class="mb-2 text-xs text-slate-500 dark:text-gray-400">
            {{ $t('Instructor\'s stated claim') }}:
            <span
              class="ml-1 inline-block rounded-full px-2 py-0.5 font-semibold"
              :class="reasonBadge[approvePermissionTarget.unblock_reason_type]"
            >{{ $t(reasonLabel[approvePermissionTarget.unblock_reason_type]) }}</span>
          </p>
          <div class="flex justify-end gap-3">
            <button @click="approvePermissionTarget = null" class="rounded-lg border border-slate-300 px-4 py-2 text-sm dark:border-gray-600 dark:text-gray-300">{{ $t('Cancel') }}</button>
            <button @click="submit(`/dashboard/instructor-attendance-blocks/${approvePermissionTarget.id}/approve-after-permission`, {}, () => approvePermissionTarget = null)" class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-700">{{ $t('Approve After Permission') }}</button>
          </div>
        </div>
      </div>
    </transition>
  </DashboardLayout>
</template>
