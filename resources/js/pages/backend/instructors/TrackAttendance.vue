<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from "vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { QrcodeCanvas } from "qrcode.vue";
import { ArrowLeft, Bot, Clock, QrCode, Save } from "@lucide/vue";
import { useToast } from "vue-toastification";

import DashboardLayout from "../../../layouts/DashboardLayout.vue";
import { getEcho } from "../../../echo";
import { useConfirm } from "../../../composables/useConfirm";

const props = defineProps({
  classData: {
    type: Object,
    required: true,
  },
  students: {
    type: Array,
    default: () => [],
  },
  attendanceLocked: {
    type: Boolean,
    default: false,
  },
  attendanceWindow: {
    type: Object,
    default: null,
  },
  // Today's ClassSession row (status/recorded_at/can_override) — null if none was
  // generated for today (e.g. the class doesn't meet today).
  todaySession: {
    type: Object,
    default: null,
  },
  attendanceSession: {
    type: Object,
    default: null,
  },
  attendanceSummary: {
    type: Object,
    default: null,
  },
  qrAttendanceAvailable: {
    type: Boolean,
    default: false,
  },
  allowTrackAnytime: {
    type: Boolean,
    default: false,
  },
});

const classLifecycleStatus = computed(() => String(props.classData?.class_status ?? "").toLowerCase());
// The system recorded today's class and the instructor still has time to correct it -
const isOverridable = computed(() => false);
const isPreAttendance = computed(() => props.todaySession?.is_pre_attendance === true);
const now = ref(Date.now());
let nowTimer = null;
let stopBeforeLeaveListener = null;
const pendingNavigationUrl = ref("");
const isBypassingLeaveGuard = ref(false);
const { confirm } = useConfirm();

function parseDeadline(value) {
  if (!value) {
    return null;
  }

  const parsed = new Date(String(value).replace(" ", "T"));

  return Number.isNaN(parsed.getTime()) ? null : parsed;
}

const graceDeadline = computed(() => parseDeadline(props.attendanceWindow?.ends_at));
const attendanceStartTime = computed(() => props.attendanceWindow?.starts_at ?? null);
const timingHint = computed(() => {
  if (!attendanceStartTime.value || !graceDeadline.value) {
    return null;
  }

  const parts = [];

  if (props.classData?.created_date) {
    parts.push(`Created at ${props.classData.created_date}`);
  }

  parts.push(`Starts at ${attendanceStartTime.value}`);
  parts.push(`Auto-records at ${props.attendanceWindow.ends_at}`);

  return parts.join(" · ");
});
const isGraceWindowClosed = computed(() => {
  if (props.allowTrackAnytime || isPreAttendance.value) {
    return false;
  }

  const deadline = graceDeadline.value;

  return deadline ? now.value >= deadline.getTime() : false;
});
const locked = computed(() => classLifecycleStatus.value !== "active"
  || (props.attendanceLocked && !isOverridable.value)
  || (isGraceWindowClosed.value && !isPreAttendance.value));
const windowMessage = computed(() => {
  if (classLifecycleStatus.value === "pre_end") {
    return "This class has been pre-ended. Attendance tracking is closed.";
  }

  if (classLifecycleStatus.value === "ended") {
    return "This class has ended. Attendance tracking is no longer available.";
  }

  if (!props.attendanceWindow) {
    return null;
  }

  if (props.attendanceWindow.reason === "no_session") {
    return "This class does not have a session today.";
  }

  if (props.attendanceWindow.reason === "before_start") {
    return "This class has not started yet.";
  }

  if (!props.allowTrackAnytime && (props.attendanceWindow.reason === "after_deadline" || isGraceWindowClosed.value)) {
    return "The grace period has ended. Attendance is now locked for auto-record.";
  }

  return null;
});
const submitLabel = computed(() => {
  if (form.processing) {
    return "Saving...";
  }

  if (classLifecycleStatus.value === "pre_end") {
    return "Pre-End";
  }

  if (classLifecycleStatus.value === "ended") {
    return "Ended";
  }

  if (isOverridable.value) {
    return "Save Correction";
  }

  if (isPreAttendance.value) {
    return "Complete Pre-Attendance";
  }

  if (!props.allowTrackAnytime && isGraceWindowClosed.value) {
    return "Window Closed";
  }

  if (!locked.value) {
    return "Save Attendance";
  }

  if (props.attendanceWindow?.reason === "before_start") {
    return "Not Started";
  }

  if (props.attendanceWindow?.reason === "after_deadline") {
    return "Window Closed";
  }

  if (props.attendanceWindow?.reason === "no_session") {
    return "No Session";
  }

  return "Submitted Today";
});

