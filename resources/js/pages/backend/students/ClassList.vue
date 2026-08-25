<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import axios from "axios";
import { Search, RotateCcw, Plus, LayoutGrid, Table2, UserPlus, UserCheck, Printer, Pencil, ArrowRightLeft } from "@lucide/vue";
import { useToast } from "vue-toastification";
import DashboardLayout from "../../../layouts/DashboardLayout.vue";
import ClassCrad from "../../../components/ui/card/ClassCrad.vue";
import ClassTable from "./components/ClassTable.vue";
import Breadcrumbs from "../../../components/ui/breadcrumbs/Breadcrumbs.vue";
import PageHero from "../../../components/ui/page-hero/PageHero.vue";
import EmptyState from "../../../components/ui/empty-state/EmptyState.vue";
import DepositSummaryCard from "./components/DepositSummaryCard.vue";
import ReceiptPrint from "./components/ReceiptPrint.vue";
import MoveEnrollmentModal from "./components/MoveEnrollmentModal.vue";
import { getEcho } from "@/echo";
import { latinNameError } from "@/composables/useLatinNameValidation";
import Table from "../../../components/ui/table/Table.vue";
import TableHeader from "../../../components/ui/table/TableHeader.vue";
import TableHead from "../../../components/ui/table/TableHead.vue";
import TableBody from "../../../components/ui/table/TableBody.vue";
import TableRow from "../../../components/ui/table/TableRow.vue";
import TableCell from "../../../components/ui/table/TableCell.vue";

// Remembers the active tab across page refreshes (Card/Table/Registrations),
// defaulting to Registrations on a first-ever visit.
const VIEW_MODE_STORAGE_KEY = "enroll.classList.viewMode";
const VALID_VIEW_MODES = ["card", "table", "registrations"];

function storedViewMode() {
  if (typeof window === "undefined") return "registrations";
  const stored = window.localStorage.getItem(VIEW_MODE_STORAGE_KEY);
  return VALID_VIEW_MODES.includes(stored) ? stored : "registrations";
}

const viewMode = ref(storedViewMode());

watch(viewMode, (value) => {
  if (typeof window !== "undefined") {
    window.localStorage.setItem(VIEW_MODE_STORAGE_KEY, value);
  }
});

const props = defineProps({
  classes: {
    type: Object,
    default: () => ({ data: [] }),
  },
  filters: {
    type: Object,
    default: () => ({ search: "" }),
  },
  depositSummary: {
    type: Object,
    default: null,
  },
});

const toast = useToast();
const filteredClasses = computed(() => props.classes?.data ?? []);
let searchTimer = null;

const search = ref(props.filters?.search ?? "");

const breadcrumbItems = [
  { label: "Dashboard", href: "/dashboard" },
  { label: "Class List", current: true },
];

function refresh() {
  clearTimeout(searchTimer);
  search.value = "";
  router.get("/dashboard/enroll", {}, { preserveState: true, replace: true, preserveScroll: true });
}

function goCreateClass() {
    router.visit("/dashboard/enroll/create");
}

function goRegisterStudent() {
    router.visit("/dashboard/enroll/students/create");
}

function fetchClasses() {
  router.get("/dashboard/enroll", {
    search: search.value || null,
  }, {
    preserveState: true,
    replace: true,
    preserveScroll: true,
  });
}

// "Registrations" tab — students who self-registered via the public /classes
// page (StudentEnrollment.source = 'public_website'), fetched from
// EnrollmentClassController::publicRegistrations() / GetPublicRegistrations.
const registrations = ref([]);
const registrationsLoading = ref(false);
const registrationsLoaded = ref(false);

// Rows RegisterStudentForSchedule couldn't slot into a class (no
// room/instructor free at the time) come back from the same query with
// study_class_id null and needs_manual_scheduling true - distinct from the
// "Pending" enrollment_status below, which is a QR registration awaiting
// instructor approval and already has a class.
const pendingRegistrationsCount = computed(
  () => registrations.value.filter((row) => row.enrollment_status === "Pending" || row.needs_manual_scheduling).length
);

function needsManualScheduling(row) {
  return !!row.needs_manual_scheduling;
}

