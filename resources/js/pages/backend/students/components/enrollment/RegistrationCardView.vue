<script setup>
import {
  UserRound, BookOpen, CalendarDays, Clock3, DollarSign, GraduationCap,
  UserPlus, UserCheck, Printer, Pencil, ArrowRightLeft, X, Check,
} from "@lucide/vue";
import { useEnrollmentRegistrations } from "@/composables/useEnrollmentRegistrations";

const {
  registrations,
  loading,
  pagination,
  printingId,
  needsManualScheduling,
  isPendingRegistration,
  scheduleLabel,
  requestedScheduleLabel,
  registrationPageLabel,
  goRegistrationPage,
  printReceipt,
  approveRegistration,
  openPartialPaymentModal,
  openMoveModal,
  editDraft,
  editErrors,
  editSaving,
  editNameLiveError,
  isEditing,
  startEdit,
  cancelEdit,
  saveEdit,
} = useEnrollmentRegistrations();

function total(row) {
  return (Number(row.fee_amount) + Number(row.document_fee_amount)).toFixed(2);
}

function statusPill(row) {
  if (isPendingRegistration(row)) {
    return { label: "Pending Approval", dot: "bg-amber-500", classes: "bg-amber-50 text-amber-700 ring-amber-600/20" };
  }
  if (row.payment_status === "Paid") {
    return { label: "Paid", dot: "bg-emerald-500", classes: "bg-emerald-50 text-emerald-700 ring-emerald-600/20" };
  }
  if (row.payment_status === "Partial") {
    return { label: "Partial", dot: "bg-amber-500", classes: "bg-amber-50 text-amber-700 ring-amber-600/20" };
  }
  return { label: "Unpaid", dot: "bg-rose-500", classes: "bg-rose-50 text-rose-700 ring-rose-600/20" };
}

const rowLabel = "flex items-center gap-2 text-slate-500 dark:text-gray-400 text-xs sm:text-sm";
const rowValue = "text-xs sm:text-sm font-medium text-slate-800 text-right truncate dark:text-gray-200";
</script>

