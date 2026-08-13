<script setup>
import { computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import {
  ArrowLeft,
  ClipboardCheck,
  Eye,
  FileText,
  Mars,
  Pencil,
  RefreshCw,
  Save,
  Trash2,
  Users,
  Venus,
} from "@lucide/vue";

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

const totalPresent = computed(() =>
  props.students.reduce((total, student) => total + Number(student.attendance?.present ?? 0), 0),
);
</script>

<template>
  <Head :title="`${classData.title} Attendance`" />

  <DashboardLayout>
    <section class="space-y-5">
      <div class="flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
        <div>
          <Link
            href="/dashboard"
            class="inline-flex h-9 items-center gap-2 rounded-lg bg-slate-700 px-3 text-xs font-semibold text-white transition hover:bg-slate-800 sm:text-sm"
          >
            <ArrowLeft class="h-4 w-4" />
            Back to Dashboard
          </Link>
          <h1 class="mt-4 text-xl font-black text-blue-950 dark:text-gray-100 sm:text-3xl">{{ classData.title }}</h1>
          <p class="mt-1 text-xs font-semibold text-slate-500 dark:text-gray-400 sm:text-sm">
            Created Date: <span class="rounded-md bg-slate-100 px-2 py-0.5 font-mono dark:bg-gray-800">{{ classData.created_date ?? "-" }}</span>
          </p>
        </div>
        <div class="flex flex-wrap gap-2">
          <button class="inline-flex h-10 items-center gap-2 rounded-lg bg-slate-700 px-3 text-sm font-semibold text-white transition hover:bg-slate-800">
            <RefreshCw class="h-4 w-4" />
            Refresh Table
          </button>
          <button class="inline-flex h-10 items-center gap-2 rounded-lg bg-cyan-500 px-3 text-sm font-semibold text-white transition hover:bg-cyan-600">
            <Users class="h-4 w-4" />
            Group
          </button>
          <button class="inline-flex h-10 items-center gap-2 rounded-lg bg-amber-500 px-3 text-sm font-semibold text-white transition hover:bg-amber-600">
            <FileText class="h-4 w-4" />
            Request Certificate
          </button>
          <button class="inline-flex h-10 items-center gap-2 rounded-lg bg-emerald-600 px-3 text-sm font-semibold text-white transition hover:bg-emerald-700">
            <Save class="h-4 w-4" />
            Save Score
          </button>
          <Link
            :href="`/dashboard/instructor/classes/${classData.id}/attendance/track`"
            class="inline-flex h-10 items-center gap-2 rounded-lg bg-blue-600 px-3 text-sm font-semibold text-white transition hover:bg-blue-700"
          >
            <ClipboardCheck class="h-4 w-4" />
            Track Attendance
          </Link>
        </div>
      </div>

      <div class="grid gap-3 lg:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
          <p class="text-sm font-bold text-slate-500 dark:text-gray-400">Total Students</p>
          <p class="mt-1 text-xl font-black text-slate-950 dark:text-gray-100">{{ students.length }} Students</p>
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
              <tr v-for="student in students" :key="student.enrollment_id" class="align-middle">
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
                    <Link :href="`/dashboard/instructor/classes/${classData.id}/attendance/students/${student.id}`" class="grid h-9 w-9 place-items-center rounded-lg bg-slate-50 text-slate-900 hover:bg-slate-100 dark:bg-gray-800 dark:text-gray-100"><Eye class="h-4 w-4" /></Link>
                    <button class="grid h-9 w-9 place-items-center rounded-lg bg-slate-50 text-slate-900 hover:bg-slate-100 dark:bg-gray-800 dark:text-gray-100"><RefreshCw class="h-4 w-4" /></button>
                    <button class="grid h-9 w-9 place-items-center rounded-lg border border-slate-300 bg-white text-slate-900 hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100"><Pencil class="h-4 w-4" /></button>
                    <button class="grid h-9 w-9 place-items-center rounded-lg bg-rose-500 text-white hover:bg-rose-600"><Trash2 class="h-4 w-4" /></button>
                  </div>
                </td>
              </tr>
              <tr v-if="!students.length">
                <td class="border border-slate-200 px-3 py-12 text-center text-sm font-semibold text-slate-500 dark:border-gray-800" colspan="11">
                  No students are enrolled in this class yet.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <p class="text-xs font-semibold text-slate-400 dark:text-gray-500">
        Present total: {{ totalPresent }}. Score fields are UI-ready for the attendance module data.
      </p>
    </section>
  </DashboardLayout>
</template>
