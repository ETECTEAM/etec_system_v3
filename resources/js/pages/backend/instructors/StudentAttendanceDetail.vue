<script setup>
import { computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import { ArrowLeft, CalendarDays, Clock, Mars, User, Venus } from "@lucide/vue";

import DashboardLayout from "../../../layouts/DashboardLayout.vue";

const props = defineProps({
  classData: {
    type: Object,
    required: true,
  },
  student: {
    type: Object,
    required: true,
  },
});

const statusClasses = {
  present: "border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300",
  permission: "border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300",
  absent: "border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300",
};
const displayStatus = (status) => statusClasses[status] ? status : "absent";

const attendanceScore = computed(() => {
  const total = Number(props.student.attendance?.total ?? 0);
  const present = Number(props.student.attendance?.present ?? 0);

  return total > 0 ? Math.round((present / total) * 100) : 0;
});
</script>

<template>
  <Head :title="`${student.name} Attendance`" />

  <DashboardLayout>
    <section class="space-y-5">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
          <Link :href="`/dashboard/instructor/classes/${classData.id}/attendance`" class="inline-flex h-9 items-center gap-2 rounded-lg bg-slate-700 px-3 text-xs font-semibold text-white transition hover:bg-slate-800 sm:text-sm">
            <ArrowLeft class="h-4 w-4" />
            Back to Attendance
          </Link>
          <h1 class="mt-4 text-2xl font-black text-blue-950 dark:text-gray-100 sm:text-3xl">{{ student.name }}</h1>
          <p class="mt-1 flex flex-wrap items-center gap-2 text-sm font-semibold text-slate-500 dark:text-gray-400">
            <span class="inline-flex items-center gap-1"><User class="h-4 w-4" /> #{{ student.id }}</span>
            <span>{{ student.email }}</span>
            <span class="inline-flex items-center gap-1 capitalize">
              <Venus v-if="student.gender === 'female'" class="h-4 w-4 text-rose-500" />
              <Mars v-else class="h-4 w-4 text-blue-500" />
              {{ student.gender || "-" }}
            </span>
          </p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-600 shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
          <p class="font-black text-slate-950 dark:text-gray-100">{{ classData.title }}</p>
          <p class="mt-1 flex items-center gap-2"><Clock class="h-4 w-4" /> {{ classData.term }} ({{ classData.time }})</p>
        </div>
      </div>

      <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
          <p class="text-sm font-bold text-slate-500 dark:text-gray-400">Total</p>
          <p class="mt-1 text-3xl font-black text-slate-950 dark:text-gray-100">{{ student.attendance?.total ?? 0 }}</p>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">
          <p class="text-sm font-bold">Present</p>
          <p class="mt-1 text-3xl font-black">{{ student.attendance?.present ?? 0 }}</p>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300">
          <p class="text-sm font-bold">Permission</p>
          <p class="mt-1 text-3xl font-black">{{ student.attendance?.permission ?? 0 }}</p>
        </div>
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-700 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300">
          <p class="text-sm font-bold">Absent</p>
          <p class="mt-1 text-3xl font-black">{{ student.attendance?.absent ?? 0 }}</p>
        </div>
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-blue-700 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-300">
          <p class="text-sm font-bold">Attendance Score</p>
          <p class="mt-1 text-3xl font-black">{{ attendanceScore }}%</p>
        </div>
      </div>

      <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex items-center gap-2 border-b border-slate-200 px-4 py-3 text-base font-black text-slate-950 dark:border-gray-800 dark:text-gray-100">
          <CalendarDays class="h-5 w-5 text-blue-600" />
          Attendance Detail
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-[760px] w-full border-collapse text-sm">
            <thead>
              <tr class="bg-slate-50 text-left text-xs font-black uppercase tracking-[0.08em] text-slate-500 dark:bg-gray-950 dark:text-gray-400">
                <th class="border-b border-slate-200 px-4 py-3 dark:border-gray-800">Date</th>
                <th class="border-b border-slate-200 px-4 py-3 dark:border-gray-800">Status</th>
                <th class="border-b border-slate-200 px-4 py-3 dark:border-gray-800">Note</th>
                <th class="border-b border-slate-200 px-4 py-3 dark:border-gray-800">Tracked By</th>
                <th class="border-b border-slate-200 px-4 py-3 dark:border-gray-800">Updated</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="record in student.records" :key="`${record.date}-${record.status}`" class="align-middle hover:bg-slate-50/80 dark:hover:bg-gray-800/50">
                <td class="border-b border-slate-100 px-4 py-4 font-mono font-bold text-slate-700 dark:border-gray-800 dark:text-gray-300">{{ record.date }}</td>
                <td class="border-b border-slate-100 px-4 py-4 dark:border-gray-800">
                  <span :class="['inline-flex rounded-lg border px-3 py-1 text-xs font-black capitalize', statusClasses[displayStatus(record.status)] ?? statusClasses.absent]">{{ displayStatus(record.status) }}</span>
                </td>
                <td class="border-b border-slate-100 px-4 py-4 font-semibold text-slate-600 dark:border-gray-800 dark:text-gray-300">{{ record.note }}</td>
                <td class="border-b border-slate-100 px-4 py-4 font-semibold text-slate-600 dark:border-gray-800 dark:text-gray-300">{{ record.tracked_by }}</td>
                <td class="border-b border-slate-100 px-4 py-4 font-mono text-xs font-bold text-slate-500 dark:border-gray-800 dark:text-gray-400">{{ record.updated_at }}</td>
              </tr>
              <tr v-if="!student.records.length">
                <td colspan="5" class="px-4 py-12 text-center text-sm font-semibold text-slate-500 dark:text-gray-400">No attendance has been saved for this student yet.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </DashboardLayout>
</template>
