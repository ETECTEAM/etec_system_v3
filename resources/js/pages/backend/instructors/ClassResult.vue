<script setup>
import { computed, nextTick, onMounted, ref } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { ArrowLeft, Download } from "@lucide/vue";

import DashboardLayout from "../../../layouts/DashboardLayout.vue";
import etecLogoBase64 from "../../../assets/etecLogoBase64";

const props = defineProps({
  classData: {
    type: Object,
    required: true,
  },
  students: {
    type: Array,
    default: () => [],
  },
  autoDownload: {
    type: Boolean,
    default: false,
  },
});

const reportRef = ref(null);
const html2PdfLoading = ref(false);
const autoDownloaded = ref(false);

const khMonths = [
  "មករា", "កុម្ភៈ", "មីនា", "មេសា", "ឧសភា", "មិថុនា",
  "កក្កដា", "សីហា", "កញ្ញា", "តុលា", "វិច្ឆិកា", "ធ្នូ",
];

function toKhmerDigits(value) {
  const digits = ["០", "១", "២", "៣", "៤", "៥", "៦", "៧", "៨", "៩"];
  return String(value).split("").map((char) => digits[Number(char)] ?? char).join("");
}

function formatKhmerDate(date = new Date()) {
  const day = toKhmerDigits(date.getDate());
  const month = khMonths[date.getMonth()];
  const year = toKhmerDigits(date.getFullYear());

  return `ធ្វើនៅភ្នំពេញ, ថ្ងៃទី ${day} ខែ ${month} ឆ្នាំ ${year}`;
}

function formatNumber(value) {
  const numeric = Number(value ?? 0);
  return Number.isFinite(numeric) ? numeric.toFixed(numeric % 1 === 0 ? 0 : 2) : "0";
}

function totalScore(student) {
  return Number(student.scores?.attendance ?? 0)
    + Number(student.scores?.activity ?? 0)
    + Number(student.scores?.exam ?? 0);
}

const sortedStudents = computed(() => [...props.students].sort((a, b) => {
  const totalA = totalScore(a);
  const totalB = totalScore(b);
  const failA = totalA < 50 ? 1 : 0;
  const failB = totalB < 50 ? 1 : 0;

  if (failA !== failB) {
    return failA - failB;
  }

  return totalB - totalA;
}));

const summary = computed(() => {
  const total = sortedStudents.value.length;
  const passed = sortedStudents.value.filter((student) => totalScore(student) >= 50).length;
  const failed = total - passed;

  return {
    total,
    passed,
    failed,
  };
});

let html2CanvasPromise = null;
let jsPdfPromise = null;

function ensureHtml2Canvas() {
  if (typeof window !== "undefined" && window.html2canvas) {
    return Promise.resolve(window.html2canvas);
  }

  if (!html2CanvasPromise) {
    html2CanvasPromise = new Promise((resolve, reject) => {
      const script = document.createElement("script");
      script.src = "https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js";
      script.onload = () => resolve(window.html2canvas);
      script.onerror = () => reject(new Error("Failed to load html2canvas."));
      document.head.appendChild(script);
    });
  }

  return html2CanvasPromise;
}

function ensureJsPdf() {
  if (typeof window !== "undefined" && window.jspdf?.jsPDF) {
    return Promise.resolve(window.jspdf.jsPDF);
  }

  if (!jsPdfPromise) {
    jsPdfPromise = new Promise((resolve, reject) => {
      const script = document.createElement("script");
      script.src = "https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js";
      script.onload = () => resolve(window.jspdf?.jsPDF);
      script.onerror = () => reject(new Error("Failed to load jsPDF."));
      document.head.appendChild(script);
    });
  }

  return jsPdfPromise;
}

async function downloadPdf() {
  window.location.href = `/dashboard/instructor/classes/${props.classData.id}/result?download=1`;
}

onMounted(async () => {
  if (props.autoDownload && !autoDownloaded.value) {
    autoDownloaded.value = true;
    window.location.href = `/dashboard/instructor/classes/${props.classData.id}/result?download=1`;
  }
});
</script>