const sessionState = computed(() => String(props.attendanceSession?.status ?? "inactive"));
const canCorrectQrAttendance = computed(() => sessionState.value === "active");
const qrUrl = computed(() => props.attendanceSession?.qr_url ?? "");
const showQrPanel = computed(() => props.qrAttendanceAvailable);
const liveAttendanceSummary = reactive({
  present: props.attendanceSummary?.present ?? 0,
  total: props.attendanceSummary?.total ?? props.students.length,
  records: [...(props.attendanceSummary?.records ?? [])],
});
const liveVerification = reactive(
  Object.fromEntries(liveAttendanceSummary.records.map((record) => [record.student_id, record])),
);
const statuses = [
  {
    value: "absent",
    label: "Absent",
    button: "border-rose-200 bg-rose-50 text-rose-700 hover:border-rose-300 hover:bg-rose-100 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300",
    active: "border-rose-500 bg-rose-600 text-white shadow-sm dark:border-rose-400 dark:bg-rose-500 dark:text-white",
  },
  {
    value: "present",
    label: "Present",
    button: "border-emerald-200 bg-emerald-50 text-emerald-700 hover:border-emerald-300 hover:bg-emerald-100 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300",
    active: "border-emerald-500 bg-emerald-600 text-white shadow-sm dark:border-emerald-400 dark:bg-emerald-500 dark:text-white",
  },
  {
    value: "permission",
    label: "Permission",
    button: "border-amber-200 bg-amber-50 text-amber-700 hover:border-amber-300 hover:bg-amber-100 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300",
    active: "border-amber-500 bg-amber-500 text-white shadow-sm dark:border-amber-400 dark:bg-amber-500 dark:text-white",
  },
];
const statusValues = statuses.map((status) => status.value);
const normalizeAttendanceStatus = (status, fallback = "absent") => statusValues.includes(status) ? status : fallback;

const toast = useToast();

// An absence-blocked student is pinned to "absent" - the office must clear the
// block before the instructor can change it.
const isStudentLocked = (student) => Boolean(student?.attendance?.is_locked);

const attendance = reactive(
  Object.fromEntries(props.students.map((student) => [
    student.id,
    isStudentLocked(student)
      ? "absent"
      : normalizeAttendanceStatus(student.attendance?.current_status, isPreAttendance.value && !student.attendance?.is_tracked ? null : "absent"),
  ])),
);

const attendanceTouched = reactive(
  Object.fromEntries(props.students.map((student) => [student.id, false])),
);

const permissionNotes = reactive(
  Object.fromEntries(props.students.map((student) => [student.id, student.attendance?.note ?? ""])),
);

const savedAttendance = reactive(
  Object.fromEntries(props.students.map((student) => [
    student.id,
    normalizeAttendanceStatus(student.attendance?.current_status, isPreAttendance.value && !student.attendance?.is_tracked ? null : "absent"),
  ])),
);

const savedPermissionNotes = reactive(
  Object.fromEntries(props.students.map((student) => [student.id, student.attendance?.note ?? ""])),
);

const hasUnsavedChanges = computed(() => !locked.value && props.students.some((student) =>
  attendanceTouched[student.id]
    || attendance[student.id] !== savedAttendance[student.id]
    || (permissionNotes[student.id] ?? "") !== (savedPermissionNotes[student.id] ?? "")
));

let attendanceChannel = null;

const form = useForm({
  attendance_date: new Date().toISOString().slice(0, 10),
  records: [],
  stop_session: false,
});

const studentVerification = (student) => liveVerification[student.id] ?? student.attendance ?? {};

function setAttendanceStatus(studentId, status) {
  const student = props.students.find((s) => s.id === studentId);
  if (isStudentLocked(student)) {
    return;
  }

  attendance[studentId] = status;
  attendanceTouched[studentId] = true;

  if (status !== "permission") {
    permissionNotes[studentId] = "";
  }
}

function markSavedStateForStudent(studentId) {
  savedAttendance[studentId] = attendance[studentId];
  savedPermissionNotes[studentId] = permissionNotes[studentId] ?? "";
  attendanceTouched[studentId] = false;
}

