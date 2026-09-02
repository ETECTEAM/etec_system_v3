<script setup>
import { Head, router } from "@inertiajs/vue3";
import { CheckCircle2 } from "@lucide/vue";

import DashboardLayout from "../../../layouts/DashboardLayout.vue";

defineProps({
  preAttendanceClasses: {
    type: Array,
    default: () => [],
  },
});

const statusClasses = {
  pending: "bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300",
  approved: "bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300",
  rejected: "bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300",
  completed: "bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300",
  pre_attendance: "bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300",
  partial: "bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300",
};

function approveClass(classData) {
  router.post(`/dashboard/pre-attendance-classes/${classData.id}/approve`, {}, {
    preserveScroll: true,
  });
}
</script>

<template>
  <Head :title="$t('Pre-Att Class')" />

  <DashboardLayout>
    <section class="space-y-6">
      <div class="flex flex-col gap-2">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-600 dark:text-amber-300">{{ $t("Attendance Recovery") }}</p>
        <h1 class="text-2xl font-black text-slate-950 dark:text-gray-100 sm:text-3xl">{{ $t("Pre-att classes") }}</h1>
      </div>

      <div class="overflow-hidden rounded-lg border border-slate-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <div class="border-b border-slate-200 px-5 py-4 dark:border-gray-800">
          <h2 class="text-lg font-black text-slate-950 dark:text-gray-100">{{ $t("Classes") }}</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-[1180px] w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs font-black uppercase tracking-[0.12em] text-slate-500 dark:bg-gray-950 dark:text-gray-400">
              <tr>
                <th class="px-5 py-4">{{ $t("Class") }}</th>
                <th class="px-5 py-4">{{ $t("Instructor") }}</th>
                <th class="px-5 py-4">{{ $t("Date") }}</th>
                <th class="px-5 py-4">{{ $t("Time") }}</th>
                <th class="px-5 py-4">{{ $t("Session") }}</th>
                <th class="px-5 py-4">{{ $t("Students") }}</th>
                <th class="px-5 py-4">{{ $t("Missing") }}</th>
                <th class="px-5 py-4">{{ $t("Request") }}</th>
                <th class="px-5 py-4 text-right">{{ $t("Action") }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="classData in preAttendanceClasses" :key="classData.id" class="border-t border-slate-100 dark:border-gray-800">
                <td class="px-5 py-4 font-bold text-slate-900 dark:text-gray-100">{{ classData.class_title }}</td>
                <td class="px-5 py-4 text-slate-700 dark:text-gray-300">{{ classData.instructor }}</td>
                <td class="px-5 py-4 text-slate-700 dark:text-gray-300">{{ classData.session_date }}</td>
                <td class="px-5 py-4 text-slate-700 dark:text-gray-300">{{ classData.time }}</td>
                <td class="px-5 py-4">
                  <span class="rounded-full px-2.5 py-1 text-xs font-black" :class="statusClasses[classData.session_status] ?? statusClasses.pre_attendance">
                    {{ classData.session_status_label }}
                  </span>
                </td>
                <td class="px-5 py-4 text-slate-700 dark:text-gray-300">{{ classData.tracked_count }} / {{ classData.total_students }}</td>
                <td class="px-5 py-4 font-black text-amber-600 dark:text-amber-300">{{ classData.unresolved_count }}</td>
                <td class="px-5 py-4">
                  <span class="rounded-full px-2.5 py-1 text-xs font-black" :class="statusClasses[classData.request_status] ?? 'bg-slate-100 text-slate-600 dark:bg-gray-800 dark:text-gray-300'">
                    {{ classData.request_status_label }}
                  </span>
                </td>
                <td class="px-5 py-4 text-right">
                  <button
                    v-if="classData.request_status !== 'approved'"
                    type="button"
                    class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-emerald-600 px-3 text-xs font-bold text-white hover:bg-emerald-500"
                    @click="approveClass(classData)"
                  >
                    <CheckCircle2 class="h-4 w-4" />
                    {{ $t("Approve") }}
                  </button>
                  <span v-else class="text-xs font-bold text-emerald-600 dark:text-emerald-300">{{ $t("Approved") }}</span>
                </td>
              </tr>
              <tr v-if="!preAttendanceClasses.length">
                <td colspan="9" class="px-5 py-12 text-center text-sm font-bold text-slate-400">{{ $t("No pre-att classes") }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </DashboardLayout>
</template>