<template>
  <Head :title="`Class Result - ${classData.title}`" />

  <DashboardLayout v-if="!autoDownload">
    <section class="space-y-5">
      <div v-if="!autoDownload" class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
          <p class="text-xs font-black uppercase tracking-[0.18em] text-amber-500 dark:text-amber-300">
            Result Export
          </p>
          <h1 class="mt-1 text-2xl font-black text-slate-950 dark:text-gray-100 sm:text-3xl">
            Class result sheet
          </h1>
          <p class="mt-1 max-w-3xl text-sm font-medium text-slate-500 dark:text-gray-400">
            Download the class result as PDF after ending the class.
          </p>
        </div>

        <div class="flex flex-wrap gap-3">
          <Link
            :href="`/dashboard/instructor/classes/${classData.id}/attendance`"
            class="inline-flex h-10 items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 text-sm font-bold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800"
          >
            <ArrowLeft class="h-4 w-4" />
            Back to class
          </Link>
          <button
            type="button"
            :disabled="html2PdfLoading"
            class="inline-flex h-10 items-center gap-2 rounded-lg bg-blue-600 px-4 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-70"
            @click="downloadPdf"
          >
            <Download class="h-4 w-4" />
            {{ html2PdfLoading ? "Preparing PDF..." : "Download PDF" }}
          </button>
        </div>
      </div>

      <div v-if="!autoDownload" class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
          <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500 dark:text-gray-400">Students</p>
          <p class="mt-2 text-3xl font-black text-slate-950 dark:text-gray-100">{{ summary.total }}</p>
        </div>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 shadow-sm dark:border-emerald-500/20 dark:bg-emerald-500/10">
          <p class="text-xs font-bold uppercase tracking-[0.12em] text-emerald-700 dark:text-emerald-300">Passed</p>
          <p class="mt-2 text-3xl font-black text-emerald-700 dark:text-emerald-300">{{ summary.passed }}</p>
        </div>
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 shadow-sm dark:border-rose-500/20 dark:bg-rose-500/10">
          <p class="text-xs font-bold uppercase tracking-[0.12em] text-rose-700 dark:text-rose-300">Failed</p>
          <p class="mt-2 text-3xl font-black text-rose-700 dark:text-rose-300">{{ summary.failed }}</p>
        </div>
      </div>

      <div
        ref="reportRef"
        :class="[
          'pdf-sheet overflow-hidden rounded-2xl border border-slate-300 bg-white text-slate-900 shadow-sm',
          autoDownload ? 'fixed left-[-12000px] top-0 w-[297mm]' : '',
        ]"
      >
        <div class="border-b border-slate-200 px-6 py-5 dark:border-gray-800">
          <div class="flex items-start gap-4">
            <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl bg-slate-100 dark:bg-gray-800">
              <img :src="etecLogoBase64" alt="ETEC" class="h-14 w-14 object-contain" />
            </div>

            <div class="min-w-0 flex-1 text-center">
              <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-500 dark:text-gray-400">ETEC Center</p>
              <h2 class="mt-2 text-2xl font-black text-slate-950 dark:text-gray-100">
                លទ្ធផលនៃការប្រលងបញ្ចប់
              </h2>
              <p class="mt-2 text-sm font-semibold text-slate-600 dark:text-gray-300">
                វគ្គសិក្សា៖ <span class="font-black text-slate-950 dark:text-gray-100">{{ classData.course }}</span>
                · ម៉ោងសិក្សា៖ <span class="font-black text-slate-950 dark:text-gray-100">{{ classData.time }}</span>
              </p>
              <p class="mt-1 text-sm font-semibold text-slate-600 dark:text-gray-300">
                ថ្ងៃទី៖ <span class="font-black text-rose-600 dark:text-rose-400">{{ new Date().toLocaleDateString("en-GB") }}</span>
                · គ្រូបង្រៀន៖ <span class="font-black text-slate-950 dark:text-gray-100">{{ classData.teacher }}</span>
              </p>
            </div>

            <div class="w-20 shrink-0"></div>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-[1100px] w-full border-collapse text-sm">
            <thead>
              <tr class="bg-slate-100 text-center text-xs font-black uppercase tracking-[0.08em] text-slate-600 dark:bg-gray-950 dark:text-gray-300">
                <th rowspan="2" class="border border-slate-200 px-3 py-3 dark:border-gray-800">No</th>
                <th rowspan="2" class="border border-slate-200 px-3 py-3 text-left dark:border-gray-800">Full Name</th>
                <th rowspan="2" class="border border-slate-200 px-3 py-3 dark:border-gray-800">Gender</th>
                <th colspan="4" class="border border-slate-200 px-3 py-3 dark:border-gray-800">Score</th>
                <th rowspan="2" class="border border-slate-200 px-3 py-3 dark:border-gray-800">Result</th>
                <th rowspan="2" class="border border-slate-200 px-3 py-3 dark:border-gray-800">Other</th>
              </tr>
              <tr class="bg-slate-50 text-center text-xs font-black uppercase tracking-[0.08em] text-slate-500 dark:bg-gray-950 dark:text-gray-400">
                <th class="border border-slate-200 px-3 py-3 dark:border-gray-800">ATT</th>
                <th class="border border-slate-200 px-3 py-3 dark:border-gray-800">ACT</th>
                <th class="border border-slate-200 px-3 py-3 dark:border-gray-800">EXAM</th>
                <th class="border border-slate-200 px-3 py-3 dark:border-gray-800">Total</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="(student, index) in sortedStudents"
                :key="student.enrollment_id"
                :class="[
                  'align-middle',
                  totalScore(student) >= 50 ? '' : 'text-rose-700 dark:text-rose-300',
                ]"
              >
                <td class="border border-slate-200 px-3 py-3 text-center font-bold dark:border-gray-800">
                  {{ index + 1 }}
                </td>
                <td class="border border-slate-200 px-3 py-3 dark:border-gray-800">
                  <p class="font-black text-slate-950 dark:text-gray-100">{{ student.name }}</p>
                  <p class="mt-1 text-[11px] font-bold text-slate-500 dark:text-gray-400">ID: #{{ student.id }}</p>
                </td>
                <td class="border border-slate-200 px-3 py-3 text-center dark:border-gray-800">
                  <span
                    class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-black capitalize"
                    :class="student.gender === 'female'
                      ? 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300'
                      : 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-300'"
                  >
                    {{ student.gender || "-" }}
                  </span>
                </td>
                <td class="border border-slate-200 px-3 py-3 text-center dark:border-gray-800">
                  <span class="inline-flex min-w-12 justify-center rounded-lg bg-slate-100 px-2 py-2 font-black text-slate-700 dark:bg-gray-800 dark:text-gray-200">
                    {{ formatNumber(student.scores?.attendance) }}
                  </span>
                </td>
                <td class="border border-slate-200 px-3 py-3 text-center dark:border-gray-800">
                  <span class="inline-flex min-w-12 justify-center rounded-lg bg-slate-100 px-2 py-2 font-black text-slate-700 dark:bg-gray-800 dark:text-gray-200">
                    {{ formatNumber(student.scores?.activity) }}
                  </span>
                </td>
                <td class="border border-slate-200 px-3 py-3 text-center dark:border-gray-800">
                  <span class="inline-flex min-w-12 justify-center rounded-lg bg-slate-100 px-2 py-2 font-black text-slate-700 dark:bg-gray-800 dark:text-gray-200">
                    {{ formatNumber(student.scores?.exam) }}
                  </span>
                </td>
                <td class="border border-slate-200 px-3 py-3 text-center dark:border-gray-800">
                  <span class="inline-flex min-w-14 justify-center rounded-lg px-2 py-2 font-black" :class="totalScore(student) >= 50 ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300'">
                    {{ formatNumber(totalScore(student)) }}
                  </span>
                </td>
                <td class="border border-slate-200 px-3 py-3 text-center font-black dark:border-gray-800">
                  <span :class="totalScore(student) >= 50 ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300'">
                    {{ totalScore(student) >= 50 ? 'Pass' : 'Fail' }}
                  </span>
                </td>
                <td class="border border-slate-200 px-3 py-3 text-center text-xs font-semibold text-slate-500 dark:border-gray-800 dark:text-gray-400">
                  Attendance total: {{ student.attendance?.total ?? 0 }}
                </td>
              </tr>

              <tr v-if="!sortedStudents.length">
                <td colspan="9" class="border border-slate-200 px-3 py-12 text-center text-sm font-semibold text-slate-500 dark:border-gray-800 dark:text-gray-400">
                  No students found.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="border-t border-slate-200 px-6 py-4 dark:border-gray-800">
          <p class="text-sm font-semibold text-rose-700 dark:text-rose-300">
            ចំណាំ៖ លទ្ធផលនេះត្រូវបានបង្កើតដោយស្វ័យប្រវត្តិពីទិន្នន័យដែលបានរក្សាទុករួច។
          </p>
        </div>

        <div class="flex items-end justify-between gap-6 px-6 py-6">
          <div class="max-w-xs text-center">
            <p class="text-sm font-semibold text-slate-700 dark:text-gray-200">បានឃើញ និង ឯកភាព</p>
            <p class="text-sm font-semibold text-slate-500 dark:text-gray-400">នាយកមជ្ឈមណ្ឌល</p>
            <div class="mt-3 h-16 border-t border-dashed border-slate-300 dark:border-gray-700"></div>
          </div>

          <div class="text-right">
            <p class="text-sm font-semibold text-slate-700 dark:text-gray-200">
              {{ formatKhmerDate() }}
            </p>
            <div class="mt-3 h-16 border-t border-dashed border-slate-300 dark:border-gray-700"></div>
            <p class="mt-2 text-sm font-semibold text-slate-700 dark:text-gray-200">គ្រូបង្រៀន៖ {{ classData.teacher }}</p>
          </div>
        </div>
      </div>

      <p v-if="!autoDownload" class="text-xs font-semibold text-slate-400 dark:text-gray-500">
        PDF preview ready. Use the download button above to export the report.
      </p>
    </section>
  </DashboardLayout>

  <div v-else class="fixed inset-0 overflow-hidden bg-transparent pointer-events-none">
    <div
      ref="reportRef"
      class="pdf-sheet fixed left-[-12000px] top-0 w-[297mm] overflow-hidden rounded-2xl border border-slate-300 bg-white text-slate-900 shadow-sm"
    >
      <div class="border-b border-slate-200 px-6 py-5 dark:border-gray-800">
        <div class="flex items-start gap-4">
          <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl bg-slate-100 dark:bg-gray-800">
            <img :src="etecLogoBase64" alt="ETEC" class="h-14 w-14 object-contain" />
          </div>

          <div class="min-w-0 flex-1 text-center">
            <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-500 dark:text-gray-400">ETEC Center</p>
            <h2 class="mt-2 text-2xl font-black text-slate-950 dark:text-gray-100">
              លទ្ធផលនៃការប្រលងបញ្ចប់
            </h2>
            <p class="mt-2 text-sm font-semibold text-slate-600 dark:text-gray-300">
              វគ្គសិក្សា៖ <span class="font-black text-slate-950 dark:text-gray-100">{{ classData.course }}</span>
              · ម៉ោងសិក្សា៖ <span class="font-black text-slate-950 dark:text-gray-100">{{ classData.time }}</span>
            </p>
            <p class="mt-1 text-sm font-semibold text-slate-600 dark:text-gray-300">
              ថ្ងៃទី៖ <span class="font-black text-rose-600 dark:text-rose-400">{{ new Date().toLocaleDateString("en-GB") }}</span>
              · គ្រូបង្រៀន៖ <span class="font-black text-slate-950 dark:text-gray-100">{{ classData.teacher }}</span>
            </p>
          </div>

          <div class="w-20 shrink-0"></div>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-[1100px] w-full border-collapse text-sm">
          <thead>
            <tr class="bg-slate-100 text-center text-xs font-black uppercase tracking-[0.08em] text-slate-600 dark:bg-gray-950 dark:text-gray-300">
              <th rowspan="2" class="border border-slate-200 px-3 py-3 dark:border-gray-800">No</th>
              <th rowspan="2" class="border border-slate-200 px-3 py-3 text-left dark:border-gray-800">Full Name</th>
              <th rowspan="2" class="border border-slate-200 px-3 py-3 dark:border-gray-800">Gender</th>
              <th colspan="4" class="border border-slate-200 px-3 py-3 dark:border-gray-800">Score</th>
              <th rowspan="2" class="border border-slate-200 px-3 py-3 dark:border-gray-800">Result</th>
              <th rowspan="2" class="border border-slate-200 px-3 py-3 dark:border-gray-800">Other</th>
            </tr>
            <tr class="bg-slate-50 text-center text-xs font-black uppercase tracking-[0.08em] text-slate-500 dark:bg-gray-950 dark:text-gray-400">
              <th class="border border-slate-200 px-3 py-3 dark:border-gray-800">ATT</th>
              <th class="border border-slate-200 px-3 py-3 dark:border-gray-800">ACT</th>
              <th class="border border-slate-200 px-3 py-3 dark:border-gray-800">EXAM</th>
              <th class="border border-slate-200 px-3 py-3 dark:border-gray-800">Total</th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="(student, index) in sortedStudents"
              :key="student.enrollment_id"
              :class="[
                'align-middle',
                totalScore(student) >= 50 ? '' : 'text-rose-700 dark:text-rose-300',
              ]"
            >
              <td class="border border-slate-200 px-3 py-3 text-center font-bold dark:border-gray-800">
                {{ index + 1 }}
              </td>
              <td class="border border-slate-200 px-3 py-3 dark:border-gray-800">
                <p class="font-black text-slate-950 dark:text-gray-100">{{ student.name }}</p>
                <p class="mt-1 text-[11px] font-bold text-slate-500 dark:text-gray-400">ID: #{{ student.id }}</p>
              </td>
              <td class="border border-slate-200 px-3 py-3 text-center dark:border-gray-800">
                <span
                  class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-black capitalize"
                  :class="student.gender === 'female'
                    ? 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300'
                    : 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-300'"
                >
                  {{ student.gender || "-" }}
                </span>
              </td>
              <td class="border border-slate-200 px-3 py-3 text-center dark:border-gray-800">
                <span class="inline-flex min-w-12 justify-center rounded-lg bg-slate-100 px-2 py-2 font-black text-slate-700 dark:bg-gray-800 dark:text-gray-200">
                  {{ formatNumber(student.scores?.attendance) }}
                </span>
              </td>
              <td class="border border-slate-200 px-3 py-3 text-center dark:border-gray-800">
                <span class="inline-flex min-w-12 justify-center rounded-lg bg-slate-100 px-2 py-2 font-black text-slate-700 dark:bg-gray-800 dark:text-gray-200">
                  {{ formatNumber(student.scores?.activity) }}
                </span>
              </td>
              <td class="border border-slate-200 px-3 py-3 text-center dark:border-gray-800">
                <span class="inline-flex min-w-12 justify-center rounded-lg bg-slate-100 px-2 py-2 font-black text-slate-700 dark:bg-gray-800 dark:text-gray-200">
                  {{ formatNumber(student.scores?.exam) }}
                </span>
              </td>
              <td class="border border-slate-200 px-3 py-3 text-center dark:border-gray-800">
                <span class="inline-flex min-w-14 justify-center rounded-lg px-2 py-2 font-black" :class="totalScore(student) >= 50 ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300'">
                  {{ formatNumber(totalScore(student)) }}
                </span>
              </td>
              <td class="border border-slate-200 px-3 py-3 text-center font-black dark:border-gray-800">
                <span :class="totalScore(student) >= 50 ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300'">
                  {{ totalScore(student) >= 50 ? 'Pass' : 'Fail' }}
                </span>
              </td>
              <td class="border border-slate-200 px-3 py-3 text-center text-xs font-semibold text-slate-500 dark:border-gray-800 dark:text-gray-400">
                Attendance total: {{ student.attendance?.total ?? 0 }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<style scoped>
.pdf-sheet {
  background: #ffffff !important;
  color: #111827 !important;
}

.pdf-sheet :deep(.dark\:bg-gray-900),
.pdf-sheet :deep(.dark\:bg-gray-800),
.pdf-sheet :deep(.dark\:bg-gray-950) {
  background-color: #ffffff !important;
}

.pdf-sheet :deep(.dark\:text-gray-100),
.pdf-sheet :deep(.dark\:text-gray-200),
.pdf-sheet :deep(.dark\:text-gray-300),
.pdf-sheet :deep(.dark\:text-gray-400),
.pdf-sheet :deep(.dark\:text-gray-500) {
  color: #111827 !important;
}

.pdf-sheet :deep(.dark\:border-gray-800),
.pdf-sheet :deep(.dark\:border-gray-700) {
  border-color: #d1d5db !important;
}

.pdf-sheet :deep(table),
.pdf-sheet :deep(th),
.pdf-sheet :deep(td) {
  border-color: #111827 !important;
}

.pdf-sheet :deep(.text-slate-500),
.pdf-sheet :deep(.text-slate-600),
.pdf-sheet :deep(.text-slate-700),
.pdf-sheet :deep(.text-gray-500),
.pdf-sheet :deep(.text-gray-400) {
  color: #4b5563 !important;
}
</style>
