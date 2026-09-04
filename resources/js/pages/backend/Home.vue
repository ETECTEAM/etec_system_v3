<script setup>
import { computed, reactive, watch } from "vue";
import { Head, router } from "@inertiajs/vue3";
import { Activity, BarChart3, CalendarDays, ChevronDown, DollarSign, GraduationCap, RotateCcw, Users } from "@lucide/vue";
import DashboardLayout from "../../layouts/DashboardLayout.vue";

const props = defineProps({
  report: {
    type: Object,
    required: true,
  },
});

const filters = reactive({
  quick: props.report.filters?.quick ?? "all_time",
  start_date: props.report.filters?.start_date ?? "",
  end_date: props.report.filters?.end_date ?? "",
  year: props.report.filters?.year ?? "",
});

let filterTimer = null;

const datePeriodOptions = [
  { value: "all_time", label: "All Time" },
  { value: "today", label: "Today" },
  { value: "this_week", label: "This Week" },
  { value: "this_month", label: "This Month" },
  { value: "this_year", label: "This Year" },
  { value: "custom", label: "Custom Range" },
];

const summaryCards = computed(() => [
  { label: "Total Students Enrolled", value: numberFormat(props.report.summary.total_students_enrolled), change: props.report.summary.enrollment_change_percent, icon: Users, tone: "blue" },
  { label: "Total Revenue Collected", value: moneyFormat(props.report.summary.total_revenue_collected), change: props.report.summary.revenue_change_percent, icon: DollarSign, tone: "emerald" },
  { label: "New Enrollments", value: numberFormat(props.report.summary.new_enrollments), change: props.report.summary.new_enrollment_change_percent, icon: GraduationCap, tone: "violet" },
  { label: "Average Revenue Per Enrollment", value: moneyFormat(props.report.summary.average_revenue_per_enrollment), change: props.report.summary.average_revenue_change_percent, icon: Activity, tone: "amber" },
]);

const isCustomRange = computed(() => filters.quick === "custom");
const today = new Date();
const donutSize = 148;
const donutRadius = 58;
const donutCircumference = 2 * Math.PI * donutRadius;
const paymentStatusItems = computed(() => props.report.paymentStatus?.items ?? []);

watch(filters, () => {
  clearTimeout(filterTimer);
  filterTimer = setTimeout(applyFilters, 250);
}, { deep: true });

function applyFilters() {
  router.get("/dashboard", cleanFilters(filters), {
    preserveState: true,
    preserveScroll: true,
    replace: true,
    only: ["report"],
  });
}

function resetFilters() {
  clearTimeout(filterTimer);
  filters.quick = "all_time";
  filters.start_date = "";
  filters.end_date = "";
  filters.year = "";
  applyFilters();
}

function onDatePeriodChange() {
  filters.year = "";

  if (isCustomRange.value) {
    filters.start_date = filters.start_date || currentMonthStart();
    filters.end_date = filters.end_date || currentMonthEnd();
    return;
  }

  filters.start_date = "";
  filters.end_date = "";
}

function onYearChange() {
  if (filters.year) {
    filters.quick = "all_time";
    filters.start_date = "";
    filters.end_date = "";
  }
}

function cleanFilters(values) {
  return Object.fromEntries(Object.entries(values).filter(([, value]) => value !== "" && value !== null && value !== undefined));
}

function dateValue(date) {
  return date.toLocaleDateString("en-CA");
}

function currentMonthStart() {
  return dateValue(new Date(today.getFullYear(), today.getMonth(), 1));
}

function currentMonthEnd() {
  return dateValue(new Date(today.getFullYear(), today.getMonth() + 1, 0));
}

function numberFormat(value) {
  return new Intl.NumberFormat("en-US").format(Number(value ?? 0));
}

function moneyFormat(value) {
  return new Intl.NumberFormat("en-US", { style: "currency", currency: "USD" }).format(Number(value ?? 0));
}

function changeLabel(value) {
  if (value === null || value === undefined) return "No previous period";
  return `${Number(value) >= 0 ? "+" : ""}${value}%`;
}

function changeClass(value) {
  if (value === null || value === undefined) return "text-slate-500 dark:text-gray-400";
  return Number(value) >= 0 ? "text-emerald-600 dark:text-emerald-400" : "text-red-500 dark:text-red-400";
}