async function fetchRegistrations() {
  registrationsLoading.value = true;

  try {
    const response = await axios.get("/dashboard/enroll/registrations/data");
    registrations.value = response.data?.data ?? [];
    registrationsLoaded.value = true;
  } catch (error) {
    console.error("Failed to fetch class registrations", error);
  } finally {
    registrationsLoading.value = false;
  }
}

function selectRegistrationsTab() {
  viewMode.value = "registrations";

  if (!registrationsLoaded.value) {
    fetchRegistrations();
  }
}

const dayAbbreviations = {
  Monday: "Mon",
  Tuesday: "Tue",
  Wednesday: "Wed",
  Thursday: "Thu",
  Friday: "Fri",
  Saturday: "Sat",
  Sunday: "Sun",
};

function studyDaysLabel(row) {
  return (row.study_days ?? []).map((day) => dayAbbreviations[day] ?? day).join(" & ") || "-";
}

function scheduleLabel(row) {
  const days = studyDaysLabel(row);
  const time = row.start_time && row.end_time ? `${row.start_time} - ${row.end_time}` : "";
  return [days === "-" ? "" : days, time].filter(Boolean).join(", ") || "-";
}

// No class exists yet for a "needs manual scheduling" row, so there's no
// real study_days/start_time/end_time to format - fall back to the plain
// term/time names the student picked on the public form (see
// GetPublicRegistrations' requested_term/requested_time).
function requestedScheduleLabel(row) {
  return [row.requested_term, row.requested_time].filter(Boolean).join(", ") || "-";
}

function isPendingRegistration(row) {
  return row.enrollment_status === "Pending";
}

// Feeds the same ReceiptPrint.vue component RegisterStudent.vue/ViewClass.vue use.
const receiptClassData = ref(null);
const receiptStudent = ref(null);
const printingId = ref(null);

async function printReceipt(row) {
  receiptClassData.value = {
    course: row.course_title ?? row.class_title,
    price: row.fee_amount,
    document_price: row.document_fee_amount,
    term: studyDaysLabel(row),
    time: row.start_time && row.end_time ? `${row.start_time} - ${row.end_time}` : "-",
    teacher: row.teacher_name,
    building: row.building,
    floor: row.floor,
    room: row.room,
    enroll_start_date: row.enroll_start_date,
  };
  receiptStudent.value = {
    name: row.name,
    gender: row.gender,
    payment_date: row.enrolled_at,
    amount_paid: row.amount_paid,
    fee_amount: row.fee_amount,
    document_fee_amount: row.document_fee_amount,
    enrollment_id: row.enrollment_id,
  };

  await nextTick();
  window.print();
}

function remainingBalance(row) {
  return Number(row.fee_amount) + Number(row.document_fee_amount) - Number(row.amount_paid);
}


const confirmPaidModalOpen = ref(false);
const pendingRow = ref(null);
const paymentAmountInput = ref("");
const paymentAmountErrorMsg = ref("");

const paymentAmountError = computed(() => {
  if (!pendingRow.value) return "";

  const amount = Number(paymentAmountInput.value);
  const max = remainingBalance(pendingRow.value);

  if (paymentAmountInput.value === "" || Number.isNaN(amount)) return "Enter an amount.";
  if (amount <= 0) return "Amount must be greater than 0.";
  if (amount > max + 0.01) return `Amount can't be more than the remaining $${max.toFixed(2)}.`;

  return paymentAmountErrorMsg.value;
});

function capitalizeStatus(status) {
  return status.charAt(0).toUpperCase() + status.slice(1);
}

async function recordPayment(row, amount) {
  printingId.value = row.enrollment_id;

  try {
    const response = await axios.post(`/dashboard/enroll/enrollments/${row.enrollment_id}/deposit`, {
      deposit_amount: amount,
    });
    row.amount_paid = response.data.amount_paid;
    row.payment_status = capitalizeStatus(response.data.payment_status);
  } catch (error) {
    console.error("Failed to record payment", error);
    printingId.value = null;
    throw error;
  }

  printingId.value = null;
}

async function approveRegistration(row) {
  try {
    await axios.post(`/dashboard/enroll/enrollments/${row.enrollment_id}/approve`);
    toast.success("Student request approved successfully.");
    await fetchRegistrations();
  } catch (error) {
    toast.error(error.response?.data?.message ?? "Failed to approve the request.");
  }
}

