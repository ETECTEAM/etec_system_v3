<script setup>
import { computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import { AlertTriangle, ArrowRight, ClipboardCheck, Clock3, Users } from "@lucide/vue";

import DashboardLayout from "../../../layouts/DashboardLayout.vue";

const props = defineProps({
  classes: {
    type: Array,
    default: () => [],
  },
});

const totalUnresolvedStudents = computed(() =>
  props.classes.reduce((sum, classData) => sum + Number(classData.unresolved_count ?? 0), 0),
);
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

        <div class="grid grid-cols-2 gap-3 sm:min-w-[340px]">
          <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500 dark:text-gray-400">{{ $t("Classes") }}</p>
            <p class="mt-2 text-3xl font-black text-slate-950 dark:text-gray-100">{{ classes.length }}</p>
          </div>
          <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 shadow-sm dark:border-amber-500/20 dark:bg-amber-500/10">
            <p class="text-xs font-bold uppercase tracking-[0.12em] text-amber-700 dark:text-amber-300">{{ $t("Unresolved") }}</p>
            <p class="mt-2 text-3xl font-black text-amber-700 dark:text-amber-300">{{ totalUnresolvedStudents }}</p>
          </div>
        </div>
      </div>

      <div v-if="classes.length" class="grid grid-cols-1 gap-5 xl:grid-cols-2">
        <article
          v-for="classData in classes"
          :key="classData.id"
          class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900"
        >
          <div class="border-b border-slate-200 px-5 py-4 dark:border-gray-800">
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="inline-flex items-center gap-2 rounded-full bg-amber-50 px-3 py-1 text-[11px] font-black uppercase tracking-[0.14em] text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
                  <AlertTriangle class="h-3.5 w-3.5" />
                  {{ $t(classData.session_status_label) }}
                </div>
                <h2 class="mt-3 text-lg font-black text-slate-950 dark:text-gray-100">
                  {{ classData.title }}
                </h2>
                <p class="mt-1 text-sm font-medium text-slate-500 dark:text-gray-400">
                  {{ classData.course }}
                </p>
              </div>

              <Link
                v-if="classData.can_retrack"
                :href="`/dashboard/instructor/classes/${classData.id}/attendance/track`"
                class="inline-flex h-10 shrink-0 items-center gap-2 rounded-lg bg-blue-900 px-4 text-sm font-bold text-white transition hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-500"
              >
                {{ $t("Re-Track") }}
                <ArrowRight class="h-4 w-4" />
              </Link>
              <span v-else class="inline-flex h-10 shrink-0 items-center rounded-lg bg-slate-100 px-4 text-sm font-bold text-slate-500 dark:bg-gray-800 dark:text-gray-400">
                {{ $t("Closed") }}
              </span>
            </div>
          </div>

          <div class="space-y-4 px-5 py-4">
            <div class="grid gap-3 sm:grid-cols-3">
              <div class="rounded-xl bg-slate-50 p-3 dark:bg-gray-800/80">
                <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500 dark:text-gray-400">{{ $t("Time") }}</p>
                <p class="mt-1 flex items-center gap-2 text-sm font-bold text-slate-900 dark:text-gray-100">
                  <Clock3 class="h-4 w-4 text-blue-600 dark:text-blue-300" />
                  {{ classData.time }}
                </p>
              </div>
              <div class="rounded-xl bg-slate-50 p-3 dark:bg-gray-800/80">
                <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500 dark:text-gray-400">{{ $t("Schedule") }}</p>
                <p class="mt-1 text-sm font-bold text-slate-900 dark:text-gray-100">{{ classData.term }}</p>
              </div>
              <div class="rounded-xl bg-slate-50 p-3 dark:bg-gray-800/80">
                <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-slate-500 dark:text-gray-400">{{ $t("Students") }}</p>
                <p class="mt-1 flex items-center gap-2 text-sm font-bold text-slate-900 dark:text-gray-100">
                  <Users class="h-4 w-4 text-emerald-600 dark:text-emerald-300" />
                  {{ $t(":tracked tracked / :total total", { tracked: classData.tracked_count, total: classData.students }) }}
                </p>
              </div>
            </div>

            <div>
              <div class="flex items-center justify-between gap-3">
                <h3 class="text-sm font-black text-slate-950 dark:text-gray-100">{{ $t("Students waiting for attendance") }}</h3>
                <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-black text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
                  {{ $t(":count unresolved", { count: classData.unresolved_count }) }}
                </span>
              </div>

              <div class="mt-3 flex flex-wrap gap-2">
                <span
                  v-for="student in classData.unresolved_students"
                  :key="student.id"
                  class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200"
                >
                  #{{ student.id }} · {{ student.name }}
                </span>
              </div>
            </div>
          </div>
        </article>
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