function markAllSavedState() {
  for (const student of props.students) {
    markSavedStateForStudent(student.id);
  }
}

function handleQrSubmitted(event) {
  const record = event.attendance;

  if (!record || Number(record.class_id) !== Number(props.classData.id)) {
    return;
  }

  attendance[record.student_id] = normalizeAttendanceStatus(record.status ?? "present");
  attendanceTouched[record.student_id] = true;
  permissionNotes[record.student_id] = "";
  liveVerification[record.student_id] = record;
  markSavedStateForStudent(record.student_id);

  if (event.summary) {
    liveAttendanceSummary.present = event.summary.present ?? liveAttendanceSummary.present;
    liveAttendanceSummary.total = event.summary.total ?? liveAttendanceSummary.total;
    liveAttendanceSummary.records = [...(event.summary.records ?? liveAttendanceSummary.records)];
  } else {
    liveAttendanceSummary.present = Object.values(attendance).filter((value) => value === "present").length;
    liveAttendanceSummary.records = [
      ...liveAttendanceSummary.records.filter((item) => Number(item.student_id) !== Number(record.student_id)),
      record,
    ];
  }

  toast.success(`Student #${record.student_id} submitted attendance.`);
}

function handleBeforeUnload(event) {
  if (!hasUnsavedChanges.value) {
    return;
  }

  event.preventDefault();
  event.returnValue = "";
}

onMounted(() => {
  nowTimer = window.setInterval(() => {
    now.value = Date.now();
  }, 10000);

  stopBeforeLeaveListener = router.on("before", async (event) => {
    if (isBypassingLeaveGuard.value || !hasUnsavedChanges.value) {
      return;
    }

    const visitUrl = `${event.detail.visit.url.pathname}${event.detail.visit.url.search}${event.detail.visit.url.hash}`;
    event.preventDefault();

    const ok = await confirm({
      title: "Leave this page?",
      message: "If you leave now, your current attendance selections will be saved first.",
      confirmText: "Save and Leave",
      cancelText: "Stay Here",
    });

    if (!ok) {
      return;
    }

    pendingNavigationUrl.value = visitUrl;
    submit({
      includeUntouched: false,
      allowLocked: true,
    });
  });

  window.addEventListener("beforeunload", handleBeforeUnload);

  attendanceChannel = getEcho()
    ?.private(`attendance.class.${props.classData.id}`)
    .listen(".attendance.qr-submitted", handleQrSubmitted);
});

onBeforeUnmount(() => {
  if (nowTimer !== null) {
    window.clearInterval(nowTimer);
  }

  stopBeforeLeaveListener?.();
  window.removeEventListener("beforeunload", handleBeforeUnload);
  attendanceChannel?.stopListening(".attendance.qr-submitted", handleQrSubmitted);
});

function buildRecords(includeUntouched = true) {
  return props.students
    .filter((student) => includeUntouched || attendanceTouched[student.id])
    .map((student) => ({
      student_id: student.id,
      enrollment_id: student.enrollment_id,
      status: attendance[student.id] ?? "absent",
      note: permissionNotes[student.id] || null,
    }));
}

const submit = (options = {}) => {
  const {
    stopSessionAfterSave = false,
    includeUntouched = true,
    refreshAfterSave = true,
    allowLocked = false,
  } = typeof options === "boolean" ? { stopSessionAfterSave: options } : options;

  if (locked.value && !allowLocked) {
    toast.warning(windowMessage.value ?? "Attendance has already been submitted for this class today.");
    return;
  }

  if (includeUntouched && isPreAttendance.value && props.students.some((student) => !attendance[student.id])) {
    toast.warning("Please complete attendance for every unresolved student.");
    return;
  }

  form.stop_session = stopSessionAfterSave === true;
  form.records = buildRecords(includeUntouched);

  if (!form.records.length) {
    if (!includeUntouched) {
      toast.info("No attendance changes to save yet.");
    }

    pendingNavigationUrl.value = "";
    isBypassingLeaveGuard.value = false;

    return;
  }
  form.records = props.students.map((student) => ({
    student_id: student.id,
    enrollment_id: student.enrollment_id,
    status: isStudentLocked(student) ? "absent" : (attendance[student.id] ?? "absent"),
    note: permissionNotes[student.id] || null,
  }));

  const url = `/dashboard/instructor/classes/${props.classData.id}/attendance`;
  const visitOptions = {
    preserveScroll: true,
    onError: () => {
      pendingNavigationUrl.value = "";
      isBypassingLeaveGuard.value = false;
      toast.error(isOverridable.value ? "Failed to save correction." : "Failed to save attendance.");
    },
    onSuccess: () => {
      markAllSavedState();

      if (pendingNavigationUrl.value) {
        const nextUrl = pendingNavigationUrl.value;
        pendingNavigationUrl.value = "";
        isBypassingLeaveGuard.value = true;
        router.visit(nextUrl, {
          preserveScroll: true,
        });
      } else if (refreshAfterSave) {
        router.reload({
          preserveScroll: true,
          preserveState: true,
        });
      }
    },
    onFinish: () => {
      if (!pendingNavigationUrl.value) {
        isBypassingLeaveGuard.value = false;
      }
    },
  };

  if (isOverridable.value) {
    form.put(url, visitOptions);
  } else {
    form.post(url, visitOptions);
  }
};

