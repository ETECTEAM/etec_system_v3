<script setup>
import { computed, ref } from "vue";
import { router } from "@inertiajs/vue3";
import { ArrowLeft, ChevronDown, Mail, MapPin, Phone, Cake } from "@lucide/vue";

import DashboardLayout from "../../../layouts/DashboardLayout.vue";
import Breadcrumbs from "../../../components/ui/breadcrumbs/Breadcrumbs.vue";
import PageHero from "../../../components/ui/page-hero/PageHero.vue";

import Table from "../../../components/ui/table/Table.vue";
import TableHeader from "../../../components/ui/table/TableHeader.vue";
import TableHead from "../../../components/ui/table/TableHead.vue";
import TableBody from "../../../components/ui/table/TableBody.vue";
import TableRow from "../../../components/ui/table/TableRow.vue";
import TableCell from "../../../components/ui/table/TableCell.vue";

const props = defineProps({
  student: {
    type: Object,
    default: null,
  },
  enrollments: {
    type: Array,
    default: () => [],
  },
  summary: {
    type: Object,
    default: () => ({}),
  },
});

const defaultBackUrl = "/dashboard/enroll";
const backUrl = computed(() => {
  try {
    return (
      new URL(window.location.href).searchParams.get("back") ?? defaultBackUrl
    );
  } catch {
    return defaultBackUrl;
  }
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
    case "active":
    case "paid":
      return "bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400";
    case "partial":
      return "bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400";
    case "pending":
      return "bg-sky-100 text-sky-700 dark:bg-sky-500/10 dark:text-sky-400";
    case "unpaid":
    case "cancelled":
    case "rejected":
      return "bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400";
    default:
      return "bg-slate-100 text-slate-600 dark:bg-gray-800 dark:text-gray-300";
  }
}

const expandedEnrollment = ref(null);

function toggleEnrollment(id) {
  expandedEnrollment.value = expandedEnrollment.value === id ? null : id;
}

function goBack() {
  router.get(backUrl.value);
}

const breadcrumbItems = [
  { label: "Dashboard", href: "/dashboard" },
  { label: "Class List", href: "/dashboard/enroll" },
  { label: "View Student", current: true },
];

const summaryCards = computed(() => [
  { label: "Total Enrollments", value: props.summary?.enrollments ?? 0 },
  { label: "Active", value: props.summary?.active ?? 0 },
  { label: "Pending", value: props.summary?.pending ?? 0 },
  { label: "Amount Paid", value: formatMoney(props.summary?.amount_paid) },
  { label: "Remaining Balance", value: formatMoney(props.summary?.remaining_balance) },
]);
</script>

