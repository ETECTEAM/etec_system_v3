<script setup>
import { computed } from "vue";
import { X } from "@lucide/vue";

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  student: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(["close"]);

const session = computed(() => {
  const s = props.student;
  if (!s) return null;

  const courseFee = Number(s.fee_amount ?? 0);
  const documentFee = Number(s.document_fee_amount ?? 0);
  const totalDue = courseFee + documentFee;
  const totalPaid = Number(s.amount_paid ?? s.deposit_amount ?? 0);
  const remaining = Math.max(totalDue - totalPaid, 0);
  const status = totalPaid <= 0 ? "Unpaid" : totalPaid < totalDue ? "Partial" : "Paid";

  return {
    id: s.id,
    name: s.name,
    gender: s.gender,
    phone: s.phone,
    classTitle: s.class_title,
    course: s.course,
    enrolledAt: s.enrolled_at,
    enrollmentStatus: s.enrollment_status,
    courseFee,
    documentFee,
    fee: totalDue,
    totalPaid,
    remaining,
    status,
    statusRaw: s.payment_status,
    lastPaymentDate: s.payment_date,
    history: s.payment_history ?? [],
  };
});

function formatMoney(value) {
  return `$${Number(value ?? 0).toFixed(2)}`;
}

function formatGender(value) {
  if (!value) return "—";
  const normalized = String(value).toLowerCase();
  if (normalized === "male") return "Male";
  if (normalized === "female") return "Female";
  return value;
}

function statusBadge(status) {
  const value = String(status ?? "").toLowerCase();
  switch (value) {
    case "paid":
    case "completed":
      return "bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400";
    case "partial":
      return "bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400";
    case "unpaid":
      return "bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400";
    default:
      return "bg-slate-100 text-slate-600 dark:bg-gray-800 dark:text-gray-300";
  }
}

function summaryCardClasses(status) {
  switch (String(status).toLowerCase()) {
    case "paid":
      return "bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400";
    case "partial":
      return "bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400";
    case "unpaid":
      return "bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400";
    default:
      return "text-slate-900 dark:text-gray-100";
  }
}

function infoItem(label, value) {
  return { label, value: value ?? "—" };
}

function close() {
  emit("close");
}
</script>

