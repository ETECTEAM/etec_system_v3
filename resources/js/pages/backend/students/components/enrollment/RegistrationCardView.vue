<script setup>
import {
  UserRound, BookOpen, CalendarDays, Clock3, DollarSign, GraduationCap,
  UserPlus, UserCheck, Printer, Pencil, ArrowRightLeft,
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
  registrationTypeBadge,
  registrationPageLabel,
  goRegistrationPage,
  printReceipt,
  approveRegistration,
  openPartialPaymentModal,
  openMoveModal,
  startEdit,
} = useEnrollmentRegistrations();

function total(row) {
  return (Number(row.fee_amount) + Number(row.document_fee_amount)).toFixed(2);
}

function paidState(row) {
  if (isPendingRegistration(row)) return "pending";
  if (row.payment_status === "Paid") return "paid";
  if (row.payment_status === "Partial") return "partial";
  return "unpaid";
}

const STATE_STYLES = {
  paid: { card: "border-emerald-200 hover:border-emerald-300 dark:border-emerald-500/20", pill: "bg-emerald-50 text-emerald-700 ring-emerald-600/20", dot: "bg-emerald-500", label: "Paid" },
  partial: { card: "border-amber-200 hover:border-amber-300 dark:border-amber-500/20", pill: "bg-amber-50 text-amber-700 ring-amber-600/20", dot: "bg-amber-500", label: "Partial" },
  pending: { card: "border-amber-200 hover:border-amber-300 dark:border-amber-500/20", pill: "bg-amber-50 text-amber-700 ring-amber-600/20", dot: "bg-amber-500", label: "Pending Approval" },
  unpaid: { card: "border-rose-200 hover:border-rose-300 dark:border-rose-500/20", pill: "bg-rose-50 text-rose-700 ring-rose-600/20", dot: "bg-rose-500", label: "Unpaid" },
};

function style(row) {
  return STATE_STYLES[paidState(row)];
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

    <div v-else class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
      <div
        v-for="row in registrations"
        :key="row.enrollment_id"
        :class="[
          'group relative flex flex-col rounded-2xl border bg-white shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:shadow-xl dark:bg-gray-900',
          style(row).card,
        ]"
      >
        <div class="flex flex-1 flex-col p-5 sm:p-6">
          <!-- Header -->
          <div class="flex items-start justify-between gap-3">
            <div class="flex min-w-0 items-start gap-3">
              <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 ring-1 ring-indigo-100 dark:bg-indigo-500/10 dark:text-indigo-400 dark:ring-indigo-500/20">
                <UserRound class="h-5 w-5" />
              </div>
              <div class="min-w-0">
                <div class="flex items-center gap-1.5">
                  <h3 class="truncate text-sm font-semibold text-slate-900 transition-colors group-hover:text-indigo-600 dark:text-gray-100 dark:group-hover:text-indigo-400 sm:text-base">
                    {{ row.name }}
                  </h3>
                  <span :class="['shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide', registrationTypeBadge(row).classes]">
                    {{ $t(registrationTypeBadge(row).label) }}
                  </span>
                </div>
                <div class="mt-1.5 flex items-center gap-2">
                  <span class="text-[11px] font-medium uppercase tracking-wider text-slate-400 dark:text-gray-500">{{ $t('Phone') }}</span>
                  <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-semibold leading-none text-slate-600 tabular-nums dark:bg-gray-800 dark:text-gray-400">{{ row.phone || '—' }}</span>
                </div>
              </div>
            </div>

            <div class="flex shrink-0 items-center gap-1">
              <button
                v-if="!isPendingRegistration(row)"
                type="button"
                class="inline-flex h-7 w-7 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                :title="$t('Edit')"
                @click="startEdit(row)"
              >
                <Pencil class="h-3.5 w-3.5" />
              </button>
              <button
                v-if="!isPendingRegistration(row) && !needsManualScheduling(row)"
                type="button"
                class="inline-flex h-7 w-7 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 disabled:cursor-not-allowed disabled:opacity-40 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                :disabled="row.payment_status !== 'Paid'"
                :title="row.payment_status === 'Paid' ? $t('Move to Another Class') : $t('Record payment first.')"
                @click="openMoveModal(row)"
              >
                <ArrowRightLeft class="h-3.5 w-3.5" />
              </button>
            </div>
          </div>

          <!-- Information -->
          <div class="mt-4 flex-1 space-y-3 sm:mt-5">
            <div class="flex items-center justify-between gap-2">
              <span :class="rowLabel"><UserRound class="h-3.5 w-3.5 shrink-0" /> {{ $t('Gender') }}</span>
              <span :class="rowValue">{{ row.gender }}</span>
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
              <span :class="rowLabel"><Clock3 class="h-3.5 w-3.5 shrink-0" /> {{ $t('Status') }}</span>
              <span :class="['inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset', style(row).pill]">
                <span class="h-1.5 w-1.5 rounded-full" :class="style(row).dot"></span>
                {{ $t(style(row).label) }}
              </span>
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

          <!-- Footer: one clean primary action -->
          <button
            v-if="isPendingRegistration(row)"
            type="button"
            class="mt-4 inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-blue-900 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-500 sm:mt-5"
            @click="approveRegistration(row)"
          >
            <UserCheck class="h-4 w-4" />
            {{ $t('Approve Registration') }}
          </button>

          <button
            v-else-if="needsManualScheduling(row)"
            type="button"
            class="mt-4 inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 sm:mt-5"
            @click="openMoveModal(row)"
          >
            <UserPlus class="h-4 w-4" />
            {{ $t('Assign to Class') }}
          </button>

          <button
            v-else
            type="button"
            :disabled="printingId === row.enrollment_id"
            class="mt-4 inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-blue-900 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-blue-600 dark:hover:bg-blue-500 sm:mt-5"
            @click="row.payment_status === 'Paid' ? printReceipt(row) : openPartialPaymentModal(row)"
          >
            <Printer class="h-4 w-4" />
            {{ row.payment_status === 'Paid' ? $t('Print Receipt') : $t('Record Payment') }}
          </button>
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
