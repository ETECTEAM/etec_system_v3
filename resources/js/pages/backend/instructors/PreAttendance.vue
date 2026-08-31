<script setup>
import { Head, Link } from "@inertiajs/vue3";
import { ArrowRight, ClipboardCheck, Clock3, Users } from "@lucide/vue";

import DashboardLayout from "../../../layouts/DashboardLayout.vue";

const props = defineProps({
  classes: {
    type: Array,
    default: () => [],
  },
});
</script>

<template>
  <Head :title="$t('Pre Att')" />

  <DashboardLayout>
    <section class="space-y-5 sm:space-y-6">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-600 dark:text-amber-300">
            {{ $t("Attendance Recovery") }}
          </p>
          <h1 class="mt-1 text-2xl font-black text-slate-950 dark:text-gray-100 sm:text-3xl">
            {{ $t("Pre-attendance classes") }}
          </h1>
          <p class="mt-1 max-w-3xl text-sm font-medium text-slate-500 dark:text-gray-400">
            {{ $t("Review classes that still have pre-attendance students and re-track attendance for the missing students.") }}
          </p>
        </div>

      </div>

      <div v-if="classes.length" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="border-b border-slate-200 px-5 py-4 dark:border-gray-800">
          <div class="flex items-center justify-between gap-3">
            <div>
              <h2 class="text-lg font-black text-slate-950 dark:text-gray-100">{{ $t("Classes needing re-track") }}</h2>
              <p class="mt-1 text-sm font-medium text-slate-500 dark:text-gray-400">
                {{ $t("Each row shows the class, unresolved students, and the action to continue attendance recovery.") }}
              </p>
            </div>
            <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-black text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
              {{ $t(":count unresolved", { count: totalUnresolvedStudents }) }}
            </span>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-[1100px] w-full border-collapse text-sm">
            <thead>
              <tr class="bg-slate-50 text-left text-xs font-black uppercase tracking-[0.12em] text-slate-500 dark:bg-gray-950 dark:text-gray-400">
                <th class="border-b border-slate-200 px-5 py-4 dark:border-gray-800">#</th>
                <th class="border-b border-slate-200 px-5 py-4 dark:border-gray-800">{{ $t("Class") }}</th>
                <th class="border-b border-slate-200 px-5 py-4 dark:border-gray-800">{{ $t("Time") }}</th>
                <th class="border-b border-slate-200 px-5 py-4 dark:border-gray-800">{{ $t("Schedule") }}</th>
                <th class="border-b border-slate-200 px-5 py-4 dark:border-gray-800">{{ $t("Students") }}</th>
                <th class="border-b border-slate-200 px-5 py-4 dark:border-gray-800">{{ $t("Action") }}</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="(classData, index) in classes"
                :key="classData.id"
                class="align-top transition hover:bg-slate-50/80 dark:hover:bg-gray-800/50"
              >
                <td class="border-b border-slate-100 px-5 py-5 font-black text-slate-500 dark:border-gray-800 dark:text-gray-400">
                  {{ index + 1 }}
                </td>
                <td class="border-b border-slate-100 px-5 py-5 dark:border-gray-800">
                  <h3 class="mt-3 text-base font-black text-slate-950 dark:text-gray-100">
                    {{ classData.title }}
                  </h3>
                </td>
                <td class="border-b border-slate-100 px-5 py-5 font-bold text-slate-700 dark:border-gray-800 dark:text-gray-200">
                  <div class="flex items-center gap-2">
                    <Clock3 class="h-4 w-4 text-blue-600 dark:text-blue-300" />
                    {{ classData.time }}
                  </div>
                </td>
                <td class="border-b border-slate-100 px-5 py-5 font-bold text-slate-700 dark:border-gray-800 dark:text-gray-200">
                  {{ classData.term }}
                </td>
                <td class="border-b border-slate-100 px-5 py-5 dark:border-gray-800">
                  <div class="flex items-center gap-2 font-bold text-slate-900 dark:text-gray-100">
                    <Users class="h-4 w-4 text-emerald-600 dark:text-emerald-300" />
                    {{ $t(":tracked tracked / :total total", { tracked: classData.tracked_count, total: classData.students }) }}
                  </div>
                </td>
                <td class="border-b border-slate-100 px-5 py-5 dark:border-gray-800">
                  <div class="flex flex-col items-start gap-3">
                    <Link
                      v-if="classData.can_retrack"
                      :href="`/dashboard/instructor/classes/${classData.id}/attendance/track?from=pre-attendance`"
                      class="inline-flex h-10 items-center gap-2 rounded-lg bg-blue-900 px-4 text-sm font-bold text-white transition hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-500"
                    >
                      {{ $t("Re-Track") }}
                      <ArrowRight class="h-4 w-4" />
                    </Link>
                    <span
                      v-else
                      class="inline-flex h-10 items-center rounded-lg bg-slate-100 px-4 text-sm font-bold text-slate-500 dark:bg-gray-800 dark:text-gray-400"
                    >
                      {{ $t("Closed") }}
                    </span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div v-else class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center dark:border-gray-700 dark:bg-gray-900">
        <div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-slate-100 dark:bg-gray-800">
          <ClipboardCheck class="h-6 w-6 text-slate-400 dark:text-gray-500" />
        </div>
        <h2 class="mt-4 text-lg font-black text-slate-950 dark:text-gray-100">{{ $t("No pre-attendance classes") }}</h2>
        <p class="mt-2 text-sm font-medium text-slate-500 dark:text-gray-400">
          {{ $t("Classes with unresolved pre-attendance students will appear here after the grace period ends.") }}
        </p>
      </div>
    </section>
  </DashboardLayout>
</template>
