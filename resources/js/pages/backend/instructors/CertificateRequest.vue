<script setup>
import { computed } from "vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { CheckCircle2, ClipboardList, FileText, Users } from "@lucide/vue";

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

const breadcrumbItems = computed(() => [
  { label: "Dashboard", href: "/dashboard" },
  { label: props.classData.title, href: `/dashboard/instructor/classes/${props.classData.id}/attendance` },
  { label: "Request Certificate", current: true },
]);

const requestForm = useForm({
  confirm_request: true,
  note: props.certificateRequest?.note ?? "",
});

const requestStatusClass = computed(() => {
  if (props.certificateRequest?.status === "approved") {
    return "border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300";
  }

  if (props.certificateRequest?.status === "rejected") {
    return "border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300";
  }

  return "border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300";
});

const requestStatusLabel = computed(() => props.certificateRequest?.status_label ?? "Not requested yet");

function submitRequest() {
  requestForm.post(`/dashboard/instructor/classes/${props.classData.id}/certificate-request`, {
    preserveScroll: true,
  });
}
</script>

<template>
  <Head :title="`Request Certificate - ${classData.title}`" />

  <DashboardLayout>
    <section class="space-y-6">
      <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
        <div>
          <Breadcrumbs :items="breadcrumbItems" />
          <p class="mt-4 inline-flex items-center gap-2 text-xs font-black uppercase tracking-[0.3em] text-amber-500 dark:text-amber-400">
            <FileText class="h-4 w-4" />
            Certificate Request
          </p>
          <h1 class="mt-2 text-2xl font-black text-slate-950 dark:text-gray-100 sm:text-3xl">
            {{ classData.title }}
          </h1>
          <p class="mt-1 text-sm font-semibold text-slate-500 dark:text-gray-400">
            Request type: <span class="text-slate-900 dark:text-gray-100">{{ certificateTypeLabel }}</span>
          </p>
        </div>

        <div class="flex flex-wrap gap-2">
          <Link
            :href="`/dashboard/instructor/classes/${classData.id}/attendance`"
            class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800"
          >
            Back to Class
          </Link>
          <button
            type="button"
            class="inline-flex h-10 items-center gap-2 rounded-lg bg-amber-500 px-4 text-sm font-semibold text-white transition hover:bg-amber-600 disabled:cursor-not-allowed disabled:opacity-70"
            :disabled="requestForm.processing || !studentCount"
            @click="submitRequest"
          >
            <CheckCircle2 class="h-4 w-4" />
            {{ requestForm.processing ? "Submitting..." : "Submit Request" }}
          </button>
        </div>
      </div>

      <div
        v-if="certificateRequest"
        :class="['flex flex-wrap items-center gap-2 rounded-xl border px-4 py-3 text-sm font-semibold', requestStatusClass]"
      >
        <ClipboardList class="h-4 w-4 shrink-0" />
        <span>
          Current status: {{ requestStatusLabel }} · Requested students: {{ certificateRequest.student_count }} · Requested by
          {{ certificateRequest.requested_by ?? "N/A" }}
        </span>
        <span v-if="certificateRequest.requested_at">
          · Requested at {{ certificateRequest.requested_at }}
        </span>
      </div>

      <div class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
          <p class="text-sm font-bold text-slate-500 dark:text-gray-400">Students</p>
          <p class="mt-1 text-2xl font-black text-slate-950 dark:text-gray-100">{{ studentCount }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
          <p class="text-sm font-bold text-slate-500 dark:text-gray-400">Certificate Type</p>
          <p class="mt-1 text-2xl font-black text-slate-950 dark:text-gray-100">{{ certificateTypeLabel }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
          <p class="text-sm font-bold text-slate-500 dark:text-gray-400">Status</p>
          <p class="mt-1 text-2xl font-black text-slate-950 dark:text-gray-100">{{ requestStatusLabel }}</p>
        </div>
      </div>

      <div class="grid gap-6 xl:grid-cols-[minmax(0,65fr)_minmax(280px,35fr)]">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
          <div class="border-b border-slate-200 px-5 py-4 dark:border-gray-800">
            <p class="text-xs font-black uppercase tracking-[0.2em] text-amber-500 dark:text-amber-400">Student Table</p>
            <h2 class="mt-1 text-lg font-black text-slate-950 dark:text-gray-100">Review the class roster before submitting</h2>
          </div>

          <div class="overflow-x-auto">
            <table class="min-w-[760px] w-full border-collapse text-sm">
              <thead>
                <tr class="bg-slate-50 text-xs font-black uppercase tracking-[0.08em] text-slate-500 dark:bg-gray-950 dark:text-gray-400">
                  <th class="border-b border-slate-200 px-4 py-3 text-center dark:border-gray-800">Nº</th>
                  <th class="border-b border-slate-200 px-4 py-3 text-left dark:border-gray-800">Student Name</th>
                  <th class="border-b border-slate-200 px-4 py-3 text-center dark:border-gray-800">Gender</th>
                  <th class="border-b border-slate-200 px-4 py-3 text-center dark:border-gray-800">Action</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(student, index) in students"
                  :key="student.id"
                  class="align-middle transition hover:bg-slate-50/80 dark:hover:bg-gray-800/50"
                >
                  <td class="border-b border-slate-100 px-4 py-3 text-center font-black text-slate-500 dark:border-gray-800 dark:text-gray-400">
                    {{ index + 1 }}
                  </td>
                  <td class="border-b border-slate-100 px-4 py-3 dark:border-gray-800">
                    <input
                      :value="student.name"
                      type="text"
                      readonly
                      class="h-9 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-900 outline-none dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"
                    />
                  </td>
                  <td class="border-b border-slate-100 px-4 py-3 text-center font-semibold capitalize text-slate-700 dark:border-gray-800 dark:text-gray-300">
                    {{ student.gender }}
                  </td>
                  <td class="border-b border-slate-100 px-4 py-3 text-center dark:border-gray-800">
                    <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">
                      Included
                    </span>
                  </td>
                </tr>

                <tr v-if="!students.length">
                  <td colspan="4" class="px-4 py-12 text-center text-sm font-semibold text-slate-500 dark:text-gray-400">
                    No active students found in this class.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="h-fit rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
          <div class="flex items-center gap-2">
            <Users class="h-4 w-4 text-amber-500 dark:text-amber-400" />
            <p class="text-xs font-black uppercase tracking-[0.2em] text-amber-500 dark:text-amber-400">Request Form</p>
          </div>

          <div class="mt-4 space-y-4">
            <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-200">
              <input
                v-model="requestForm.confirm_request"
                type="checkbox"
                class="mt-1 h-4 w-4 rounded border-slate-300 text-amber-600 focus:ring-amber-500"
              />
              <span>This class is ready for certificate request.</span>
            </label>
            <p v-if="requestForm.errors.confirm_request" class="text-sm font-semibold text-rose-600 dark:text-rose-400">
              {{ requestForm.errors.confirm_request }}
            </p>

            <div>
              <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-200">Note</label>
              <textarea
                v-model="requestForm.note"
                rows="6"
                placeholder="Add a short note for the super admin..."
                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-900 outline-none transition focus:border-amber-400 focus:ring-4 focus:ring-amber-100 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100 dark:focus:ring-amber-500/10"
              />
              <p v-if="requestForm.errors.note" class="mt-2 text-sm font-semibold text-rose-600 dark:text-rose-400">
                {{ requestForm.errors.note }}
              </p>
            </div>

            <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-4 text-sm text-slate-600 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-300">
              Submitting this form will mark the class as a certificate request for super admin review.
            </div>
          </div>
        </div>
      </div>
    </section>
  </DashboardLayout>
</template>
