<script setup>
import { computed } from "vue";
import { Head } from "@inertiajs/vue3";
import { CalendarCheck, CalendarX, Clock, FileCheck2, GraduationCap, Inbox } from "@lucide/vue";
import { useI18n } from "@/i18n";

// Public, read-only page a family member opens by scanning the receipt QR code.
const props = defineProps({
  enrollment: {
    type: Object,
    default: () => ({}),
  },
  attendances: {
    type: Array,
    default: () => [],
  },
  stats: {
    type: Object,
    default: () => ({ present: 0, absent: 0, late: 0, permission: 0, total: 0 }),
  },
});

const { t } = useI18n();

const student = computed(() => props.enrollment.student ?? {});
const classInfo = computed(() => props.enrollment.class ?? {});

const attendanceRate = computed(() =>
  props.stats.total > 0 ? Math.round((props.stats.present / props.stats.total) * 100) : 0
);

const summaryCards = computed(() => [
  { key: "present", label: t("Present"), value: props.stats.present, classes: "bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/30" },
  { key: "late", label: t("Late"), value: props.stats.late, classes: "bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/30" },
  { key: "absent", label: t("Absent"), value: props.stats.absent, classes: "bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/30" },
  { key: "permission", label: t("Permission"), value: props.stats.permission, classes: "bg-sky-50 text-sky-700 ring-sky-200 dark:bg-sky-500/10 dark:text-sky-300 dark:ring-sky-500/30" },
]);

const statusStyles = {
  present: "border-emerald-500 bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300",
  late: "border-amber-500 bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300",
  absent: "border-rose-500 bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300",
  permission: "border-sky-500 bg-sky-50 text-sky-700 dark:bg-sky-500/10 dark:text-sky-300",
};

function rowClasses(status) {
  return statusStyles[status] ?? "border-slate-300 bg-slate-50 text-slate-600 dark:bg-gray-800 dark:text-gray-300";
}

function statusLabel(status) {
  return status ? t(status.charAt(0).toUpperCase() + status.slice(1)) : "-";
}

// Parse as local midnight so a UTC-parsed date can't roll back a day in Asia/Phnom_Penh.
function formatDate(date) {
  if (!date) return "-";
  return new Date(`${date}T00:00:00`).toLocaleDateString("en-GB", { day: "2-digit", month: "short", year: "numeric" });
}
</script>

<template>
  <Head :title="$t('Attendance Summary')" />

  <div class="min-h-screen bg-slate-100 px-4 py-8 dark:bg-gray-950">
    <div class="mx-auto w-full max-w-md space-y-4">
      <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex items-center gap-3">
          <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-white">
            <GraduationCap class="h-6 w-6" />
          </div>
          <div class="min-w-0">
            <h1 class="truncate text-lg font-black text-slate-950 dark:text-gray-100">{{ student.name }}</h1>
            <p class="text-xs font-semibold text-slate-500 dark:text-gray-400">
              #{{ student.id }} · {{ enrollment.reference }}
            </p>
          </div>
        </div>

        <dl class="mt-4 space-y-2 border-t border-slate-100 pt-4 text-sm dark:border-gray-800">
          <div class="flex justify-between gap-4">
            <dt class="font-semibold text-slate-500 dark:text-gray-400">{{ $t('Course') }}</dt>
            <dd class="text-right font-bold text-slate-900 dark:text-gray-100">{{ classInfo.course }}</dd>
          </div>
          <div class="flex justify-between gap-4">
            <dt class="font-semibold text-slate-500 dark:text-gray-400">{{ $t('Class') }}</dt>
            <dd class="text-right font-bold text-slate-900 dark:text-gray-100">{{ classInfo.title }}</dd>
          </div>
          <div class="flex justify-between gap-4">
            <dt class="font-semibold text-slate-500 dark:text-gray-400">{{ $t('Schedule') }}</dt>
            <dd class="text-right font-bold text-slate-900 dark:text-gray-100">{{ classInfo.term }} · {{ classInfo.time }}</dd>
          </div>
        </dl>
      </section>

      <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex items-end justify-between">
          <div>
            <p class="text-xs font-black uppercase tracking-wide text-slate-400 dark:text-gray-500">{{ $t('Attendance Rate') }}</p>
            <p class="mt-1 text-4xl font-black text-slate-950 dark:text-gray-100">{{ attendanceRate }}%</p>
          </div>
          <p class="text-sm font-semibold text-slate-500 dark:text-gray-400">
            {{ stats.present }} / {{ stats.total }} {{ $t('Total Sessions') }}
          </p>
        </div>
        <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-gray-800">
          <div class="h-full rounded-full bg-emerald-500 transition-all" :style="{ width: attendanceRate + '%' }"></div>
        </div>
      </section>

      <section class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div v-for="card in summaryCards" :key="card.key" class="rounded-2xl p-4 text-center ring-1" :class="card.classes">
          <p class="text-2xl font-black">{{ card.value }}</p>
          <p class="mt-0.5 text-xs font-bold uppercase tracking-wide">{{ card.label }}</p>
        </div>
      </section>

      <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex items-center justify-between">
          <h2 class="text-sm font-black uppercase tracking-wide text-slate-500 dark:text-gray-400">{{ $t('Attendance History') }}</h2>
          <span class="text-xs font-bold text-slate-400 dark:text-gray-500">{{ stats.total }}</span>
        </div>

        <ul v-if="attendances.length" class="mt-4 space-y-2">
          <li v-for="(row, index) in attendances" :key="index" class="flex items-center justify-between rounded-xl border-l-4 px-3 py-2.5 text-sm" :class="rowClasses(row.status)">
            <div class="flex items-center gap-2 font-bold">
              <CalendarCheck v-if="row.status === 'present'" class="h-4 w-4" />
              <Clock v-else-if="row.status === 'late'" class="h-4 w-4" />
              <FileCheck2 v-else-if="row.status === 'permission'" class="h-4 w-4" />
              <CalendarX v-else class="h-4 w-4" />
              <span>{{ formatDate(row.date) }}</span>
            </div>
            <div class="flex items-center gap-2">
              <span v-if="row.verification_status && row.verification_status !== 'verified'" class="rounded-full bg-black/5 px-2 py-0.5 text-[10px] font-bold uppercase dark:bg-white/10">
                {{ $t('Unverified') }}
              </span>
              <span class="font-black uppercase">{{ statusLabel(row.status) }}</span>
            </div>
          </li>
        </ul>

        <div v-else class="mt-6 flex flex-col items-center gap-2 py-6 text-center">
          <Inbox class="h-10 w-10 text-slate-300 dark:text-gray-600" />
          <p class="text-sm font-bold text-slate-600 dark:text-gray-300">{{ $t('No attendance records yet') }}</p>
          <p class="text-xs font-semibold text-slate-400 dark:text-gray-500">{{ $t('Class sessions will appear here once attendance has been taken.') }}</p>
        </div>
      </section>

      <p class="pt-2 text-center text-xs font-semibold text-slate-400 dark:text-gray-600">ETEC Center</p>
    </div>
  </div>
</template>