<template>
  <div
    v-if="show && session"
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 px-4 py-6"
    @click.self="close"
  >
    <div class="flex max-h-[85vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-gray-900">
      <!-- Header -->
      <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-6 py-4 dark:border-gray-800">
        <div>
          <p class="text-xs font-semibold uppercase tracking-wide text-blue-900 dark:text-blue-400">{{ $t('Student Deposit Details') }}</p>
          <p class="mt-0.5 text-lg font-semibold text-slate-900 dark:text-gray-100">{{ session.name }}</p>
        </div>
        <button
          type="button"
          :aria-label="$t('Close')"
          class="-mr-2 -mt-1 shrink-0 rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-gray-800 dark:hover:text-gray-200"
          @click="close"
        >
          <X class="h-5 w-5" />
        </button>
      </div>

      <!-- Scrollable body -->
      <div class="overflow-y-auto px-6 py-5">
        <!-- Student Information -->
        <section>
          <h4 class="text-xs font-bold uppercase tracking-wide text-blue-900 dark:text-blue-400">
            {{ $t('Student Information') }}
          </h4>
          <div class="mt-3 grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div v-for="item in [
              infoItem('Student ID', `#${session.id}`),
              infoItem('Student Name', session.name),
              infoItem('Gender', formatGender(session.gender)),
              infoItem('Phone Number', session.phone),
            ]" :key="item.label" class="rounded-xl bg-slate-50 p-3 dark:bg-gray-800/60">
              <p class="text-xs font-medium text-slate-400 dark:text-gray-500">{{ item.label }}</p>
              <p class="mt-0.5 text-sm font-semibold text-slate-800 dark:text-gray-200">{{ item.value }}</p>
            </div>
          </div>
        </section>

        <!-- Enrollment Information -->
        <section class="mt-6">
          <h4 class="text-xs font-bold uppercase tracking-wide text-blue-900 dark:text-blue-400">
            {{ $t('Enrollment Information') }}
          </h4>
          <div class="mt-3 grid grid-cols-2 gap-4 sm:grid-cols-3">
            <div class="rounded-xl bg-slate-50 p-3 dark:bg-gray-800/60">
              <p class="text-xs font-medium text-slate-400 dark:text-gray-500">{{ $t('Course') }}</p>
              <p class="mt-0.5 text-sm font-semibold text-slate-800 dark:text-gray-200">{{ session.course }}</p>
            </div>
            <div class="rounded-xl bg-slate-50 p-3 dark:bg-gray-800/60">
              <p class="text-xs font-medium text-slate-400 dark:text-gray-500">{{ $t('Enrollment Date') }}</p>
              <p class="mt-0.5 text-sm font-semibold text-slate-800 dark:text-gray-200">{{ session.enrolledAt }}</p>
            </div>
            <div class="rounded-xl bg-slate-50 p-3 dark:bg-gray-800/60">
              <p class="text-xs font-medium text-slate-400 dark:text-gray-500">{{ $t('Enrollment Status') }}</p>
              <span
                class="mt-0.5 inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold"
                :class="statusBadge(session.enrollmentStatus)"
              >
                {{ session.enrollmentStatus }}
              </span>
            </div>
          </div>
        </section>

        <!-- Payment Summary -->
        <section class="mt-6">
          <h4 class="text-xs font-bold uppercase tracking-wide text-blue-900 dark:text-blue-400">
            {{ $t('Payment Summary') }}
          </h4>
          <div class="mt-3 grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="rounded-xl border border-slate-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800/60">
              <p class="text-xs font-medium text-slate-400 dark:text-gray-500">{{ $t('Total Due') }}</p>
              <p class="mt-1 text-xl font-bold text-slate-900 dark:text-gray-100">{{ formatMoney(session.fee) }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800/60">
              <p class="text-xs font-medium text-slate-400 dark:text-gray-500">{{ $t('Total Paid') }}</p>
              <p class="mt-1 text-xl font-bold text-emerald-700 dark:text-emerald-400">{{ formatMoney(session.totalPaid) }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800/60">
              <p class="text-xs font-medium text-slate-400 dark:text-gray-500">{{ $t('Remaining') }}</p>
              <p class="mt-1 text-xl font-bold text-red-700 dark:text-red-400">{{ formatMoney(session.remaining) }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800/60">
              <p class="text-xs font-medium text-slate-400 dark:text-gray-500">{{ $t('Status') }}</p>
              <span
                class="mt-1 inline-block rounded-full px-3 py-1 text-sm font-bold"
                :class="summaryCardClasses(session.status)"
              >
                {{ session.status }}
              </span>
            </div>
          </div>

          <div class="mt-3 grid grid-cols-1 gap-4 rounded-xl bg-slate-50 p-4 text-sm sm:grid-cols-3 dark:bg-gray-800/60">
            <div class="flex items-center justify-between gap-2 sm:block">
              <span class="font-medium text-slate-400 dark:text-gray-500">{{ $t('Total Course Fee') }}:</span>
              <span class="font-semibold text-slate-800 dark:text-gray-200">{{ formatMoney(session.fee) }}</span>
            </div>
            <div class="flex items-center justify-between gap-2 sm:block">
              <span class="font-medium text-slate-400 dark:text-gray-500">{{ $t('Document Fee') }}:</span>
              <span class="font-semibold text-slate-800 dark:text-gray-200">{{ formatMoney(session.documentFee) }}</span>
            </div>
            <div class="flex items-center justify-between gap-2 sm:block">
              <span class="font-medium text-slate-400 dark:text-gray-500">{{ $t('Last Payment Date') }}:</span>
              <span class="font-semibold text-slate-800 dark:text-gray-200">{{ session.lastPaymentDate ?? "—" }}</span>
            </div>
          </div>
        </section>

        <!-- Payment History -->
        <section class="mt-6">
          <div v-if="session.history.length" class="overflow-x-auto rounded-xl border border-slate-200 dark:border-gray-700">
            <table class="w-full min-w-[680px] text-sm">
              <thead class="bg-slate-100 text-left text-[11px] font-bold uppercase tracking-wide text-slate-500 dark:bg-gray-800 dark:text-gray-400">
                <tr>
                  <th class="px-4 py-3">{{ $t('Payment Date') }}</th>
                  <th class="px-4 py-3">{{ $t('Amount') }}</th>
                  <th class="px-4 py-3">{{ $t('Payment Method') }}</th>
                  <th class="px-4 py-3">{{ $t('Reference Number') }}</th>
                  <th class="px-4 py-3">{{ $t('Payment Status') }}</th>
                  <th class="px-4 py-3">{{ $t('Recorded By') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(payment, index) in session.history" :key="index" class="border-t border-slate-100 dark:border-gray-800">
                  <td class="px-4 py-2.5">{{ payment.payment_date ?? "—" }}</td>
                  <td class="px-4 py-2.5 font-medium">{{ formatMoney(payment.amount) }}</td>
                  <td class="px-4 py-2.5">{{ payment.payment_method }}</td>
                  <td class="px-4 py-2.5">{{ payment.reference_number }}</td>
                  <td class="px-4 py-2.5">
                    <span
                      class="inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold"
                      :class="statusBadge(payment.payment_status)"
                    >
                      {{ payment.payment_status }}
                    </span>
                  </td>
                  <td class="px-4 py-2.5">{{ payment.recorded_by }}</td>
                </tr>
              </tbody>
            </table>

            <div class="flex flex-col gap-2 border-t border-slate-200 px-4 py-3 text-sm sm:flex-row sm:items-center sm:justify-between dark:border-gray-700">
              <span class="font-semibold text-slate-700 dark:text-gray-300">
                {{ $t('Total Paid') }}:
                <span class="text-emerald-700 dark:text-emerald-400">{{ formatMoney(session.totalPaid) }}</span>
              </span>
              <span class="font-semibold text-slate-700 dark:text-gray-300">
                {{ $t('Remaining Balance') }}:
                <span class="text-red-700 dark:text-red-400">{{ formatMoney(session.remaining) }}</span>
              </span>
            </div>
          </div>

          <!-- <div v-else class="mt-3 rounded-xl border border-dashed border-slate-300 bg-slate-50 py-8 text-center dark:border-gray-700 dark:bg-gray-800/40">
            <p class="text-sm font-medium text-slate-400 dark:text-gray-500">{{ $t('No payment history available') }}</p>
            <p class="mt-1 text-xs text-slate-400 dark:text-gray-600">{{ $t('Last payment date') }}: {{ session.lastPaymentDate ?? "—" }}</p>
          </div> -->
        </section>
      </div>
    </div>
  </div>
</template>