function openPartialPaymentModal(row) {
  pendingRow.value = row;
  paymentAmountInput.value = remainingBalance(row).toFixed(2);
  paymentAmountErrorMsg.value = "";
  confirmPaidModalOpen.value = true;
}

function cancelMarkPaid() {
  confirmPaidModalOpen.value = false;
  pendingRow.value = null;
}

async function confirmMarkPaidAndPrint() {
  const row = pendingRow.value;
  if (!row || paymentAmountError.value) return;

  const amount = Number(paymentAmountInput.value);
  confirmPaidModalOpen.value = false;

  try {
    await recordPayment(row, amount);
  } catch (error) {
    paymentAmountErrorMsg.value = error.response?.data?.errors?.deposit_amount?.[0] ?? "Failed to record payment.";
    confirmPaidModalOpen.value = true;
    return;
  }

  pendingRow.value = null;

  await printReceipt(row);
}

// Edit a public registration's basic info (name/gender/phone) inline from
// the Registrations tab — the only editable fields for a submission; class
// assignment itself isn't editable here (see EnrollmentClassController::updateRegistration).
const editModalOpen = ref(false);
const editingRow = ref(null);
const editForm = ref({ name: "", gender: "", phone: "" });
const editErrors = ref({});
const editSaving = ref(false);
const editNameLiveError = computed(() => latinNameError(editForm.value.name));

function openEditModal(row) {
  editingRow.value = row;
  editForm.value = { name: row.name, gender: row.gender, phone: row.phone };
  editErrors.value = {};
  editModalOpen.value = true;
}

function cancelEdit() {
  editModalOpen.value = false;
  editingRow.value = null;
}

async function submitEdit() {
  if (!editingRow.value || editNameLiveError.value) return;

  editSaving.value = true;
  editErrors.value = {};

  try {
    await axios.put(`/dashboard/enroll/registrations/${editingRow.value.enrollment_id}`, editForm.value);
    Object.assign(editingRow.value, editForm.value);
    editModalOpen.value = false;
    editingRow.value = null;
  } catch (error) {
    editErrors.value = error.response?.data?.errors ?? {};
  } finally {
    editSaving.value = false;
  }
}

// Move a registered student into a different existing class (e.g. joining a
// friend's class instead of the one they were auto-assigned to).
const moveModalOpen = ref(false);
const movingRow = ref(null);

function openMoveModal(row) {
  movingRow.value = row;
  moveModalOpen.value = true;
}

function closeMoveModal() {
  moveModalOpen.value = false;
  movingRow.value = null;
}

// The modal's move request is a plain axios call, not an Inertia visit, so
// nothing here refreshes on its own - reload the classes prop (Card/Table
// counts) alongside the Registrations tab data so both reflect the move
// immediately instead of only after a manual page refresh.
function onStudentMoved() {
  fetchRegistrations();
  router.reload({ only: ["classes", "depositSummary"], preserveScroll: true });
}

let notificationsChannel = null;

onMounted(() => {
  // Always fetched (not just when the tab is active) so the badge count on
  // the Registrations button is accurate no matter which tab loads first.
  fetchRegistrations();

  notificationsChannel = getEcho()
    ?.private("admin-notifications")
    .listen(".notifications.updated", () => {
      if (registrationsLoaded.value) fetchRegistrations();

      // Card/Table views' student counts come from the classes prop, which
      // a websocket push doesn't touch on its own — reload it too so a new
      // registration's seat shows up without the user refreshing manually.
      router.reload({ only: ["classes", "depositSummary"], preserveScroll: true });
    });
});

watch(search, () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(fetchClasses, 350);
});

onBeforeUnmount(() => {
  clearTimeout(searchTimer);

  if (notificationsChannel) {
    notificationsChannel.stopListening(".notifications.updated");
  }
});
</script>

