<script setup>
import { computed, reactive } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import { ArrowLeft, Clock, Save } from "@lucide/vue";

import DashboardLayout from "../../../layouts/DashboardLayout.vue";

const props = defineProps({
  classData: {
    type: Object,
    required: true,
  },
  students: {
    type: Array,
    default: () => [],
  },
});

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

const attendance = reactive(
  Object.fromEntries(props.students.map((student) => [student.id, "absent"])),
);

const permissionNotes = reactive(
  Object.fromEntries(props.students.map((student) => [student.id, ""])),
);

const totals = computed(() => {
  const values = Object.values(attendance);

  return {
    present: values.filter((value) => value === "present").length,
    permission: values.filter((value) => value === "permission").length,
    absent: values.filter((value) => value === "absent").length,
  };
});
</script>

<template>
  <Head :title="`Track Attendance - ${classData.title}`" />

  <DashboardLayout>
    <section class="space-y-3 sm:space-y-5">
      <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:p-6">
        <div class="flex flex-col gap-3 sm:gap-4 lg:flex-row lg:items-start lg:justify-between">
          <div class="min-w-0">
            <Link
              :href="`/dashboard/instructor/classes/${classData.id}/attendance`"
              class="inline-flex h-8 max-w-full items-center gap-1.5 rounded-lg bg-slate-700 px-2.5 text-[11px] font-semibold text-white transition hover:bg-slate-800 sm:h-9 sm:gap-2 sm:px-3 sm:text-sm"
            >
              <ArrowLeft class="h-3.5 w-3.5 shrink-0 sm:h-4 sm:w-4" />
              <span class="truncate">Back to Attendance</span>
            </Link>

            <p class="mt-3 text-[10px] font-black uppercase tracking-[0.12em] text-blue-600 dark:text-blue-400 sm:mt-5 sm:text-[11px] sm:tracking-[0.18em]">Track Attendance</p>
            <h1 class="mt-0.5 break-words text-lg font-black tracking-tight text-slate-950 dark:text-gray-100 sm:mt-1 sm:text-3xl">{{ classData.title }}</h1>
            <p class="mt-0.5 flex min-w-0 items-center gap-1.5 text-xs font-semibold text-slate-500 dark:text-gray-400 sm:mt-1 sm:gap-2 sm:text-sm">
              <Clock class="h-3.5 w-3.5 shrink-0 sm:h-4 sm:w-4" />
              <span class="min-w-0 break-words">{{ classData.term }} · {{ classData.time }}</span>
            </p>
          </div>

          <button
            type="button"
            class="inline-flex h-9 w-full items-center justify-center gap-1.5 rounded-lg bg-blue-600 px-3 text-xs font-bold text-white shadow-sm transition hover:bg-blue-700 sm:h-10 sm:w-auto sm:gap-2 sm:px-4 sm:text-sm"
          >
            <Save class="h-3.5 w-3.5 shrink-0 sm:h-4 sm:w-4" />
            Save Attendance
          </button>
        </div>
      </div>

      <div class="grid grid-cols-3 gap-2 sm:gap-3">
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-2.5 text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300 sm:rounded-xl sm:p-4">
          <p class="text-[9px] font-black uppercase tracking-[0.04em] sm:text-xs sm:tracking-[0.14em]">Present</p>
          <p class="mt-0.5 text-xl font-black sm:mt-1 sm:text-3xl">{{ totals.present }}</p>
        </div>
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-2.5 text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300 sm:rounded-xl sm:p-4">
          <p class="text-[9px] font-black uppercase tracking-[0.04em] sm:text-xs sm:tracking-[0.14em]">Permission</p>
          <p class="mt-0.5 text-xl font-black sm:mt-1 sm:text-3xl">{{ totals.permission }}</p>
        </div>
        <div class="rounded-lg border border-rose-200 bg-rose-50 p-2.5 text-rose-700 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300 sm:rounded-xl sm:p-4">
          <p class="text-[9px] font-black uppercase tracking-[0.04em] sm:text-xs sm:tracking-[0.14em]">Absent</p>
          <p class="mt-0.5 text-xl font-black sm:mt-1 sm:text-3xl">{{ totals.absent }}</p>
        </div>
      </div>

      <div class="space-y-2 md:hidden">
        <article
          v-for="student in students"
          :key="student.enrollment_id"
          class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm dark:border-gray-800 dark:bg-gray-900"
        >
          <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
              <p class="text-[9px] font-black uppercase tracking-[0.08em] text-slate-400 dark:text-gray-500">Nº {{ student.roster_no }}</p>
              <h2 class="mt-0.5 break-words text-sm font-black text-slate-950 dark:text-gray-100">{{ student.name }}</h2>
            </div>
            <span class="shrink-0 rounded-md bg-slate-100 px-2 py-0.5 font-mono text-[10px] font-black text-slate-700 dark:bg-gray-800 dark:text-gray-300">#{{ student.id }}</span>
          </div>

          <div class="mt-3 grid grid-cols-3 gap-1.5">
            <button
              v-for="status in statuses"
              :key="status.value"
              type="button"
              :disabled="attendance[student.id] === status.value"
              :class="[
                'h-9 rounded-md border px-1.5 text-[10px] font-black transition disabled:cursor-not-allowed',
                attendance[student.id] === status.value
                  ? status.active
                  : status.button,
              ]"
              @click="attendance[student.id] = status.value"
            >
              {{ status.label }}
            </button>
          </div>

          <input
            v-model="permissionNotes[student.id]"
            type="text"
            placeholder="Enter note..."
            class="mt-2 h-9 w-full rounded-md border border-slate-200 bg-white px-2.5 text-xs font-semibold text-slate-900 outline-none transition focus:border-blue-400 focus:ring-4 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100 dark:focus:ring-blue-500/10"
          />
        </article>

        <div v-if="!students.length" class="rounded-lg border border-dashed border-slate-300 bg-white px-3 py-10 text-center text-xs font-semibold text-slate-500 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400">
          No students are enrolled in this class yet.
        </div>
      </div>

      <div class="hidden overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900 md:block">
        <div class="overflow-x-auto">
          <table class="min-w-[920px] w-full border-collapse text-sm">
            <thead>
              <tr class="bg-slate-50 text-center text-xs font-black uppercase tracking-[0.08em] text-slate-500 dark:bg-gray-950 dark:text-gray-400">
                <th class="border-b border-slate-200 px-4 py-4 dark:border-gray-800">Nº</th>
                <th class="border-b border-slate-200 px-4 py-4 text-left dark:border-gray-800">Student ID</th>
                <th class="border-b border-slate-200 px-4 py-4 text-left dark:border-gray-800">Student Name</th>
                <th class="border-b border-slate-200 px-4 py-4 dark:border-gray-800">Attendance</th>
                <th class="w-56 border-b border-slate-200 px-4 py-4 text-left dark:border-gray-800">Note</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="student in students"
                :key="student.enrollment_id"
                class="align-middle transition hover:bg-slate-50/80 dark:hover:bg-gray-800/50"
              >
                <td class="border-b border-slate-100 px-4 py-4 text-center font-black text-slate-500 dark:border-gray-800 dark:text-gray-400">
                  {{ student.roster_no }}
                </td>
                <td class="border-b border-slate-100 px-4 py-4 font-mono text-sm font-black text-slate-700 dark:border-gray-800 dark:text-gray-300">
                  #{{ student.id }}
                </td>
                <td class="border-b border-slate-100 px-4 py-4 dark:border-gray-800">
                  <p class="font-black text-slate-950 dark:text-gray-100">{{ student.name }}</p>
                </td>
                <td class="border-b border-slate-100 px-4 py-4 dark:border-gray-800">
                  <div class="flex flex-wrap justify-center gap-2">
                    <button
                      v-for="status in statuses"
                      :key="status.value"
                      type="button"
                      :disabled="attendance[student.id] === status.value"
                      :class="[
                        'h-10 rounded-lg border px-3 text-xs font-black transition disabled:cursor-not-allowed',
                        attendance[student.id] === status.value
                          ? status.active
                          : status.button,
                      ]"
                      @click="attendance[student.id] = status.value"
                    >
                      {{ status.label }}
                    </button>
                  </div>
                </td>
                <td class="w-56 border-b border-slate-100 px-4 py-4 dark:border-gray-800">
                  <input
                    v-model="permissionNotes[student.id]"
                    type="text"
                    placeholder="Enter note..."
                    class="h-10 w-48 max-w-full rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-blue-400 focus:ring-4 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100 dark:focus:ring-blue-500/10"
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
    </section>
  </DashboardLayout>
</template>
