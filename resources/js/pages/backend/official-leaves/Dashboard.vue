<script setup>
import { Head } from '@inertiajs/vue3'
import { Search, UserRoundSearch } from '@lucide/vue'
import { Breadcrumbs } from '../../../components/ui/breadcrumbs'
import { EmptyState } from '../../../components/ui/empty-state'
import { PageHero } from '../../../components/ui/page-hero'
import DashboardLayout from '../../../layouts/DashboardLayout.vue'
import QrRequestModal from './components/QrRequestModal.vue'
import { useOfficialLeaveDashboard } from './composables/useOfficialLeaveDashboard'

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Official Leave', current: true },
]

const {
  search,
  isSearching,
  hasSearched,
  results,
  debounceSearch,
  activeStudent,
  modalOpen,
  sessionState,
  qrSession,
  reviewLeave,
  remainingSeconds,
  deciding,
  rejectNote,
  openRequestModal,
  generateQr,
  closeModal,
  approveReview,
  rejectReview,
} = useOfficialLeaveDashboard()
</script>

<template>
  <Head :title="$t('Official Leave')" />

  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero
        eyebrow="Office Desk"
        :title="$t('Official Leave')"
        :description="$t('Search a student, generate their QR, and review the request the moment it arrives.')"
      />

      <!-- Student search -->
      <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:p-6">
        <label for="official-leave-search" class="text-xs font-black uppercase tracking-[0.14em] text-slate-500 dark:text-gray-400">
          {{ $t('Student search') }}
        </label>

        <div class="relative mt-2">
          <Search class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
          <input
            id="official-leave-search"
            v-model="search"
            type="text"
            :placeholder="$t('Full name or student ID...')"
            class="h-12 w-full rounded-xl border border-slate-200 bg-white pl-11 pr-4 text-sm font-semibold outline-none transition focus:border-blue-400 focus:ring-4 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100 dark:focus:ring-blue-500/10"
            @input="debounceSearch"
          />
        </div>

        <!-- Results -->
        <div v-if="isSearching" class="mt-4 space-y-3">
          <div v-for="i in 2" :key="i" class="h-24 animate-pulse rounded-xl bg-slate-100 dark:bg-gray-800" />
        </div>

        <div v-else-if="results.length" class="mt-4 grid gap-3 sm:grid-cols-2">
          <article
            v-for="student in results"
            :key="student.id"
            class="flex items-center gap-4 rounded-xl border border-slate-200 p-4 transition hover:border-blue-300 hover:shadow-sm dark:border-gray-700"
          >
            <img
              v-if="student.photo_url"
              :src="student.photo_url"
              :alt="student.full_name"
              class="h-12 w-12 shrink-0 rounded-full object-cover"
            />
            <span
              v-else
              class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-slate-100 text-sm font-black uppercase text-slate-500 dark:bg-gray-800 dark:text-gray-400"
            >
              {{ student.full_name?.slice(0, 1) }}
            </span>

            <div class="min-w-0 flex-1">
              <p class="truncate font-black text-slate-950 dark:text-gray-100">{{ student.full_name }}</p>
              <p class="truncate text-xs font-semibold text-slate-500 dark:text-gray-400">
                #{{ student.id }} · {{ student.classes?.length ? student.classes.join(', ') : (student.course ?? '-') }}
              </p>
              <p class="mt-1 flex items-center gap-2 text-xs font-semibold">
                <span
                  :class="[
                    'inline-flex items-center rounded-lg border px-2 py-0.5 text-[11px] font-black',
                    student.block.blocked
                      ? 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300'
                      : 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300',
                  ]"
                >
                  {{ student.block.blocked ? $t('Blocked') : $t('OK') }}
                </span>
                <span class="text-slate-500 dark:text-gray-400">
                  {{ $t('Block score') }}: {{ student.block.score }}/{{ student.block.threshold }}
                </span>
              </p>
            </div>

            <button
              type="button"
              class="inline-flex h-10 shrink-0 items-center gap-2 rounded-lg bg-blue-600 px-4 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700"
              @click="openRequestModal(student)"
            >
              <UserRoundSearch class="hidden h-4 w-4 sm:block" />
              {{ $t('Request Leave') }}
            </button>
          </article>
        </div>

        <EmptyState
          v-else-if="hasSearched && !results.length"
          class="mt-4"
          :icon="UserRoundSearch"
          :title="'No students found'"
          :description="'No active student matches this name or ID. Try a different search.'"
        />
      </div>
    </section>

    <QrRequestModal
      v-if="modalOpen && activeStudent"
      :student="activeStudent"
      :session-state="sessionState"
      :qr-url="qrSession?.url ?? ''"
      :remaining-seconds="remainingSeconds"
      :leave="reviewLeave"
      :deciding="deciding"
      v-model:reject-note="rejectNote"
      @close="closeModal"
      @regenerate="generateQr"
      @approve="approveReview"
      @reject="rejectReview"
    />
  </DashboardLayout>
</template>
