<script setup>
import { computed, ref, watch } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import axios from "axios";
import { useToast } from "vue-toastification";
import {
  ArrowRightLeft,
  Bot,
  ClipboardCheck,
  Eye,
  FileText,
  Mars,
  Pencil,
  RefreshCw,
  Save,
  Users,
  Venus,
} from "@lucide/vue";

import DashboardLayout from "../../../layouts/DashboardLayout.vue";
import Breadcrumbs from "../../../components/ui/breadcrumbs/Breadcrumbs.vue";

const toast = useToast();

const props = defineProps({
  classData: {
    type: Object,
    required: true,
  },
  students: {
    type: Array,
    default: () => [],
  },
  // Today's ClassSession row — null if the class doesn't meet today.
  todaySession: {
    type: Object,
    default: null,
  },
});

const rosterStudents = ref([]);

const totalPresent = computed(() =>
  rosterStudents.value.reduce((total, student) => total + Number(student.attendance?.present ?? 0), 0),
);

const activeStudent = ref(null);
const editModalOpen = ref(false);
const transferModalOpen = ref(false);
const transferConfirmOpen = ref(false);
const editSaving = ref(false);
const transferSaving = ref(false);
const editErrors = ref({});
const transferErrors = ref({});

const editForm = ref({
  full_name: "",
  gender: "male",
  date_of_birth: "",
  phone: "",
});

const transferForm = ref({
  study_class_id: "",
});

const breadcrumbItems = computed(() => [
  { label: "Dashboard", href: "/dashboard" },
  { label: props.classData.title, current: true },
]);

watch(
  () => props.students,
  (students) => {
    rosterStudents.value = [...students];
  },
  { immediate: true },
);

function resetEditForm() {
  editForm.value = {
    full_name: "",
    gender: "male",
    date_of_birth: "",
    phone: "",
  };
}

function openEditModal(student) {
  activeStudent.value = student;
  editErrors.value = {};
  editForm.value = {
    full_name: student.name ?? "",
    gender: student.gender ?? "male",
    date_of_birth: student.date_of_birth ?? "",
    phone: student.phone ?? "",
  };
  editModalOpen.value = true;
}

function closeEditModal() {
  editModalOpen.value = false;
  activeStudent.value = null;
  editErrors.value = {};
  resetEditForm();
}

function openTransferModal(student) {
  activeStudent.value = student;
  transferErrors.value = {};
  transferForm.value = { study_class_id: "" };
  transferModalOpen.value = true;
}

function closeTransferModal() {
  transferModalOpen.value = false;
  transferConfirmOpen.value = false;
  activeStudent.value = null;
  transferErrors.value = {};
  transferForm.value = { study_class_id: "" };
}

function syncStudentInRoster(studentId, patch) {
  const index = rosterStudents.value.findIndex((student) => student.id === studentId);

  if (index === -1) {
    return;
  }

  rosterStudents.value[index] = {
    ...rosterStudents.value[index],
    ...patch,
  };
}

function removeStudentFromRoster(studentId) {
  rosterStudents.value = rosterStudents.value.filter((student) => student.id !== studentId);
}

async function submitEdit() {
  if (!activeStudent.value) {
    return;
  }

  editSaving.value = true;
  editErrors.value = {};

  try {
    await axios.put(`/dashboard/instructor/classes/${props.classData.id}/students/${activeStudent.value.id}`, editForm.value);
    syncStudentInRoster(activeStudent.value.id, {
      name: editForm.value.full_name,
      gender: editForm.value.gender,
      date_of_birth: editForm.value.date_of_birth || null,
      phone: editForm.value.phone,
    });
    toast.success("Student information updated successfully.");
    closeEditModal();
  } catch (error) {
    editErrors.value = error.response?.data?.errors ?? {};
    toast.error(error.response?.data?.message ?? "Failed to update student.");
  } finally {
    editSaving.value = false;
  }
}

async function submitTransfer() {
  if (!activeStudent.value || !transferForm.value.study_class_id) {
    return;
  }

  transferConfirmOpen.value = true;
}

async function confirmTransfer() {
  if (!activeStudent.value || !transferForm.value.study_class_id) {
    transferConfirmOpen.value = false;
    return;
  }

  const targetClassId = Number(transferForm.value.study_class_id);

  transferSaving.value = true;
  transferErrors.value = {};
  transferConfirmOpen.value = false;

  try {
    await axios.put(`/dashboard/instructor/classes/${props.classData.id}/students/${activeStudent.value.id}/transfer`, {
      study_class_id: targetClassId,
    });
    removeStudentFromRoster(activeStudent.value.id);
    toast.success("Student transferred successfully.");
    closeTransferModal();
  } catch (error) {
    transferErrors.value = error.response?.data?.errors ?? {};
    toast.error(error.response?.data?.message ?? "Failed to transfer student.");
  } finally {
    transferSaving.value = false;
  }
}
</script>