<template>
  <DashboardLayout>
    <div class="w-full">
      <div class="space-y-4 sm:space-y-5">
        <Breadcrumbs :items="breadcrumbItems" />
        <PageHero :title="$t('View Student')" />
      </div>

      <div v-if="!student" class="mt-6 flex items-center justify-center rounded-xl bg-white py-20 shadow dark:bg-gray-900">
        <p class="text-slate-400 dark:text-gray-500">{{ $t('Student data is not available.') }}</p>
      </div>

      <template v-else>
        <!-- Back button -->
        <div class="mt-6">
          <button
            @click="goBack"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-500"
          >
            <ArrowLeft class="h-4 w-4" /> Back to Class List
          </button>
        </div>

        <!-- Student profile card -->
        <div class="mt-6 rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
          <div class="border-b border-slate-100 px-6 py-5 dark:border-gray-800">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
              <div class="flex items-center gap-4">
                <div class="grid h-14 w-14 place-items-center rounded-full bg-blue-900 text-lg font-bold text-white dark:bg-blue-600">
                  {{ (student.full_name ?? "?").charAt(0).toUpperCase() }}
                </div>
                <div>
                  <h3 class="text-lg font-semibold text-slate-900 dark:text-gray-100">
                    {{ student.full_name }}
                  </h3>
                  <p class="text-sm text-slate-500 dark:text-gray-400">
                    #{{ student.id }} • {{ formatGender(student.gender) }}
                  </p>
                </div>
              </div>
              <span
                class="inline-block w-fit rounded-full px-3 py-1 text-xs font-semibold"
                :class="statusBadge(student.student_status)"
              >
                {{ student.student_status }}
              </span>
            </div>
          </div>

          <div class="grid gap-4 px-6 py-5 sm:grid-cols-2 lg:grid-cols-3">
            <div class="flex items-start gap-3 rounded-xl bg-slate-50 p-3 dark:bg-gray-800/60">
              <Phone class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
              <div>
                <p class="text-xs font-medium text-slate-400">{{ $t('Phone') }}</p>
                <p class="text-sm font-medium text-slate-800 dark:text-gray-200">{{ student.phone }}</p>
              </div>
            </div>
            <div class="flex items-start gap-3 rounded-xl bg-slate-50 p-3 dark:bg-gray-800/60">
              <Mail class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
              <div>
                <p class="text-xs font-medium text-slate-400">{{ $t('Email') }}</p>
                <p class="text-sm font-medium break-all text-slate-800 dark:text-gray-200">{{ student.email }}</p>
              </div>
            </div>
            <div class="flex items-start gap-3 rounded-xl bg-slate-50 p-3 dark:bg-gray-800/60">
              <Cake class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
              <div>
                <p class="text-xs font-medium text-slate-400">{{ $t('Date of Birth') }}</p>
                <p class="text-sm font-medium text-slate-800 dark:text-gray-200">{{ student.date_of_birth ?? "—" }}</p>
              </div>
            </div>
            <div class="flex items-start gap-3 rounded-xl bg-slate-50 p-3 dark:bg-gray-800/60">
              <MapPin class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" />
              <div>
                <p class="text-xs font-medium text-slate-400">{{ $t('Address') }}</p>
                <p class="text-sm font-medium text-slate-800 dark:text-gray-200">{{ student.address }}</p>
              </div>
            </div>
            <div class="flex items-start gap-3 rounded-xl bg-slate-50 p-3 dark:bg-gray-800/60">
              <div>
                <p class="text-xs font-medium text-slate-400">{{ $t('Default Course') }}</p>
                <p class="text-sm font-medium text-slate-800 dark:text-gray-200">{{ student.course }}</p>
              </div>
            </div>
            <div class="flex items-start gap-3 rounded-xl bg-slate-50 p-3 dark:bg-gray-800/60">
              <div>
                <p class="text-xs font-medium text-slate-400">{{ $t('Joined') }}</p>
                <p class="text-sm font-medium text-slate-800 dark:text-gray-200">{{ student.created_at }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Summary cards -->
        <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-5">
          <div
            v-for="card in summaryCards"
            :key="card.label"
            class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900"
          >
            <p class="text-xs font-medium text-slate-400 dark:text-gray-500">{{ card.label }}</p>
            <p class="mt-1 text-xl font-bold text-slate-900 dark:text-gray-100">{{ card.value }}</p>
          </div>
        </div>

        <!-- Enrollments -->
        <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
          <div class="border-b border-slate-100 px-6 py-4 dark:border-gray-800">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-gray-100">
              {{ $t('Enrollment History') }}
              <span class="ml-1.5 text-sm font-normal text-slate-400 dark:text-gray-500">
                ({{ enrollments.length }})
              </span>
            </h3>
          </div>

          <div v-if="enrollments.length" class="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>{{ $t('Course') }}</TableHead>
                  <TableHead>{{ $t('Class') }}</TableHead>
                  <TableHead>{{ $t('Term') }}</TableHead>
                  <TableHead>{{ $t('Time') }}</TableHead>
                  <TableHead>{{ $t('Enrollment Status') }}</TableHead>
                  <TableHead>{{ $t('Payment Status') }}</TableHead>
                  <TableHead>{{ $t('Paid') }}</TableHead>
                  <TableHead>{{ $t('Remaining') }}</TableHead>
                  <TableHead class="text-center">{{ $t('Payments') }}</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <template v-for="enrollment in enrollments" :key="enrollment.id">
                  <TableRow class="cursor-pointer" @click="toggleEnrollment(enrollment.id)">
                    <TableCell class="font-medium text-slate-900 dark:text-gray-100">
                      {{ enrollment.course }}
                    </TableCell>
                    <TableCell class="whitespace-nowrap">
                      {{ enrollment.class_title }}
                    </TableCell>
                    <TableCell class="whitespace-nowrap">{{ enrollment.term }}</TableCell>
                    <TableCell class="whitespace-nowrap">{{ enrollment.time }}</TableCell>
                    <TableCell>
                      <span
                        class="inline-block whitespace-nowrap rounded-full px-3 py-0.5 text-xs font-semibold"
                        :class="statusBadge(enrollment.enrollment_status)"
                      >
                        {{ enrollment.enrollment_status }}
                      </span>
                    </TableCell>
                    <TableCell>
                      <span
                        class="inline-block whitespace-nowrap rounded-full px-3 py-0.5 text-xs font-semibold"
                        :class="statusBadge(enrollment.payment_status)"
                      >
                        {{ enrollment.payment_status }}
                      </span>
                    </TableCell>
                    <TableCell class="whitespace-nowrap font-medium">
                      {{ formatMoney(enrollment.amount_paid) }}
                    </TableCell>
                    <TableCell class="whitespace-nowrap font-medium">
                      {{ formatMoney(enrollment.remaining_balance) }}
                    </TableCell>
                    <TableCell>
                      <div class="flex justify-center">
                        <ChevronDown
                          class="h-4 w-4 text-slate-400 transition-transform"
                          :class="{ 'rotate-180': expandedEnrollment === enrollment.id }"
                        />
                      </div>
                    </TableCell>
                  </TableRow>

                  <TableRow v-if="expandedEnrollment === enrollment.id">
                    <TableCell colspan="9" class="!bg-slate-50 dark:!bg-gray-800/40">
                      <div class="py-2">
                        <div class="mb-3 flex flex-col gap-3 text-sm text-slate-600 dark:text-gray-300 sm:flex-row sm:items-center sm:gap-8">
                          <span>
                            <span class="font-medium text-slate-400">Fee:</span>
                            {{ formatMoney(enrollment.fee_amount) }}
                          </span>
                          <span>
                            <span class="font-medium text-slate-400">Doc Fee:</span>
                            {{ formatMoney(enrollment.document_fee_amount) }}
                          </span>
                          <span>
                            <span class="font-medium text-slate-400">Total Due:</span>
                            {{ formatMoney(enrollment.total_due) }}
                          </span>
                          <span>
                            <span class="font-medium text-slate-400">Enrolled:</span>
                            {{ enrollment.enrolled_at ?? "—" }}
                          </span>
                          <span>
                            <span class="font-medium text-slate-400">Paid At:</span>
                            {{ enrollment.paid_at ?? "—" }}
                          </span>
                        </div>

                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">
                          {{ $t('Payment History') }} ({{ enrollment.payments.length }})
                        </p>

                        <div v-if="enrollment.payments.length" class="overflow-x-auto rounded-xl border border-slate-200 dark:border-gray-700">
                          <table class="w-full min-w-[640px] text-sm">
                            <thead class="bg-slate-100 text-left text-[11px] font-bold uppercase tracking-wide text-slate-500 dark:bg-gray-800 dark:text-gray-400">
                              <tr>
                                <th class="px-3 py-2">Date</th>
                                <th class="px-3 py-2">Amount</th>
                                <th class="px-3 py-2">Method</th>
                                <th class="px-3 py-2">Status</th>
                                <th class="px-3 py-2">Reference</th>
                                <th class="px-3 py-2">Recorded By</th>
                              </tr>
                            </thead>
                            <tbody>
                              <tr v-for="payment in enrollment.payments" :key="payment.id" class="border-t border-slate-100 dark:border-gray-800">
                                <td class="px-3 py-2.5">{{ payment.payment_date ?? "—" }}</td>
                                <td class="px-3 py-2.5 font-medium">{{ formatMoney(payment.amount) }}</td>
                                <td class="px-3 py-2.5">{{ payment.payment_method }}</td>
                                <td class="px-3 py-2.5">
                                  <span
                                    class="inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                    :class="statusBadge(payment.payment_status)"
                                  >
                                    {{ payment.payment_status }}
                                  </span>
                                </td>
                                <td class="px-3 py-2.5">{{ payment.reference_number }}</td>
                                <td class="px-3 py-2.5">{{ payment.recorded_by }}</td>
                              </tr>
                            </tbody>
                          </table>
                        </div>

                        <p v-else class="text-sm text-slate-400 dark:text-gray-500">
                          {{ $t('No payment records for this enrollment.') }}
                        </p>
                      </div>
                    </TableCell>
                  </TableRow>
                </template>
              </TableBody>
            </Table>
          </div>

          <div v-else class="px-6 py-12 text-center">
            <p class="text-base font-medium text-slate-400 dark:text-gray-500">{{ $t('No enrollments found') }}</p>
          </div>
        </div>
      </template>
    </div>
  </DashboardLayout>
</template>