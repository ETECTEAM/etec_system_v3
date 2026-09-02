<script setup>
import { computed, reactive, ref } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { ArrowLeft, Check, CheckCircle2, Circle, FileText, RotateCcw, Send, Users, XCircle } from "@lucide/vue";

import DashboardLayout from "../../../layouts/DashboardLayout.vue";
import Breadcrumbs from "../../../components/ui/breadcrumbs/Breadcrumbs.vue";

const props = defineProps({
  classData: {
    type: Object,
    required: true,
  },
  students: {
    type: Array,
    default: () => [],
  },
  studentCount: {
    type: Number,
    default: 0,
  },
  certificateType: {
    type: String,
    default: "normal",
  },
  certificateTypeLabel: {
    type: String,
    default: "Normal",
  },
  certificateRequest: {
    type: Object,
    default: null,
  },
});

const requestSubmitting = ref(false);

const breadcrumbItems = computed(() => [
  { label: "Dashboard", href: "/dashboard" },
  { label: props.classData.title, href: `/dashboard/instructor/classes/${props.classData.id}/attendance` },
  { label: "Request Certificate", current: true },
]);

// Local editable copy of the roster so names can be updated and each
// student can be individually approved / un-approved.
const rows = reactive(
  props.students.map((student) => ({
    ...student,
    name: student.name,
    approved: props.certificateRequest?.student_ids?.includes(student.id) ?? false,
    saving: false,
  }))
);

const approvedRows = computed(() => rows.filter((row) => row.approved));
const hasPendingRequest = computed(() => props.certificateRequest?.status === "pending");
const allRowsApproved = computed(() => rows.length > 0 && approvedRows.value.length === rows.length);
const requestStatusLabel = computed(() => props.certificateRequest?.status_label ?? "Draft");

function toggleApprove(row) {
  row.approved = !row.approved;
}

function approveAll() {
  rows.forEach((row) => {
    row.approved = true;
  });
}

function clearApproved() {
  rows.forEach((row) => {
    row.approved = false;
  });
}

function studentInitials(name) {
  return (name || "?")
    .split(" ")
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part.charAt(0).toUpperCase())
    .join("");
}

function updateName(row) {
  router.put(
    `/dashboard/instructor/classes/${props.classData.id}/students/${row.id}`,
    {
      full_name: row.name,
      gender: row.gender,
      date_of_birth: row.date_of_birth,
      phone: row.phone,
    },
    { preserveScroll: true }
  );
}

function submitCertificateRequest() {
  if (!approvedRows.value.length || requestSubmitting.value) {
    return;
  }

  requestSubmitting.value = true;

  router.post(
    `/dashboard/instructor/classes/${props.classData.id}/certificate-request`,
    {
      confirm_request: true,
      student_ids: approvedRows.value.map((student) => student.id),
    },
    {
      preserveScroll: true,
      onFinish: () => {
        requestSubmitting.value = false;
      },
    }
  );
}
</script>