<template>
  <Head :title="`${classData.title} Attendance`" />

  <DashboardLayout>
    <section class="space-y-5">
      <div class="flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
        <div>
          <Breadcrumbs :items="breadcrumbItems" />
          <h1 class="mt-4 text-xl font-black text-blue-950 dark:text-gray-100 sm:text-3xl">{{ classData.title }}</h1>
          <p class="mt-1 text-xs font-semibold text-slate-500 dark:text-gray-400 sm:text-sm">
            Created Date: <span class="rounded-md bg-slate-100 px-2 py-0.5 font-mono dark:bg-gray-800">{{ classData.created_date ?? "-" }}</span>
          </p>
        </div>
        <div class="flex flex-wrap gap-2">
          <button class="inline-flex h-10 items-center gap-2 rounded-lg bg-slate-700 px-3 text-sm font-semibold text-white transition-all duration-200 hover:-translate-y-0.5 hover:bg-slate-800 hover:shadow-md" @click="router.reload({ preserveScroll: true })" type="button">
            <RefreshCw class="h-4 w-4" />
            Refresh Table
          </button>
          <button class="inline-flex h-10 items-center gap-2 rounded-lg bg-cyan-500 px-3 text-sm font-semibold text-white transition-all duration-200 hover:-translate-y-0.5 hover:bg-cyan-600 hover:shadow-md" type="button">
            <Users class="h-4 w-4" />
            Group
          </button>
          <button class="inline-flex h-10 items-center gap-2 rounded-lg bg-amber-500 px-3 text-sm font-semibold text-white transition-all duration-200 hover:-translate-y-0.5 hover:bg-amber-600 hover:shadow-md" type="button">
            <FileText class="h-4 w-4" />
            Request Certificate
          </button>
          <button class="inline-flex h-10 items-center gap-2 rounded-lg bg-emerald-600 px-3 text-sm font-semibold text-white transition-all duration-200 hover:-translate-y-0.5 hover:bg-emerald-700 hover:shadow-md" type="button">
            <Save class="h-4 w-4" />
            Save Score
          </button>
          <Link
            :href="`/dashboard/instructor/classes/${classData.id}/attendance/track`"
            class="inline-flex h-10 items-center gap-2 rounded-lg bg-blue-600 px-3 text-sm font-semibold text-white transition-all duration-200 hover:-translate-y-0.5 hover:bg-blue-700 hover:shadow-md"
          >
            <ClipboardCheck class="h-4 w-4" />
            Track Attendance
          </Link>
        </div>
      </div>

      <div
        v-if="todaySession?.status === 'auto_recorded'"
        class="flex flex-wrap items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300"
      >
        <Bot class="h-4 w-4 shrink-0" />
        <span v-if="todaySession.can_override">
          The system recorded today's class at {{ todaySession.recorded_at }}. You can correct it from Track Attendance until {{ todaySession.override_deadline }}.
        </span>
        <span v-else>
          The system recorded today's class at {{ todaySession.recorded_at }}. The window to correct it has closed.
        </span>
      </div>

      <div class="grid gap-3 lg:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
          <p class="text-sm font-bold text-slate-500 dark:text-gray-400">Total Students</p>
          <p class="mt-1 text-xl font-black text-slate-950 dark:text-gray-100">{{ rosterStudents.length }} Students</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
          <p class="text-sm font-bold text-slate-500 dark:text-gray-400">Class Type</p>
          <p class="mt-1 text-xl font-black text-slate-950 dark:text-gray-100">{{ classData.status }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
          <p class="text-sm font-bold text-slate-500 dark:text-gray-400">Term & Time</p>
          <p class="mt-1 text-xl font-black text-slate-950 dark:text-gray-100">{{ classData.term }} ({{ classData.time }})</p>
        </div>
      </div>

      <div>
        <h2 class="text-xl font-black text-blue-950 dark:text-gray-100">Track Attendance</h2>
        <p class="text-sm font-semibold text-slate-500 dark:text-gray-400">Track your student attendance</p>
      </div>

      <div class="overflow-hidden rounded-xl border border-slate-300 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="bg-blue-950 px-4 py-3 text-center text-base font-black text-white">
          Student Attendance & Score
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-[1180px] w-full border-collapse text-center text-sm">
            <thead>
              <tr class="bg-blue-100 text-slate-950 dark:bg-blue-950/60 dark:text-gray-100">
                <th class="border border-slate-300 px-3 py-3 dark:border-gray-700" rowspan="2">Nº</th>
                <th class="border border-slate-300 px-3 py-3 dark:border-gray-700" rowspan="2">Student</th>
                <th class="border border-slate-300 px-3 py-3 dark:border-gray-700" rowspan="2">Gender</th>
                <th class="border border-slate-300 px-3 py-3 text-center dark:border-gray-700" colspan="4">Attendance</th>
                <th class="border border-slate-300 px-3 py-3 text-center dark:border-gray-700" colspan="3">Score</th>
                <th class="border border-slate-300 px-3 py-3 text-center dark:border-gray-700" rowspan="2">Action</th>
              </tr>
              <tr class="bg-blue-100 text-slate-950 dark:bg-blue-950/60 dark:text-gray-100">
                <th class="border border-slate-300 px-3 py-3 dark:border-gray-700">Total</th>
                <th class="border border-slate-300 px-3 py-3 dark:border-gray-700">Present</th>
                <th class="border border-slate-300 px-3 py-3 dark:border-gray-700">Permission</th>
                <th class="border border-slate-300 px-3 py-3 dark:border-gray-700">Absent</th>
                <th class="border border-slate-300 px-3 py-3 dark:border-gray-700">Attendance Score</th>
                <th class="border border-slate-300 px-3 py-3 dark:border-gray-700">Activity Score</th>
                <th class="border border-slate-300 px-3 py-3 dark:border-gray-700">Exam Score</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="student in rosterStudents" :key="student.enrollment_id" class="align-middle">
                <td class="border border-slate-200 px-3 py-5 font-semibold dark:border-gray-800">{{ student.roster_no }}</td>
                <td class="border border-slate-200 px-3 py-5 text-left dark:border-gray-800">
                  <p class="text-base font-black text-slate-950 dark:text-gray-100">{{ student.name }}</p>
                  <p class="mt-1 text-xs font-bold">
                    ID: <span class="rounded-md bg-blue-900 px-2 py-0.5 text-white">#{{ student.id }}</span>
                  </p>
                </td>
                <td class="border border-slate-200 px-3 py-5 dark:border-gray-800">
                  <span
                    :class="[
                      'inline-flex items-center gap-1 rounded-md border px-2 py-1 text-xs font-bold capitalize',
                      student.gender === 'female'
                        ? 'border-rose-200 bg-rose-50 text-rose-600'
                        : 'border-blue-200 bg-blue-50 text-blue-600',
                    ]"
                  >
                    <Venus v-if="student.gender === 'female'" class="h-3.5 w-3.5" />
                    <Mars v-else class="h-3.5 w-3.5" />
                    {{ student.gender || "-" }}
                  </span>
                </td>
                <td class="border border-slate-200 px-3 py-5 dark:border-gray-800">
                  <span class="inline-flex min-w-14 justify-center rounded-lg bg-slate-100 px-3 py-2 text-sm font-black text-slate-700 dark:bg-gray-800 dark:text-gray-200">
                    {{ student.attendance?.total ?? 0 }}
                  </span>
                </td>
                <td class="border border-slate-200 px-3 py-5 dark:border-gray-800">
                  <span class="inline-flex min-w-14 justify-center rounded-lg bg-emerald-50 px-3 py-2 text-sm font-black text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                    {{ student.attendance?.present ?? 0 }}
                  </span>
                </td>
                <td class="border border-slate-200 px-3 py-5 dark:border-gray-800">
                  <span class="inline-flex min-w-14 justify-center rounded-lg bg-amber-50 px-3 py-2 text-sm font-black text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
                    {{ student.attendance?.permission ?? 0 }}
                  </span>
                </td>
                <td class="border border-slate-200 px-3 py-5 dark:border-gray-800">
                  <span class="inline-flex min-w-14 justify-center rounded-lg bg-rose-50 px-3 py-2 text-sm font-black text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">
                    {{ student.attendance?.absent ?? 0 }}
                  </span>
                </td>
                <td class="border border-slate-200 px-3 py-5 dark:border-gray-800">
                  <input :value="student.scores?.attendance ?? 0" class="h-10 w-28 rounded-lg border border-slate-300 bg-slate-100 px-3 text-center font-semibold outline-none dark:border-gray-700 dark:bg-gray-800" />
                </td>
                <td class="border border-slate-200 px-3 py-5 dark:border-gray-800">
                  <input :value="student.scores?.activity ?? 0" class="h-10 w-28 rounded-lg border border-slate-300 bg-white px-3 text-center font-semibold outline-none dark:border-gray-700 dark:bg-gray-950" />
                </td>
                <td class="border border-slate-200 px-3 py-5 dark:border-gray-800">
                  <input :value="student.scores?.exam ?? 0" class="h-10 w-28 rounded-lg border border-slate-300 bg-white px-3 text-center font-semibold outline-none dark:border-gray-700 dark:bg-gray-950" />
                </td>
                <td class="border border-slate-200 px-3 py-5 dark:border-gray-800">
                  <div class="flex justify-center gap-2">
                    <Link
                      :href="`/dashboard/instructor/classes/${classData.id}/attendance/students/${student.id}`"
                      class="grid h-9 w-9 place-items-center rounded-lg border border-slate-700/70 bg-slate-800/80 text-slate-100 transition-all duration-200 hover:-translate-y-0.5 hover:border-slate-500 hover:bg-slate-700 hover:shadow-md hover:shadow-slate-950/30 focus:outline-none focus:ring-2 focus:ring-blue-400/50 focus:ring-offset-2 focus:ring-offset-slate-950 dark:border-slate-700 dark:bg-slate-900/80 dark:hover:bg-slate-800"
                      title="View student attendance"
                    >
                      <Eye class="h-4 w-4" />
                    </Link>
                    <button
                      type="button"
                      class="grid h-9 w-9 place-items-center rounded-lg border border-amber-400/20 bg-amber-500/10 text-amber-300 transition-all duration-200 hover:-translate-y-0.5 hover:border-amber-400/40 hover:bg-amber-500/20 hover:text-amber-100 hover:shadow-md hover:shadow-amber-950/20 focus:outline-none focus:ring-2 focus:ring-amber-400/50 focus:ring-offset-2 focus:ring-offset-slate-950 dark:focus:ring-offset-gray-900"
                      title="Transfer student"
                      @click="openTransferModal(student)"
                    >
                      <ArrowRightLeft class="h-4 w-4" />
                    </button>
                    <button
                      type="button"
                      class="grid h-9 w-9 place-items-center rounded-lg border border-sky-400/20 bg-sky-500/10 text-sky-300 transition-all duration-200 hover:-translate-y-0.5 hover:border-sky-400/40 hover:bg-sky-500/20 hover:text-sky-100 hover:shadow-md hover:shadow-sky-950/20 focus:outline-none focus:ring-2 focus:ring-sky-400/50 focus:ring-offset-2 focus:ring-offset-slate-950 dark:focus:ring-offset-gray-900"
                      title="Edit student information"
                      @click="openEditModal(student)"
                    >
                      <Pencil class="h-4 w-4" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!rosterStudents.length">
                <td class="border border-slate-200 px-3 py-12 text-center text-sm font-semibold text-slate-500 dark:border-gray-800" colspan="11">
                  No students are enrolled in this class yet.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <Teleport to="body">
        <div v-if="editModalOpen && activeStudent" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 px-4" @click.self="closeEditModal">
          <div class="w-full max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-start justify-between gap-4">
              <div>
                <p class="text-xs font-black uppercase tracking-[0.2em] text-sky-500">Student Profile</p>
                <h3 class="mt-1 text-xl font-black text-slate-950 dark:text-gray-100">{{ activeStudent.name }}</h3>
                <p class="mt-1 text-sm font-semibold text-slate-500 dark:text-gray-400">Update the student record for this class.</p>
              </div>
              <button
                type="button"
                class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                @click="closeEditModal"
              >
                ×
              </button>
            </div>

            <div class="mt-5 grid gap-4 md:grid-cols-2">
              <label class="block">
                <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-300">Full Name</span>
                <input v-model="editForm.full_name" type="text" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-100 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100 dark:focus:border-sky-400 dark:focus:ring-sky-500/20" />
                <p v-if="editErrors.full_name" class="mt-1 text-xs font-semibold text-red-600">{{ editErrors.full_name[0] }}</p>
              </label>
              <label class="block">
                <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-300">Gender</span>
                <select v-model="editForm.gender" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-100 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100 dark:focus:border-sky-400 dark:focus:ring-sky-500/20">
                  <option value="male">Male</option>
                  <option value="female">Female</option>
                </select>
                <p v-if="editErrors.gender" class="mt-1 text-xs font-semibold text-red-600">{{ editErrors.gender[0] }}</p>
              </label>
              <label class="block">
                <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-300">Date of Birth</span>
                <input v-model="editForm.date_of_birth" type="date" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-100 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100 dark:focus:border-sky-400 dark:focus:ring-sky-500/20" />
                <p v-if="editErrors.date_of_birth" class="mt-1 text-xs font-semibold text-red-600">{{ editErrors.date_of_birth[0] }}</p>
              </label>
              <label class="block">
                <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-300">Phone</span>
                <input v-model="editForm.phone" type="text" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-100 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100 dark:focus:border-sky-400 dark:focus:ring-sky-500/20" />
                <p v-if="editErrors.phone" class="mt-1 text-xs font-semibold text-red-600">{{ editErrors.phone[0] }}</p>
              </label>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
              <button type="button" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800" @click="closeEditModal">
                Cancel
              </button>
              <button
                type="button"
                :disabled="editSaving"
                class="rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-500 disabled:cursor-not-allowed disabled:opacity-70"
                @click="submitEdit"
              >
                {{ editSaving ? "Saving..." : "Save Changes" }}
              </button>
            </div>
          </div>
        </div>
      </Teleport>

      <Teleport to="body">
        <div v-if="transferModalOpen && activeStudent" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 px-4" @click.self="closeTransferModal">
          <div class="w-full max-w-xl rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-start justify-between gap-4">
              <div>
                <p class="text-xs font-black uppercase tracking-[0.2em] text-emerald-500">Transfer Student</p>
                <h3 class="mt-1 text-xl font-black text-slate-950 dark:text-gray-100">{{ activeStudent.name }}</h3>
                <p class="mt-1 text-sm font-semibold text-slate-500 dark:text-gray-400">Enter the destination class ID to move this student.</p>
              </div>
              <button
                type="button"
                class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                @click="closeTransferModal"
              >
                ×
              </button>
            </div>

            <div class="mt-5 space-y-4">
              <label class="block">
                <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-300">Destination Class ID</span>
                <input
                  v-model="transferForm.study_class_id"
                  type="number"
                  min="1"
                  placeholder="e.g. 42"
                  class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100 dark:focus:border-emerald-400 dark:focus:ring-emerald-500/20"
                />
                <p v-if="transferErrors.study_class_id" class="mt-1 text-xs font-semibold text-red-600">{{ transferErrors.study_class_id[0] }}</p>
              </label>

              <p class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">
                After transfer, the student disappears from this class and appears in the target class instead.
              </p>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
              <button type="button" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800" @click="closeTransferModal">
                Cancel
              </button>
              <button
                type="button"
                :disabled="transferSaving || !transferForm.study_class_id"
                class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-70"
                @click="submitTransfer"
              >
                {{ transferSaving ? "Transferring..." : "Transfer Student" }}
              </button>
            </div>
          </div>
        </div>
      </Teleport>

      <Teleport to="body">
        <div v-if="transferConfirmOpen && activeStudent" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 px-4" @click.self="transferConfirmOpen = false">
          <div class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-start justify-between gap-4">
              <div>
                <p class="text-xs font-black uppercase tracking-[0.2em] text-amber-500">Confirm Transfer</p>
                <h3 class="mt-1 text-xl font-black text-slate-950 dark:text-gray-100">{{ activeStudent.name }}</h3>
                <p class="mt-1 text-sm font-semibold text-slate-500 dark:text-gray-400">
                  Are you sure you want to transfer this student to class ID <span class="font-black text-slate-900 dark:text-gray-100">{{ Number(transferForm.study_class_id) }}</span>?
                </p>
              </div>
              <button
                type="button"
                class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                @click="transferConfirmOpen = false"
              >
                ×
              </button>
            </div>

            <p class="mt-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300">
              This will move the student out of the current class. Their attendance history stays attached to the student.
            </p>

            <div class="mt-6 flex items-center justify-end gap-3">
              <button
                type="button"
                class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800"
                @click="transferConfirmOpen = false"
              >
                Cancel
              </button>
              <button
                type="button"
                :disabled="transferSaving"
                class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-70"
                @click="confirmTransfer"
              >
                {{ transferSaving ? "Transferring..." : "Yes, Transfer" }}
              </button>
            </div>
          </div>
        </div>
      </Teleport>

      <p class="text-xs font-semibold text-slate-400 dark:text-gray-500">
        Present total: {{ totalPresent }}. Score fields are UI-ready for the attendance module data.
      </p>
    </section>
  </DashboardLayout>
</template>
