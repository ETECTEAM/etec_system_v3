<script setup>
import { Head, Link } from "@inertiajs/vue3";
import { ArrowLeft } from "@lucide/vue";

import DashboardLayout from "../../../layouts/DashboardLayout.vue";

defineProps({
  instructor: {
    type: Object,
    required: true,
  },
  sessions: {
    type: Array,
    default: () => [],
  },
});

const statusClasses = {
  pre_attendance: "bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300",
  partial: "bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300",
};
</script>

<template>
  <Head :title="$t('Pre-Att Detail')" />

  <DashboardLayout>
    <section class="space-y-6">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-600 dark:text-amber-300">{{ $t("Attendance Recovery") }}</p>
          <h1 class="mt-1 text-2xl font-black text-slate-950 dark:text-gray-100 sm:text-3xl">{{ instructor.name }}</h1>
        </div>
        <Link href="/dashboard/pre-attendance-counts" class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-200 px-4 text-sm font-bold text-slate-700 hover:bg-slate-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
          <ArrowLeft class="h-4 w-4" />
          {{ $t("Back") }}
        </Link>
      </div>

      <div class="overflow-hidden rounded-lg border border-slate-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <div class="border-b border-slate-200 px-5 py-4 dark:border-gray-800">
          <h2 class="text-lg font-black text-slate-950 dark:text-gray-100">{{ $t("Dates not tracked") }}</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-[1160px] w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs font-black uppercase tracking-[0.12em] text-slate-500 dark:bg-gray-950 dark:text-gray-400">
              <tr>
                <th class="px-5 py-4">{{ $t("Date") }}</th>
                <th class="px-5 py-4">{{ $t("Class") }}</th>
                <th class="px-5 py-4">{{ $t("Time") }}</th>
                <th class="px-5 py-4">{{ $t("Session") }}</th>
                <th class="px-5 py-4">{{ $t("Students") }}</th>
                <th class="px-5 py-4">{{ $t("Missing") }}</th>
                <th class="px-5 py-4">{{ $t("Request") }}</th>
                <th class="px-5 py-4">{{ $t("Completed") }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="session in sessions" :key="session.row_key" class="border-t border-slate-100 dark:border-gray-800">
                <td class="px-5 py-4 font-bold text-slate-900 dark:text-gray-100">{{ session.session_date }}</td>
                <td class="px-5 py-4 text-slate-700 dark:text-gray-300">{{ session.class_title }}</td>
                <td class="px-5 py-4 text-slate-700 dark:text-gray-300">{{ session.time }}</td>
                <td class="px-5 py-4">
                  <span class="rounded-full px-2.5 py-1 text-xs font-black" :class="statusClasses[session.status] ?? statusClasses.pre_attendance">
                    {{ session.status_label }}
                  </span>
                </td>
                <td class="px-5 py-4 text-slate-700 dark:text-gray-300">{{ session.tracked_count }} / {{ session.total_students }}</td>
                <td class="px-5 py-4 font-black text-amber-600 dark:text-amber-300">{{ session.unresolved_count }}</td>
                <td class="px-5 py-4 text-slate-600 dark:text-gray-400">{{ session.request_status_label }}</td>
                <td class="px-5 py-4 text-slate-600 dark:text-gray-400">{{ session.completed_at }}</td>
              </tr>
              <tr v-if="!sessions.length">
                <td colspan="8" class="px-5 py-12 text-center text-sm font-bold text-slate-400">{{ $t("No pre-attendance detail found") }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </DashboardLayout>
</template>