<template>
  <div class="w-full">
    <div
      v-if="registrations.length === 0"
      class="rounded-2xl border border-dashed border-slate-300 bg-white py-14 text-center text-sm text-slate-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400"
    >
      {{ loading ? $t('Loading...') : $t('No public registrations yet.') }}
    </div>

    <div v-else class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
      <div
        v-for="row in registrations"
        :key="row.enrollment_id"
        class="group relative flex flex-col rounded-2xl border border-slate-200 bg-white shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:shadow-xl dark:border-gray-800 dark:bg-gray-900"
      >
        <div class="flex flex-1 flex-col p-5 sm:p-6">
          <!-- Header -->
          <div class="flex items-start justify-between gap-3">
            <div class="flex min-w-0 items-start gap-3">
              <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 ring-1 ring-indigo-100 dark:bg-indigo-500/10 dark:text-indigo-400 dark:ring-indigo-500/20">
                <UserRound class="h-5 w-5" />
              </div>
              <div class="min-w-0">
                <template v-if="isEditing(row)">
                  <input
                    v-model="editDraft.name"
                    type="text"
                    class="w-full rounded-lg border px-2 py-1 text-sm font-semibold outline-none transition focus:ring-2"
                    :class="editNameLiveError || editErrors.name ? 'border-red-300 focus:border-red-500 focus:ring-red-100' : 'border-slate-300 focus:border-blue-600 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200'"
                  >
                  <p v-if="editNameLiveError || editErrors.name" class="mt-1 text-[11px] font-semibold text-red-600">{{ editNameLiveError || editErrors.name[0] }}</p>
                </template>
                <template v-else>
                  <h3 class="truncate text-sm font-semibold text-slate-900 transition-colors group-hover:text-indigo-600 dark:text-gray-100 dark:group-hover:text-indigo-400 sm:text-base">
                    {{ row.name }}
                  </h3>
                  <p class="mt-1 truncate text-[11px] font-medium text-slate-500 dark:text-gray-400">{{ row.phone }}</p>
                </template>
              </div>
            </div>

            <span
              :class="[
                'inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset',
                statusPill(row).classes,
              ]"
            >
              <span class="h-1.5 w-1.5 rounded-full" :class="statusPill(row).dot"></span>
              {{ $t(statusPill(row).label) }}
            </span>
          </div>

          <!-- Information -->
          <div class="mt-4 flex-1 space-y-3 sm:mt-5">
            <div class="flex items-center justify-between gap-2">
              <span :class="rowLabel"><UserRound class="h-3.5 w-3.5 shrink-0" /> {{ $t('Gender') }}</span>
              <template v-if="isEditing(row)">
                <div class="inline-flex overflow-hidden rounded-lg border border-slate-300 dark:border-gray-600">
                  <button type="button" class="px-2.5 py-0.5 text-[11px] font-semibold transition" :class="editDraft.gender === 'male' ? 'bg-blue-600 text-white' : 'bg-white text-slate-600 dark:bg-gray-800 dark:text-gray-300'" @click="editDraft.gender = 'male'">{{ $t('Male') }}</button>
                  <button type="button" class="border-l border-slate-300 px-2.5 py-0.5 text-[11px] font-semibold transition dark:border-gray-600" :class="editDraft.gender === 'female' ? 'bg-blue-600 text-white' : 'bg-white text-slate-600 dark:bg-gray-800 dark:text-gray-300'" @click="editDraft.gender = 'female'">{{ $t('Female') }}</button>
                </div>
              </template>
              <span v-else :class="rowValue">{{ row.gender }}</span>
            </div>

            <div v-if="isEditing(row)" class="flex items-center justify-between gap-2">
              <span :class="rowLabel">Phone</span>
              <input v-model="editDraft.phone" type="text" class="w-40 rounded-lg border border-slate-300 px-2 py-1 text-xs outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
            </div>

            <div class="flex items-center justify-between gap-2">
              <span :class="rowLabel"><BookOpen class="h-3.5 w-3.5 shrink-0" /> {{ $t('Class') }}</span>
              <span :class="rowValue">{{ needsManualScheduling(row) ? (row.course_title || '-') : row.class_title }}</span>
            </div>

            <div v-if="!needsManualScheduling(row) && row.course_title" class="flex items-center justify-between gap-2">
              <span :class="rowLabel"><GraduationCap class="h-3.5 w-3.5 shrink-0" /> {{ $t('Course') }}</span>
              <span :class="rowValue">{{ row.course_title }}</span>
            </div>

            <div class="flex items-center justify-between gap-2">
              <span :class="rowLabel"><CalendarDays class="h-3.5 w-3.5 shrink-0" /> {{ $t('Schedule') }}</span>
              <span :class="rowValue">{{ needsManualScheduling(row) ? requestedScheduleLabel(row) : scheduleLabel(row) }}</span>
            </div>

            <div class="flex items-center justify-between gap-2">
              <span :class="rowLabel"><Clock3 class="h-3.5 w-3.5 shrink-0" /> {{ $t('Registered') }}</span>
              <span :class="rowValue">{{ row.enrolled_at }}</span>
            </div>
          </div>

          <!-- Price -->
          <div class="mt-4 border-t border-slate-100 pt-4 dark:border-gray-800 sm:mt-5 sm:pt-5">
            <div class="flex items-center justify-between gap-2">
              <span :class="rowLabel"><DollarSign class="h-3.5 w-3.5 shrink-0" /> {{ $t('Price') }}</span>
              <span class="text-base font-bold tabular-nums text-slate-900 dark:text-gray-100">${{ total(row) }}</span>
            </div>
            <span
              v-if="needsManualScheduling(row)"
              class="mt-2 inline-flex rounded-full bg-amber-50 px-2.5 py-0.5 text-[11px] font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-400"
            >
              {{ $t('Needs Class') }}
            </span>
          </div>

          <!-- Footer -->
          <div class="mt-4 flex items-center gap-2 sm:mt-5">
            <template v-if="isEditing(row)">
              <button
                type="button"
                :disabled="editSaving || !!editNameLiveError"
                class="inline-flex h-10 flex-1 items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                @click="saveEdit(row)"
              >
                <Check class="h-4 w-4" />
                {{ editSaving ? $t('Saving...') : $t('Save') }}
              </button>
              <button
                type="button"
                :disabled="editSaving"
                class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800"
                @click="cancelEdit"
              >
                <X class="h-4 w-4" />
              </button>
            </template>

            <template v-else-if="isPendingRegistration(row)">
              <button
                type="button"
                class="inline-flex h-10 flex-1 items-center justify-center gap-2 rounded-xl bg-blue-900 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-500"
                @click="approveRegistration(row)"
              >
                <UserCheck class="h-4 w-4" />
                {{ $t('Approve') }}
              </button>
            </template>

            <template v-else>
              <button
                type="button"
                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-300 bg-white text-slate-600 transition hover:bg-slate-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800"
                :title="$t('Edit')"
                @click="startEdit(row)"
              >
                <Pencil class="h-4 w-4" />
              </button>

              <button
                v-if="needsManualScheduling(row)"
                type="button"
                class="inline-flex h-10 flex-1 items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700"
                :title="$t('Assign to Class')"
                @click="openMoveModal(row)"
              >
                <UserPlus class="h-4 w-4" />
                {{ $t('Assign to Class') }}
              </button>
              <button
                v-else
                type="button"
                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-300 bg-white text-slate-600 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800"
                :disabled="row.payment_status !== 'Paid'"
                :title="row.payment_status === 'Paid' ? $t('Move to Another Class') : $t('Record payment first.')"
                @click="openMoveModal(row)"
              >
                <ArrowRightLeft class="h-4 w-4" />
              </button>

              <button
                v-if="!needsManualScheduling(row)"
                type="button"
                class="inline-flex h-10 flex-1 items-center justify-center gap-2 rounded-xl bg-blue-900 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-blue-600 dark:hover:bg-blue-500"
                :disabled="printingId === row.enrollment_id"
                @click="row.payment_status === 'Paid' ? printReceipt(row) : openPartialPaymentModal(row)"
              >
                <Printer class="h-4 w-4" />
                {{ row.payment_status === 'Paid' ? $t('Print Receipt') : $t('Record Payment') }}
              </button>
            </template>
          </div>
        </div>
      </div>
    </div>

    <div v-if="pagination.total > pagination.per_page" class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <p class="text-sm text-slate-500 dark:text-gray-400">
        {{ $t('Showing') }} {{ pagination.from ?? 0 }} {{ $t('to') }} {{ pagination.to ?? 0 }} {{ $t('of') }} {{ pagination.total ?? 0 }}
      </p>
      <div class="flex flex-wrap items-center gap-2">
        <button
          v-for="link in pagination.links"
          :key="link.label"
          type="button"
          :disabled="!link.url || loading"
          class="inline-flex min-w-9 items-center justify-center rounded-lg border px-3 py-2 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-50"
          :class="link.active
            ? 'border-blue-900 bg-blue-900 text-white dark:border-blue-500 dark:bg-blue-600'
            : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800'"
          @click="goRegistrationPage(link)"
        >
          {{ registrationPageLabel(link.label) }}
        </button>
      </div>
    </div>
  </div>
</template>