<template>
  <DashboardLayout>
    <div class="w-full">
      <div
        class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6"
      >
        <div>
          <Breadcrumbs :items="breadcrumbItems" class="mb-4" />
          <PageHero :title="$t('Class List')" />
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <!-- <button
            @click="goRegisterStudent"
            class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
          >
            <UserPlus class="w-4 h-4" /> {{ $t('Register Student') }}
          </button> -->

          <!-- <button
            @click="goCreateClass"
            class="inline-flex items-center justify-center rounded-xl bg-blue-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-70 dark:bg-blue-600 dark:hover:bg-blue-500"
          >
            <Plus class="w-4 h-4" /> {{ $t('Add Enroll') }}
          </button> -->
        </div>
      </div>

      <!-- depositSummary  -->
      <div class="mt-6">
        <DepositSummaryCard :depositSummary="depositSummary" />
      </div>

      <div
        class="flex flex-col gap-4 py-4 sm:flex-row sm:items-center sm:justify-between"
      >
        <!-- Title -->
        <div>
          <h1 class="text-xl font-bold text-slate-800 dark:text-gray-100">{{ $t('All Class') }}</h1>
        </div>

        <!-- Actions -->
        <div class="flex flex-wrap items-center gap-3">
          <!-- Search -->
          <div class="relative w-full sm:w-64">
            <Search
              class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-gray-500"
            />
            <input
              v-model="search"
              type="text"
              :placeholder="$t('Search student...')"
              class="w-full rounded-xl border border-slate-300 py-2 pl-10 pr-4 text-sm outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:border-indigo-500 dark:focus:ring-indigo-500/20"
            />
          </div>

          <!-- Reset -->
          <button
            @click="refresh"
            class="inline-flex items-center justify-center gap-2 rounded-xl  bg-blue-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-500"
          >
            <RotateCcw class="h-4 w-4" />
            {{ $t('Reset') }}
          </button>

          <!-- Card -->
          <button
            @click="viewMode = 'card'"
            :class="[
              'inline-flex items-center justify-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold shadow-sm transition',
              viewMode === 'card'
                ? 'border-blue-900 bg-blue-800 text-white'
                : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700',
            ]"
          >
            <LayoutGrid class="h-4 w-4" />
            {{ $t('Card') }}
          </button>

          <!-- Table -->
          <button
            @click="viewMode = 'table'"
            :class="[
              'inline-flex items-center justify-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold shadow-sm transition',
              viewMode === 'table'
                ? 'border-blue-900 bg-blue-800 text-white'
                : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700',
            ]"
          >
            <Table2 class="h-4 w-4" />
            {{ $t('Table') }}
          </button>

          <!-- Registrations (students who self-registered on the public /classes page) -->
          <button
            @click="selectRegistrationsTab"
            class="relative inline-flex items-center justify-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold shadow-sm transition"
            :class="viewMode === 'registrations'
              ? 'border-blue-900 bg-blue-800 text-white'
              : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'"
          >
            <UserCheck class="h-4 w-4" />
            {{ $t('Registrations') }}
            <span
              v-if="pendingRegistrationsCount > 0"
              class="ml-0.5 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1 text-xs font-bold text-white"
            >
              {{ pendingRegistrationsCount > 99 ? '99+' : pendingRegistrationsCount }}
            </span>
          </button>
        </div>
      </div>

      <!-- Card View -->
      <div
        v-if="viewMode === 'card' && filteredClasses.length > 0"
        class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-4 gap-5 sm:gap-5"
      >
        <ClassCrad
          v-for="item in filteredClasses"
          :key="item.id"
          :classData="item"
          :count="item.notifications"
        />
      </div>

      <!-- Table View -->
      <div v-else-if="viewMode === 'table' && filteredClasses.length > 0" class="w-full overflow-x-auto">
        <ClassTable :items="filteredClasses" />
      </div>

      <!-- Empty State (Card/Table only; Registrations keeps its own empty row) -->
      <EmptyState v-else-if="viewMode !== 'registrations' && filteredClasses.length === 0" @action="goCreateClass" />

      <!-- Registrations View -->
      <div v-else class="w-full overflow-x-auto">
        <div class="bg-white rounded-xl shadow overflow-hidden dark:bg-gray-900">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>{{ $t('Name') }}</TableHead>
                <TableHead>{{ $t('Gender') }}</TableHead>
                <TableHead>Phone</TableHead>
                <TableHead>{{ $t('Class') }}</TableHead>
                <TableHead>{{ $t('Schedule') }}</TableHead>
                <TableHead>{{ $t('Price') }}</TableHead>
                <TableHead>{{ $t('Payment Status') }}</TableHead>
                <TableHead>{{ $t('Registered') }}</TableHead>
                <TableHead class="text-right">{{ $t('Action') }}</TableHead>
              </TableRow>
            </TableHeader>

            <TableBody>
              <TableRow v-for="row in registrations" :key="row.enrollment_id">
                <TableCell class="whitespace-nowrap font-semibold">
                  {{ row.name }}
                </TableCell>

                <TableCell>
                  {{ row.gender }}
                </TableCell>

                <TableCell class="whitespace-nowrap">
                  {{ row.phone }}
                </TableCell>

                <TableCell class="whitespace-nowrap">
                  <div v-if="needsManualScheduling(row)">
                    <p>{{ row.course_title || '-' }}</p>
                  </div>
                  <div v-else>
                    <p>{{ row.class_title }}</p>
                    <p v-if="row.course_title" class="text-xs text-slate-500 dark:text-gray-400">{{ row.course_title }}</p>
                  </div>
                </TableCell>

                <TableCell class="whitespace-nowrap">
                  {{ needsManualScheduling(row) ? requestedScheduleLabel(row) : scheduleLabel(row) }}
                </TableCell>

                <TableCell class="whitespace-nowrap">
                  ${{ Number(row.fee_amount + row.document_fee_amount).toFixed(2) }}
                </TableCell>

                <TableCell>
                  <span
                    class="inline-flex whitespace-nowrap rounded-full px-3 py-1 text-xs"
                    :class="{
                      'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400': isPendingRegistration(row) || row.payment_status === 'Partial',
                      'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400': !isPendingRegistration(row) && row.payment_status === 'Paid',
                      'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400': !isPendingRegistration(row) && row.payment_status === 'Unpaid',
                    }"
                  >
                    {{ isPendingRegistration(row) ? $t('Pending Approval') : row.payment_status }}
                  </span>
                  <span
                    v-if="needsManualScheduling(row)"
                    class="ml-1 inline-flex whitespace-nowrap rounded-full bg-amber-100 px-3 py-1 text-xs text-amber-700 dark:bg-amber-500/10 dark:text-amber-400"
                  >
                    {{ $t('Needs Class') }}
                  </span>
                </TableCell>

                <TableCell class="whitespace-nowrap text-slate-500 dark:text-gray-400">
                  {{ row.enrolled_at }}
                </TableCell>

                <TableCell>
                  <div class="flex flex-wrap justify-end items-center gap-1.5">
                    <button
                      v-if="isPendingRegistration(row)"
                      type="button"
                      class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-emerald-100 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:hover:bg-emerald-500/20"
                      :title="$t('Approve request')"
                      @click="approveRegistration(row)"
                    >
                      <UserCheck class="h-4 w-4 shrink-0" />
                      <span class="truncate">{{ $t('Approve') }}</span>
                    </button>

                    <button
                      v-else
                      type="button"
                      class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                      :title="$t('Edit')"
                      @click="openEditModal(row)"
                    >
                      <Pencil class="h-4 w-4 shrink-0" />
                    </button>

                    <button
                      v-if="!isPendingRegistration(row) && needsManualScheduling(row)"
                      type="button"
                      class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-emerald-100 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:hover:bg-emerald-500/20"
                      :title="$t('Assign to Class')"
                      @click="openMoveModal(row)"
                    >
                      <UserPlus class="h-4 w-4 shrink-0" />
                      <span class="truncate">{{ $t('Assign to Class') }}</span>
                    </button>

                    <button
                      v-if="!isPendingRegistration(row) && !needsManualScheduling(row)"
                      type="button"
                      class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                      :title="$t('Move to Another Class')"
                      @click="openMoveModal(row)"
                    >
                      <ArrowRightLeft class="h-4 w-4 shrink-0" />
                    </button>

                    <button
                      v-if="!isPendingRegistration(row)"
                      type="button"
                      class="inline-flex w-[150px] items-center justify-center gap-1.5 rounded-lg bg-blue-100 px-3 py-2 text-xs font-semibold text-blue-700 transition hover:bg-blue-200 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-500/20"
                      :disabled="printingId === row.enrollment_id"
                      :title="row.payment_status === 'Paid' ? $t('Print Receipt') : $t('Record payment')"
                      @click="row.payment_status === 'Paid' ? printReceipt(row) : openPartialPaymentModal(row)"
                    >
                      <Printer class="h-4 w-4 shrink-0" />
                      <span class="truncate">{{ row.payment_status === 'Paid' ? $t('Print Receipt') : $t('Record Payment') }}</span>
                    </button>
                  </div>
                </TableCell>
              </TableRow>

              <TableRow v-if="registrations.length === 0">
                <TableCell colspan="9">
                  <div class="py-10 text-center text-slate-500 dark:text-gray-400">
                    {{ $t('No public registrations yet.') }}
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
      </div>

      <ReceiptPrint :classData="receiptClassData" :student="receiptStudent" />

      <MoveEnrollmentModal
        :show="moveModalOpen"
        :enrollment="movingRow"
        @close="closeMoveModal"
        @moved="onStudentMoved"
      />

      <!-- Record Payment & Print confirmation -->
      <div v-if="confirmPaidModalOpen && pendingRow" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 px-4">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900">
          <h3 class="text-lg font-semibold text-slate-900 dark:text-gray-100">{{ $t('Record Payment & Print Receipt') }}</h3>

          <div class="mt-4 space-y-2 rounded-xl bg-slate-50 p-4 text-sm dark:bg-gray-800">
            <div class="flex justify-between">
              <span class="text-slate-500 dark:text-gray-400">{{ $t('Name') }}</span>
              <span class="font-semibold text-slate-800 dark:text-gray-100">{{ pendingRow.name }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500 dark:text-gray-400">{{ $t('Gender') }}</span>
              <span class="font-semibold text-slate-800 dark:text-gray-100">{{ pendingRow.gender }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500 dark:text-gray-400">Phone</span>
              <span class="font-semibold text-slate-800 dark:text-gray-100">{{ pendingRow.phone }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500 dark:text-gray-400">{{ $t('Class') }}</span>
              <span class="font-semibold text-slate-800 dark:text-gray-100">{{ pendingRow.class_title }}</span>
            </div>
            <div class="flex justify-between border-t border-slate-200 pt-2 dark:border-gray-700">
              <span class="text-slate-500 dark:text-gray-400">{{ $t('Total Due') }}</span>
              <span class="font-semibold text-slate-800 dark:text-gray-100">${{ (Number(pendingRow.fee_amount) + Number(pendingRow.document_fee_amount)).toFixed(2) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500 dark:text-gray-400">{{ $t('Already Paid') }}</span>
              <span class="font-semibold text-slate-800 dark:text-gray-100">${{ Number(pendingRow.amount_paid).toFixed(2) }}</span>
            </div>
          </div>

          <label class="mt-4 grid gap-2 text-sm font-semibold text-slate-700 dark:text-gray-300">
            {{ $t('Amount to Pay Now') }}
            <input
              v-model="paymentAmountInput"
              type="number"
              min="0.01"
              step="0.01"
              :max="remainingBalance(pendingRow)"
              class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
            />
          </label>
          <p class="mt-1 text-xs text-slate-400 dark:text-gray-500">
            {{ $t('Remaining balance') }}: ${{ remainingBalance(pendingRow).toFixed(2) }}
          </p>
          <p v-if="paymentAmountError" class="mt-1 text-xs font-semibold text-red-600">{{ paymentAmountError }}</p>

          <p class="mt-4 text-sm text-slate-500 dark:text-gray-400">
            {{ $t('This records the payment above and opens the print dialog.') }}
          </p>

          <div class="mt-6 flex justify-end gap-3">
            <button type="button" @click="cancelMarkPaid" class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
              {{ $t('Cancel') }}
            </button>
            <button
              type="button"
              @click="confirmMarkPaidAndPrint"
              :disabled="printingId === pendingRow.enrollment_id || !!paymentAmountError"
              class="rounded-xl bg-blue-900 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-70 dark:bg-blue-600 dark:hover:bg-blue-500"
            >
              {{ printingId === pendingRow.enrollment_id ? $t('Saving...') : $t('Record Payment & Print') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Edit registration info (name/gender/phone only) -->
      <div v-if="editModalOpen && editingRow" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 px-4">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900">
          <h3 class="text-lg font-semibold text-slate-900 dark:text-gray-100">{{ $t('Edit Student Info') }}</h3>

          <div class="mt-4 space-y-4">
            <label class="grid gap-1.5 text-sm font-semibold text-slate-700 dark:text-gray-300">
              {{ $t('Name') }}
              <input
                v-model="editForm.name"
                type="text"
                :class="[
                  'w-full rounded-xl border px-4 py-3 text-sm outline-none focus:ring-2 dark:bg-gray-800 dark:text-gray-200',
                  editNameLiveError ? 'border-red-300 focus:border-red-500 focus:ring-red-100 dark:border-red-500/60' : 'border-slate-300 focus:border-indigo-400 focus:ring-indigo-100 dark:border-gray-600',
                ]"
              />
              <span v-if="editNameLiveError || editErrors.name" class="text-xs font-semibold text-red-600">{{ editNameLiveError || editErrors.name[0] }}</span>
            </label>

            <div class="grid gap-1.5 text-sm font-semibold text-slate-700 dark:text-gray-300">
              {{ $t('Gender') }}
              <div class="grid grid-cols-2 gap-3">
                <button
                  type="button"
                  class="rounded-xl border-2 px-4 py-2.5 text-sm font-semibold transition"
                  :class="editForm.gender === 'male'
                    ? 'border-blue-900 bg-blue-50 text-blue-900 dark:border-blue-500 dark:bg-blue-500/10 dark:text-blue-400'
                    : 'border-slate-200 bg-slate-50 text-slate-500 hover:border-slate-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400'"
                  @click="editForm.gender = 'male'"
                >
                  {{ $t('Male') }}
                </button>
                <button
                  type="button"
                  class="rounded-xl border-2 px-4 py-2.5 text-sm font-semibold transition"
                  :class="editForm.gender === 'female'
                    ? 'border-blue-900 bg-blue-50 text-blue-900 dark:border-blue-500 dark:bg-blue-500/10 dark:text-blue-400'
                    : 'border-slate-200 bg-slate-50 text-slate-500 hover:border-slate-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400'"
                  @click="editForm.gender = 'female'"
                >
                  {{ $t('Female') }}
                </button>
              </div>
              <span v-if="editErrors.gender" class="text-xs font-semibold text-red-600">{{ editErrors.gender[0] }}</span>
            </div>

            <label class="grid gap-1.5 text-sm font-semibold text-slate-700 dark:text-gray-300">
              Phone
              <input
                v-model="editForm.phone"
                type="text"
                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
              />
              <span v-if="editErrors.phone" class="text-xs font-semibold text-red-600">{{ editErrors.phone[0] }}</span>
            </label>
          </div>

          <div class="mt-6 flex justify-end gap-3">
            <button type="button" @click="cancelEdit" class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
              {{ $t('Cancel') }}
            </button>
            <button
              type="button"
              @click="submitEdit"
              :disabled="editSaving || !!editNameLiveError"
              class="rounded-xl bg-blue-900 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-70 dark:bg-blue-600 dark:hover:bg-blue-500"
            >
              {{ editSaving ? $t('Saving...') : $t('Save Changes') }}
            </button>
          </div>
        </div>
      </div>

      <div v-if="viewMode !== 'registrations' && classes?.links?.length > 3" class="mt-6 flex flex-wrap justify-center gap-2">
        <button
          v-for="link in classes.links"
          :key="link.label"
          type="button"
          :disabled="!link.url"
          @click="link.url && router.visit(link.url, { preserveState: true })"
          v-html="link.label"
          :class="[
            'rounded-lg border px-3 py-2 text-sm',
            link.active
              ? 'border-blue-900 bg-blue-900 text-white'
              : 'border-slate-300 bg-white text-slate-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300',
            !link.url ? 'cursor-not-allowed opacity-50' : 'hover:bg-slate-100 dark:hover:bg-gray-800',
          ]"
        ></button>
      </div>
    </div>
  </DashboardLayout>
</template>