<template>
  <Head :title="`Request Certificate - ${classData.title}`" />

  <DashboardLayout>
    <section class="space-y-5 pb-24">
      <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
        <div>
          <Breadcrumbs :items="breadcrumbItems" />
          <h1 class="mt-4 text-2xl font-black text-slate-950 dark:text-gray-100 sm:text-3xl">{{ classData.title }}</h1>
          <div class="mt-2 flex flex-wrap items-center gap-2 text-xs font-black uppercase tracking-[0.12em]">
            <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300">
              <FileText class="h-3.5 w-3.5" />
              {{ certificateTypeLabel }}
            </span>
            <span class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1 text-slate-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
              {{ requestStatusLabel }}
            </span>
          </div>
        </div>

        <div class="flex flex-wrap gap-2">
          <Link
            :href="`/dashboard/instructor/classes/${classData.id}/attendance`"
            class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800"
          >
            <ArrowLeft class="h-4 w-4" />
            Back
          </Link>
          <button
            type="button"
            :disabled="requestSubmitting || hasPendingRequest || !approvedRows.length"
            @click="submitCertificateRequest"
            class="inline-flex h-10 items-center gap-2 rounded-lg bg-amber-500 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-600 disabled:cursor-not-allowed disabled:opacity-60"
          >
            {{ requestSubmitting ? "Requesting..." : hasPendingRequest ? "Requested" : `Request (${approvedRows.length})` }}
          </button>
        </div>
      </div>

      <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex flex-col gap-3 border-b border-slate-200 px-4 py-4 dark:border-gray-800 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <h2 class="flex items-center gap-2 text-base font-black text-slate-950 dark:text-gray-100">
              <Users class="h-4 w-4 text-amber-500 dark:text-amber-400" />
              Certificate Roster
            </h2>
            <p class="mt-1 text-sm font-semibold text-slate-500 dark:text-gray-400">
              {{ approvedRows.length }} of {{ rows.length }} students selected
            </p>
          </div>

          <div class="flex flex-wrap gap-2">
            <button
              type="button"
              :disabled="!rows.length || allRowsApproved || hasPendingRequest"
              @click="approveAll"
              class="inline-flex h-9 items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3 text-xs font-black text-emerald-700 transition hover:bg-emerald-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300"
            >
              <CheckCircle2 class="h-4 w-4" />
              Select All
            </button>
            <button
              type="button"
              :disabled="!approvedRows.length || hasPendingRequest"
              @click="clearApproved"
              class="inline-flex h-9 items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-xs font-black text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-200 dark:hover:bg-gray-800"
            >
              <RotateCcw class="h-4 w-4" />
              Clear
            </button>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full min-w-[960px] table-fixed border-collapse text-sm">
            <colgroup>
              <col class="w-[10%]" />
              <col class="w-[35%]" />
              <col class="w-[14%]" />
              <col class="w-[18%]" />
              <col class="w-[12%]" />
            </colgroup>
            <thead>
              <tr class="bg-slate-50 text-xs font-black uppercase tracking-[0.08em] text-slate-500 dark:bg-gray-950 dark:text-gray-400">
                <th class="border-b border-slate-200 px-4 py-3 text-left dark:border-gray-800">ID</th>
                <th class="border-b border-slate-200 px-4 py-3 text-left dark:border-gray-800">Name</th>
                <th class="border-b border-slate-200 px-4 py-3 text-left dark:border-gray-800">Gender</th>
                <th class="border-b border-slate-200 px-4 py-3 text-left dark:border-gray-800">Certificate</th>
                <th class="border-b border-slate-200 px-4 py-3 text-center dark:border-gray-800">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(student, index) in rows"
                :key="student.id"
                :class="[
                  'align-middle transition hover:bg-slate-50/80 dark:hover:bg-gray-800/50',
                  student.approved ? 'bg-emerald-50/60 dark:bg-emerald-500/5' : '',
                ]"
              >
                <td class="border-b border-slate-100 px-4 py-3 text-left dark:border-gray-800">
                  <span class="inline-flex h-8 min-w-10 items-center justify-center rounded-lg bg-slate-100 px-2 text-xs font-black text-slate-500 dark:bg-gray-800 dark:text-gray-300">
                    #{{ index + 1 }}
                  </span>
                </td>
                <td class="border-b border-slate-100 px-4 py-3 dark:border-gray-800">
                  <div class="flex min-w-0 items-center gap-3">
                    <div class="min-w-0 flex-1">
                      <input
                        v-model="student.name"
                        type="text"
                        @change="updateName(student)"
                        class="h-9 w-full min-w-0 truncate rounded-lg border border-transparent bg-transparent px-2 text-sm font-black text-slate-900 outline-none transition hover:border-slate-200 hover:bg-white focus:border-amber-400 focus:bg-white focus:ring-4 focus:ring-amber-100 dark:text-gray-100 dark:hover:border-gray-700 dark:hover:bg-gray-950 dark:focus:ring-amber-500/10"
                      />
                    </div>
                  </div>
                </td>
                <td class="border-b border-slate-100 px-4 py-3 text-left font-semibold capitalize text-slate-700 dark:border-gray-800 dark:text-gray-300">
                  {{ student.gender }}
                </td>
                <td class="border-b border-slate-100 px-4 py-3 text-left dark:border-gray-800">
                  <span
                    :class="[
                      'inline-flex h-7 w-32 items-center justify-center gap-1.5 rounded-full border px-3 text-xs font-black',
                      student.approved
                        ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300'
                        : 'border-slate-200 bg-slate-50 text-slate-500 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-400',
                    ]"
                  >
                    {{ student.approved ? "Approved" : "Not Approved" }}
                  </span>
                </td>
                <td class="border-b border-slate-100 px-4 py-3 text-center dark:border-gray-800">
                  <button
                    type="button"
                    :disabled="student.saving || hasPendingRequest"
                    @click="toggleApprove(student)"
                    :class="[
                      'inline-flex h-9 w-24 items-center justify-center gap-1.5 rounded-lg border px-3 text-xs font-black transition disabled:cursor-not-allowed disabled:opacity-60',
                      student.approved
                        ? 'border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300'
                        : 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300',
                    ]"
                  >
                    <component :is="student.approved ? XCircle : CheckCircle2" class="h-3.5 w-3.5" />
                    {{ student.saving ? "Saving..." : student.approved ? "Cancel" : "Approve" }}
                  </button>
                </td>
              </tr>

              <tr v-if="!rows.length">
                <td colspan="5" class="px-4 py-12 text-center text-sm font-semibold text-slate-500 dark:text-gray-400">
                  No active students found in this class.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </DashboardLayout>
</template>
