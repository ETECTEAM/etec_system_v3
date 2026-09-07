import { computed, nextTick, ref } from "vue";
import { router } from "@inertiajs/vue3";
import axios from "axios";
import { useToast } from "@/composables/useToast";
import { latinNameError } from "@/composables/useLatinNameValidation";

// Single shared source of truth for the "Registrations" tab on the Enrollment
// Management page. Module-scoped so the panel and its table / card views all
// read and mutate the same state without prop-drilling — mirrors the logic
// that previously lived inline in ClassList.vue.

const toast = useToast();

const search = ref("");

const registrations = ref([]);
const loading = ref(false);
const loaded = ref(false);
const pagination = ref({
  current_page: 1,
  from: 0,
  last_page: 1,
  links: [],
  per_page: 10,
  to: 0,
  total: 0,
});
const pendingCount = ref(0);

const printingId = ref(null);
const receiptClassData = ref(null);
const receiptStudent = ref(null);

const dayAbbreviations = {
  Monday: "Mon",
  Tuesday: "Tue",
  Wednesday: "Wed",
  Thursday: "Thu",
  Friday: "Fri",
  Saturday: "Sat",
  Sunday: "Sun",
};

function needsManualScheduling(row) {
  return !!row.needs_manual_scheduling;
}

function isPendingRegistration(row) {
  return row.enrollment_status === "Pending";
}

function studyDaysLabel(row) {
  if (row.term_name) return row.term_name;
  return (row.study_days ?? []).map((day) => dayAbbreviations[day] ?? day).join(" & ") || "-";
}

function scheduleLabel(row) {
  const days = studyDaysLabel(row);
  const time = row.start_time && row.end_time ? `${row.start_time} - ${row.end_time}` : "";
  return [days === "-" ? "" : days, time].filter(Boolean).join(", ") || "-";
}

function requestedScheduleLabel(row) {
  return [row.requested_term, row.requested_time].filter(Boolean).join(", ") || "-";
}

function remainingBalance(row) {
  return Number(row.fee_amount) + Number(row.document_fee_amount) - Number(row.amount_paid);
}

function capitalizeStatus(status) {
  return status.charAt(0).toUpperCase() + status.slice(1);
}

async function fetchRegistrations(page = pagination.value.current_page || 1) {
  loading.value = true;

  try {
    const response = await axios.get("/dashboard/enroll/registrations/data", {
      params: { page, search: search.value || null },
    });
    registrations.value = response.data?.data ?? [];
    pagination.value = {
      current_page: response.data?.current_page ?? 1,
      from: response.data?.from ?? 0,
      last_page: response.data?.last_page ?? 1,
      links: response.data?.links ?? [],
      per_page: response.data?.per_page ?? 10,
      to: response.data?.to ?? 0,
      total: response.data?.total ?? 0,
    };
    pendingCount.value = response.data?.pending_count ?? 0;
    loaded.value = true;
  } catch (error) {
    console.error("Failed to fetch class registrations", error);
  } finally {
    loading.value = false;
  }
}

function registrationPageLabel(label) {
  return String(label)
    .replace("&laquo;", "‹")
    .replace("&raquo;", "›")
    .replace("Previous", "Prev")
    .replace("Next", "Next");
}

function goRegistrationPage(link) {
  if (!link?.url) return;
  const url = new URL(link.url, window.location.origin);
  fetchRegistrations(Number(url.searchParams.get("page") ?? 1));
}

async function printReceipt(row) {
  receiptClassData.value = {
    course: row.course_title ?? row.class_title,
    price: row.fee_amount,
    unit_price: row.unit_price ?? null,
    document_price: row.document_fee_amount,
    term: needsManualScheduling(row) ? (row.requested_term || "-") : studyDaysLabel(row),
    time: needsManualScheduling(row)
      ? (row.requested_time || "-")
      : (row.start_time && row.end_time ? `${row.start_time} - ${row.end_time}` : "-"),
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
    public_token: row.public_token,
  };

  await nextTick();
  window.print();
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

// --- Record Payment & Print confirmation modal ---
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

// --- Inline edit (name / gender / phone) ---
const editingId = ref(null);
const editDraft = ref({ name: "", gender: "", phone: "" });
const editErrors = ref({});
const editSaving = ref(false);
const editNameLiveError = computed(() => latinNameError(editDraft.value.name));

function isEditing(row) {
  return editingId.value === row.enrollment_id;
}

function startEdit(row) {
  editingId.value = row.enrollment_id;
  editDraft.value = { name: row.name, gender: row.gender, phone: row.phone };
  editErrors.value = {};
}

function cancelEdit() {
  editingId.value = null;
  editErrors.value = {};
}

async function saveEdit(row) {
  if (!isEditing(row) || editNameLiveError.value) return;

  editSaving.value = true;
  editErrors.value = {};

  try {
    await axios.put(`/dashboard/enroll/registrations/${row.enrollment_id}`, editDraft.value);
    Object.assign(row, editDraft.value);
    editingId.value = null;
  } catch (error) {
    editErrors.value = error.response?.data?.errors ?? {};
  } finally {
    editSaving.value = false;
  }
}

// --- Move / assign to another class ---
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

function onStudentMoved() {
  fetchRegistrations();
  router.reload({ only: ["classes", "depositSummary"], preserveScroll: true });
}

export function useEnrollmentRegistrations() {
  return {
    search,
    registrations,
    loading,
    loaded,
    pagination,
    pendingCount,
    printingId,
    receiptClassData,
    receiptStudent,

    needsManualScheduling,
    isPendingRegistration,
    studyDaysLabel,
    scheduleLabel,
    requestedScheduleLabel,
    remainingBalance,

    fetchRegistrations,
    registrationPageLabel,
    goRegistrationPage,
    printReceipt,
    recordPayment,
    approveRegistration,

    confirmPaidModalOpen,
    pendingRow,
    paymentAmountInput,
    paymentAmountError,
    openPartialPaymentModal,
    cancelMarkPaid,
    confirmMarkPaidAndPrint,

    editingId,
    editDraft,
    editErrors,
    editSaving,
    editNameLiveError,
    isEditing,
    startEdit,
    cancelEdit,
    saveEdit,

    moveModalOpen,
    movingRow,
    openMoveModal,
    closeMoveModal,
    onStudentMoved,
  };
}