watch(
  isGraceWindowClosed,
  (closed) => {
    if (closed && hasUnsavedChanges.value && !form.processing) {
      submit({
        includeUntouched: false,
        allowLocked: true,
      });
    }
  },
  { immediate: true },
);
</script>

<template>
  <Head :title="`Track Attendance - ${classData.title}`" />

  <DashboardLayout>
    <section class="space-y-5">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
          <div>
            <h1 class="text-xl font-black tracking-tight text-slate-950 dark:text-gray-100 sm:text-2xl">{{ classData.title }}</h1>
            <p class="mt-1 flex items-center gap-2 text-sm font-semibold text-slate-500 dark:text-gray-400">
              <Clock class="h-4 w-4" />
              {{ classData.term }} · {{ classData.time }}
            </p>
            <p v-if="timingHint" class="mt-2 text-xs font-semibold text-slate-500 dark:text-gray-400">
              {{ timingHint }}
            </p>
          </div>
        </div>

        <button
          type="button"
          :disabled="form.processing || !students.length || locked"
          class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-70"
          @click="submit()"
        >
          <Save class="h-4 w-4" />
          {{ submitLabel }}
        </button>
      </div>

      <div
        v-if="todaySession?.status === 'auto_recorded'"
        class="flex flex-wrap items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300"
      >
        <Bot class="h-4 w-4 shrink-0" />
        <span>
          The system recorded this class at {{ todaySession.recorded_at }} because attendance was not submitted in time.
        </span>
      </div>

      <div
        v-else-if="isPreAttendance"
        class="flex flex-wrap items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300"
      >
        <Bot class="h-4 w-4 shrink-0" />
        <span>
          Attendance was not fully submitted before the grace period ended. Complete the unresolved students, then save.
        </span>
      </div>

      <div
        v-else-if="windowMessage && !canCorrectQrAttendance"
        class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-semibold text-blue-700 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-300"
      >
        {{ windowMessage }}
      </div>

      <div v-else-if="locked" class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-semibold text-blue-700 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-300">
        Attendance has already been submitted for this class today. You can view the saved result, but cannot track again today.
      </div>

      <div
        class="grid items-start gap-4"
        :class="showQrPanel ? 'xl:grid-cols-[minmax(0,65fr)_minmax(300px,35fr)]' : 'xl:grid-cols-1'"
      >
        <div class="h-fit overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
          <div class="border-b border-slate-200 px-4 py-3 dark:border-gray-800">
            <p class="text-xs font-black uppercase tracking-[0.16em] text-blue-600 dark:text-blue-400">Manual Attendance</p>
          </div>
          <p v-if="form.errors.records" class="border-b border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300">{{ form.errors.records }}</p>
          <div class="overflow-x-auto">
            <table class="min-w-[800px] w-full border-collapse text-sm">
              <thead>
                <tr class="bg-slate-50 text-center text-xs font-black uppercase tracking-[0.08em] text-slate-500 dark:bg-gray-950 dark:text-gray-400">
                  <th class="border-b border-slate-200 px-4 py-3 dark:border-gray-800">Nº</th>
                  <th class="border-b border-slate-200 px-4 py-3 text-left dark:border-gray-800">Student ID</th>
                  <th class="border-b border-slate-200 px-4 py-3 text-left dark:border-gray-800">Student Name</th>
                  <th class="border-b border-slate-200 px-4 py-3 dark:border-gray-800">Attendance</th>
                  <th class="w-44 border-b border-slate-200 px-4 py-3 text-left dark:border-gray-800">Note</th>
                </tr>
              </thead>

              <tbody>
                <tr
                  v-for="student in students"
                  :key="student.enrollment_id"
                  class="align-middle transition hover:bg-slate-50/80 dark:hover:bg-gray-800/50"
                >
                  <td class="border-b border-slate-100 px-4 py-3 text-center font-black text-slate-500 dark:border-gray-800 dark:text-gray-400">
                    {{ student.roster_no }}
                  </td>
                  <td class="border-b border-slate-100 px-4 py-3 font-mono text-sm font-black text-slate-700 dark:border-gray-800 dark:text-gray-300">
                    #{{ student.id }}
                  </td>
                  <td class="border-b border-slate-100 px-4 py-3 dark:border-gray-800">
                    <p class="font-black text-slate-950 dark:text-gray-100">{{ student.name }}</p>
                    <p
                      v-if="isPreAttendance && !attendance[student.id]"
                      class="mt-1 inline-flex items-center gap-1 rounded-md border border-amber-200 bg-amber-50 px-2 py-0.5 text-[11px] font-black text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300"
                    >
                      Unresolved
                    </p>
                    <p
                      v-if="studentVerification(student).verification_status === 'suspicious'"
                      class="mt-1 inline-flex items-center gap-1 rounded-md border border-amber-200 bg-amber-50 px-2 py-0.5 text-[11px] font-black text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300"
                    >
                      Suspicious
                    </p>
                    <p
                      v-if="isStudentLocked(student)"
                      class="mt-1 flex max-w-xs items-start gap-1 rounded-md border border-rose-200 bg-rose-50 px-2 py-1 text-[11px] font-bold text-rose-700 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300"
                    >
                      🔒 {{ student.attendance?.lock_reason || 'Attendance locked - see the school office.' }}
                    </p>
                  </td>
                  <td class="border-b border-slate-100 px-4 py-3 dark:border-gray-800">
                    <div class="flex flex-wrap justify-center gap-2">
                      <button
                        v-for="status in statuses"
                        :key="status.value"
                        type="button"
                        :disabled="attendance[student.id] === status.value || locked || isStudentLocked(student)"
                        :class="[
                          'h-9 rounded-lg border px-3 text-xs font-black transition disabled:cursor-not-allowed',
                          attendance[student.id] === status.value
                            ? status.active
                            : status.button,
                        ]"
                        @click="setAttendanceStatus(student.id, status.value)"
                      >
                        {{ status.label }}
                      </button>
                    </div>
                  </td>
                  <td class="w-44 border-b border-slate-100 px-4 py-3 dark:border-gray-800">
                    <input
                      v-model="permissionNotes[student.id]"
                      type="text"
                      :disabled="locked || isStudentLocked(student) || attendance[student.id] !== 'permission'"
                      placeholder="Enter note..."
                      class="h-9 w-40 max-w-full rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-blue-400 focus:ring-4 focus:ring-blue-100 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100 dark:disabled:bg-gray-800 dark:disabled:text-gray-500 dark:focus:ring-blue-500/10"
                    />
                  </td>
                </tr>

                <tr v-if="!students.length">
                  <td colspan="5" class="px-4 py-12 text-center text-sm font-semibold text-slate-500 dark:text-gray-400">
                    No students are enrolled in this class yet.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div v-if="showQrPanel" class="h-fit rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
          <div class="flex items-center gap-2">
            <QrCode class="h-4 w-4 text-blue-600 dark:text-blue-400" />
            <p class="text-xs font-black uppercase tracking-[0.16em] text-blue-600 dark:text-blue-400">QR Code</p>
          </div>

            <div class="mt-4 flex justify-center">
              <div v-if="qrUrl" class="w-fit rounded-xl border border-slate-200 bg-white p-3 dark:border-gray-800">
                <QrcodeCanvas :value="qrUrl" :size="340" level="H" class="block h-auto max-w-full" />
              </div>
              <div v-else class="grid h-[300px] w-full max-w-[340px] place-items-center rounded-lg border border-dashed border-slate-300 px-4 text-center text-sm font-semibold text-slate-500 dark:border-gray-700 dark:text-gray-400">
                QR code is not available.
              </div>
            </div>
        </div>
      </div>
    </section>
  </DashboardLayout>
</template>
