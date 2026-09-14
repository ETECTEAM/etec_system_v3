<script setup>
import { Head, Link } from "@inertiajs/vue3";
import { ArrowRight, RotateCcw } from "@lucide/vue";

import DashboardLayout from "../../../layouts/DashboardLayout.vue";

defineProps({
  instructorStats: {
    type: Array,
    default: () => [],
  },
});
</script>

<template>
  <Head :title="$t('Pre-Att Count')" />

  <DashboardLayout>
    <section class="space-y-6">
      <div class="flex flex-col gap-2">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-600 dark:text-amber-300">{{ $t("Attendance Recovery") }}</p>
        <h1 class="text-2xl font-black text-slate-950 dark:text-gray-100 sm:text-3xl">{{ $t("Pre-attendance count") }}</h1>
      </div>

      <div class="overflow-hidden rounded-lg border border-slate-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <div class="border-b border-slate-200 px-5 py-4 dark:border-gray-800">
          <h2 class="text-lg font-black text-slate-950 dark:text-gray-100">{{ $t("Instructor counts") }}</h2>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-[820px] w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs font-black uppercase tracking-[0.12em] text-slate-500 dark:bg-gray-950 dark:text-gray-400">
              <tr>
                <th class="px-5 py-4">{{ $t("Instructor") }}</th>
                <th class="px-5 py-4">{{ $t("Pre-att count") }}</th>
                <th class="px-5 py-4">{{ $t("Re-track count") }}</th>
                <th class="px-5 py-4 text-right">{{ $t("Detail") }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="stat in instructorStats" :key="stat.instructor_id" class="border-t border-slate-100 dark:border-gray-800">
                <td class="px-5 py-4 font-bold text-slate-900 dark:text-gray-100">{{ stat.instructor }}</td>
                <td class="px-5 py-4 font-black text-amber-600 dark:text-amber-300">{{ stat.pre_attendance_count }}</td>
                <td class="px-5 py-4">
                  <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 px-3 py-1 text-xs font-black text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">
                    <RotateCcw class="h-3.5 w-3.5" />
                    {{ stat.retrack_count }}
                  </span>
                </td>
                <td class="px-5 py-4 text-right">
                  <Link :href="`/dashboard/pre-attendance-counts/instructors/${stat.instructor_id}`" class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-slate-900 px-3 text-xs font-bold text-white hover:bg-slate-700 dark:bg-blue-600 dark:hover:bg-blue-500">
                    {{ $t("View Detail") }}
                    <ArrowRight class="h-4 w-4" />
                  </Link>
                </td>
              </tr>
              <tr v-if="!instructorStats.length">
                <td colspan="4" class="px-5 py-12 text-center text-sm font-bold text-slate-400">{{ $t("No instructor counts yet") }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </DashboardLayout>
</template>