function toneClass(tone) {
  return {
    blue: "bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300",
    emerald: "bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300",
    violet: "bg-violet-100 text-violet-700 dark:bg-violet-500/10 dark:text-violet-300",
    amber: "bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300",
  }[tone] ?? "bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200";
}

function chartPoints(series, width = 560, height = 180) {
  const values = series.map((item) => Number(item.value ?? 0));
  const max = Math.max(...values, 1);
  const step = series.length > 1 ? width / (series.length - 1) : width;

  return series.map((item, index) => ({
    ...item,
    x: index * step,
    y: height - (Number(item.value ?? 0) / max) * height,
  }));
}

function linePath(series) {
  return chartPoints(series).map((point, index) => `${index === 0 ? "M" : "L"} ${point.x.toFixed(2)} ${point.y.toFixed(2)}`).join(" ");
}

function areaPath(series) {
  const path = linePath(series);
  return path ? `${path} L 560 180 L 0 180 Z` : "";
}

function labelStep(series) {
  return Math.max(1, Math.ceil(series.length / 6));
}

function barHeight(value, series) {
  const max = Math.max(...series.map((item) => Number(item.value ?? 0)), 1);
  return (Number(value ?? 0) / max) * 170;
}

function donutDash(item) {
  const segment = ((Number(item.percent) || 0) / 100) * donutCircumference;
  return `${segment} ${donutCircumference - segment}`;
}

function donutOffset(index) {
  const percentBefore = paymentStatusItems.value.slice(0, index).reduce((sum, item) => sum + (Number(item.percent) || 0), 0);
  return -(percentBefore / 100) * donutCircumference;
}
</script>

