<script setup>
import { Head, Link } from "@inertiajs/vue3";
import { CalendarDays, Clock3, Download, FileText, GraduationCap, Users } from "@lucide/vue";
import DashboardLayout from "../../../layouts/DashboardLayout.vue";

// Ended / completed classes only (see InstructorClassService::endedClasses).
defineProps({
  classes: {
    type: Array,
    default: () => [],
  },
});
</script>

<template>
  <Head :title="$t('Class History')" />

  <DashboardLayout>
    <section class="space-y-5">
      <div>
        <p class="text-xs font-black uppercase tracking-[0.18em] text-amber-500 dark:text-amber-300">{{ $t('Instructor') }}</p>
        <h1 class="mt-1 text-2xl font-black text-slate-950 dark:text-gray-100 sm:text-3xl">{{ $t('Class History') }}</h1>
        <p class="mt-1 max-w-3xl text-sm font-medium text-slate-500 dark:text-gray-400">
          {{ $t('Your ended classes. Open one to view or re-download its result sheet.') }}
        </p>
      </div>

      <div v-if="classes.length" class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        <article
          v-for="item in classes"
          :key="item.id"
          class="flex flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900"
        >
          <div class="flex items-start gap-3">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600 dark:bg-gray-800 dark:text-gray-300">
              <GraduationCap class="h-5 w-5" />
            </div>
            <div class="min-w-0">
              <h2 class="truncate text-sm font-black text-slate-950 dark:text-gray-100">{{ item.course || item.title }}</h2>
              <p class="truncate text-xs font-semibold text-slate-500 dark:text-gray-400">{{ item.title }}</p>
            </div>
            <span class="ml-auto shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold uppercase text-slate-600 dark:bg-gray-800 dark:text-gray-300">
              {{ item.class_status_label }}
            </span>
          </div>

          <dl class="mt-4 space-y-1.5 text-sm">
            <div class="flex items-center gap-2 text-slate-600 dark:text-gray-300">
              <CalendarDays class="h-4 w-4 shrink-0 text-slate-400" />
              <span class="font-semibold">{{ item.term }}</span>
            </div>
            <div class="flex items-center gap-2 text-slate-600 dark:text-gray-300">
              <Clock3 class="h-4 w-4 shrink-0 text-slate-400" />
              <span class="font-semibold">{{ item.time }}</span>
            </div>
            <div class="flex items-center gap-2 text-slate-600 dark:text-gray-300">
              <Users class="h-4 w-4 shrink-0 text-slate-400" />
              <span class="font-semibold">{{ item.students }} {{ $t('Students') }}</span>
            </div>
          </dl>

          <div class="mt-5 flex flex-wrap gap-2 border-t border-slate-100 pt-4 dark:border-gray-800">
            <Link
              :href="`/dashboard/instructor/classes/${item.id}/result`"
              class="inline-flex h-9 flex-1 items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 text-xs font-bold text-slate-700 transition hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800"
            >
              <FileText class="h-4 w-4" />
              {{ $t('View result') }}
            </Link>
            <a :href="`/dashboard/instructor/classes/${item.id}/result?download=1`" class="inline-flex h-9 flex-1 items-center justify-center gap-2 rounded-lg bg-blue-600 px-3 text-xs font-bold text-white transition hover:bg-blue-700">
              <Download class="h-4 w-4" />
              {{ $t('Download PDF') }}
            </a>
          </div>
        </article>
      </div>

      <div v-else class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center dark:border-gray-700 dark:bg-gray-900">
        <FileText class="mx-auto h-10 w-10 text-slate-300 dark:text-gray-600" />
        <p class="mt-3 text-sm font-bold text-slate-600 dark:text-gray-300">{{ $t('No ended classes yet') }}</p>
        <p class="mt-1 text-xs font-semibold text-slate-400 dark:text-gray-500">
          {{ $t('A class shows up here once it has been ended.') }}
        </p>
      </div>
    </section>
  </DashboardLayout>
</template>