<template>
  <Head :title="$t('Dashboard')" />
  <DashboardLayout>
    <section class="space-y-4">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <h1 class="text-2xl font-black text-slate-950 dark:text-gray-100">{{ $t('Report Overview') }}</h1>
          <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">{{ $t('Student enrollment and revenue summary') }}</p>
        </div>
        <div class="inline-flex h-10 items-center gap-2 self-start rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-600 shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
          <CalendarDays class="h-4 w-4 text-blue-600 dark:text-blue-400" />
          {{ report.summary.period_label }}
        </div>
      </div>

      <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm dark:border-gray-800 dark:bg-gray-900/95">
        <div class="flex flex-wrap items-end gap-3">
          <label class="min-w-[210px] flex-1">
            <span class="mb-1.5 block text-xs font-bold text-slate-600 dark:text-gray-300">{{ $t('Date Range') }}</span>
            <span class="relative block">
              <CalendarDays class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-blue-500 dark:text-blue-400" />
              <select v-model="filters.quick" class="h-11 w-full appearance-none rounded-lg border border-slate-200 bg-slate-50 pl-10 pr-9 text-sm font-semibold text-slate-800 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-800/80 dark:text-gray-100 dark:focus:border-blue-400 dark:focus:bg-gray-800 dark:focus:ring-blue-500/20" @change="onDatePeriodChange">
                <option v-for="item in datePeriodOptions" :key="item.value" :value="item.value">{{ $t(item.label) }}</option>
              </select>
              <ChevronDown class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-gray-500" />
            </span>
          </label>

          <label v-if="!isCustomRange" class="min-w-[150px] flex-1">
            <span class="mb-1.5 block text-xs font-bold text-slate-600 dark:text-gray-300">{{ $t('Year') }}</span>
            <span class="relative block">
              <CalendarDays class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-gray-500" />
              <select v-model="filters.year" class="h-11 w-full appearance-none rounded-lg border border-slate-200 bg-slate-50 pl-10 pr-9 text-sm font-semibold text-slate-800 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-800/80 dark:text-gray-100 dark:focus:border-blue-400 dark:focus:bg-gray-800 dark:focus:ring-blue-500/20" @change="onYearChange">
                <option value="">{{ $t('All Years') }}</option>
                <option v-for="year in report.filterOptions.years" :key="year" :value="year">{{ year }}</option>
              </select>
              <ChevronDown class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-gray-500" />
            </span>
          </label>

          <label v-if="isCustomRange" class="min-w-[190px] flex-1">
            <span class="mb-1.5 block text-xs font-bold text-slate-600 dark:text-gray-300">{{ $t('Start Date') }}</span>
            <span class="relative block">
              <CalendarDays class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-gray-500" />
              <input v-model="filters.start_date" type="date" class="h-11 w-full rounded-lg border border-slate-200 bg-slate-50 pl-10 pr-3 text-sm font-semibold text-slate-800 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-800/80 dark:text-gray-100 dark:focus:border-blue-400 dark:focus:bg-gray-800 dark:focus:ring-blue-500/20" />
            </span>
          </label>

          <label v-if="isCustomRange" class="min-w-[190px] flex-1">
            <span class="mb-1.5 block text-xs font-bold text-slate-600 dark:text-gray-300">{{ $t('End Date') }}</span>
            <span class="relative block">
              <CalendarDays class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-gray-500" />
              <input v-model="filters.end_date" type="date" class="h-11 w-full rounded-lg border border-slate-200 bg-slate-50 pl-10 pr-3 text-sm font-semibold text-slate-800 outline-none transition focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-800/80 dark:text-gray-100 dark:focus:border-blue-400 dark:focus:bg-gray-800 dark:focus:ring-blue-500/20" />
            </span>
          </label>

          <button type="button" class="ml-auto inline-flex h-11 min-w-[145px] items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 dark:border-gray-700 dark:bg-gray-800/80 dark:text-gray-200 dark:hover:border-blue-500/40 dark:hover:bg-blue-500/10 dark:hover:text-blue-300" @click="resetFilters">
            <RotateCcw class="h-4 w-4" />
            {{ $t('Reset Filters') }}
          </button>
        </div>

      
      </div>

      <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <article v-for="card in summaryCards" :key="card.label" class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
          <div class="flex items-start gap-4">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg" :class="toneClass(card.tone)">
              <component :is="card.icon" class="h-5 w-5" />
            </div>
            <div>
              <p class="text-xs font-semibold leading-5 text-slate-500 dark:text-gray-400">{{ $t(card.label) }}</p>
              <p class="mt-1 text-2xl font-black leading-7 text-slate-950 dark:text-gray-100">{{ card.value }}</p>
              <p class="mt-2 text-xs font-semibold">
                <span :class="changeClass(card.change)">{{ changeLabel(card.change) }}</span>
                <span class="block text-slate-500 dark:text-gray-400">{{ $t('vs previous period') }}</span>
              </p>
            </div>
          </div>
        </article>
      </div>

      <div class="grid gap-4 xl:grid-cols-2">
        <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
          <div class="mb-4 flex items-center justify-between gap-3">
            <h2 class="text-base font-black text-slate-950 dark:text-gray-100">{{ $t('Student Enrollment Trend') }}</h2>
            <BarChart3 class="h-5 w-5 text-blue-500" />
          </div>
          <svg viewBox="0 0 560 220" class="h-64 w-full overflow-visible">
            <path :d="areaPath(report.enrollmentTrend)" fill="rgb(37 99 235 / 0.14)" />
            <path :d="linePath(report.enrollmentTrend)" fill="none" stroke="#2563eb" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />
            <g v-for="point in chartPoints(report.enrollmentTrend)" :key="point.label">
              <circle :cx="point.x" :cy="point.y" r="4" fill="#2563eb" />
            </g>
            <g v-for="(point, index) in chartPoints(report.enrollmentTrend).filter((_, i) => i % labelStep(report.enrollmentTrend) === 0)" :key="`enroll-${index}`">
              <text :x="point.x" y="210" text-anchor="middle" class="fill-slate-500 text-[11px]">{{ point.label }}</text>
            </g>
          </svg>
        </article>

        <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
          <div class="mb-4 flex items-center justify-between gap-3">
            <h2 class="text-base font-black text-slate-950 dark:text-gray-100">{{ $t('Revenue Overview') }}</h2>
            <DollarSign class="h-5 w-5 text-emerald-500" />
          </div>
          <svg viewBox="0 0 560 220" class="h-64 w-full overflow-visible">
            <g v-for="(item, index) in report.revenueTrend" :key="item.label">
              <rect :x="index * (540 / Math.max(report.revenueTrend.length, 1)) + 10" :y="180 - barHeight(item.value, report.revenueTrend)" :width="Math.max(8, 540 / Math.max(report.revenueTrend.length, 1) - 8)" :height="barHeight(item.value, report.revenueTrend)" rx="6" fill="#10b981" opacity="0.85" />
              <text v-if="index % labelStep(report.revenueTrend) === 0" :x="index * (540 / Math.max(report.revenueTrend.length, 1)) + 18" y="210" class="fill-slate-500 text-[11px]">{{ item.label }}</text>
            </g>
          </svg>
        </article>
      </div>

      <div class="grid gap-4 xl:grid-cols-2">
        <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
          <div class="mb-3 flex items-center justify-between gap-3">
            <h2 class="text-base font-black text-slate-950 dark:text-gray-100">{{ $t('Top Courses by Enrollment') }}</h2>
            <a href="/dashboard/enroll" class="text-sm font-bold text-blue-600 underline underline-offset-2 dark:text-blue-400">{{ $t('View All') }}</a>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full min-w-[520px] text-left text-sm">
              <thead>
                <tr class="bg-slate-50 text-xs font-bold text-slate-500 dark:bg-gray-800/80 dark:text-gray-400">
                  <th class="rounded-l-lg px-4 py-3">#</th>
                  <th class="px-4 py-3">{{ $t('Course') }}</th>
                  <th class="px-4 py-3">{{ $t('Enrollments') }}</th>
                  <th class="rounded-r-lg px-4 py-3">{{ $t('Revenue') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-gray-800">
                <tr v-for="(course, index) in report.courseStats" :key="course.course_title" class="text-slate-700 dark:text-gray-300">
                  <td class="px-4 py-3">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-blue-100 text-xs font-black text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">{{ index + 1 }}</span>
                  </td>
                  <td class="px-4 py-3 font-semibold text-slate-950 dark:text-gray-100">{{ course.course_title }}</td>
                  <td class="px-4 py-3 font-semibold">{{ numberFormat(course.enrollments) }}</td>
                  <td class="px-4 py-3 font-semibold">{{ moneyFormat(course.revenue) }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="!report.courseStats.length" class="rounded-lg border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500 dark:border-gray-700 dark:text-gray-400">
            {{ $t('No enrollment data found for these filters.') }}
          </div>
        </article>

        <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
          <h2 class="mb-3 text-base font-black text-slate-950 dark:text-gray-100">{{ $t('Payment Status') }}</h2>
          <div class="grid gap-5 sm:grid-cols-[190px_1fr] sm:items-center">
            <div class="relative mx-auto h-[190px] w-[190px]">
              <svg :viewBox="`0 0 ${donutSize} ${donutSize}`" class="h-full w-full -rotate-90">
                <circle :cx="donutSize / 2" :cy="donutSize / 2" :r="donutRadius" fill="none" stroke="currentColor" stroke-width="22" class="text-slate-100 dark:text-gray-800" />
                <circle
                  v-for="(item, index) in paymentStatusItems"
                  :key="item.status"
                  :cx="donutSize / 2"
                  :cy="donutSize / 2"
                  :r="donutRadius"
                  fill="none"
                  :stroke="item.color"
                  stroke-linecap="butt"
                  stroke-width="22"
                  :stroke-dasharray="donutDash(item)"
                  :stroke-dashoffset="donutOffset(index)"
                />
              </svg>
              <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                <p class="text-2xl font-black text-slate-950 dark:text-gray-100">{{ numberFormat(report.paymentStatus?.total) }}</p>
                <p class="text-xs font-semibold text-slate-500 dark:text-gray-400">{{ $t('Total Payments') }}</p>
              </div>
            </div>
            <div class="space-y-3">
              <div v-for="item in paymentStatusItems" :key="item.status" class="grid grid-cols-[1fr_auto] items-center gap-3 text-sm">
                <div class="flex items-center gap-3">
                  <span class="h-3.5 w-3.5 rounded-full" :style="{ backgroundColor: item.color }"></span>
                  <span class="font-semibold text-slate-600 dark:text-gray-300">{{ $t(item.label) }}</span>
                </div>
                <p class="font-black text-slate-950 dark:text-gray-100">{{ numberFormat(item.count) }} <span class="font-semibold text-slate-500 dark:text-gray-400">({{ item.percent }}%)</span></p>
              </div>
              <div v-if="!paymentStatusItems.length" class="rounded-lg border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500 dark:border-gray-700 dark:text-gray-400">
                {{ $t('No payment data found for these filters.') }}
              </div>
            </div>
          </div>
        </article>
      </div>
    </section>
  </DashboardLayout>
</template>
