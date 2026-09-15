<script setup>
import { computed, ref } from "vue";
import { Head, router } from "@inertiajs/vue3";
import { ChevronDown, GraduationCap, Info, Mail, LogOut, MapPin, Phone, UserRound } from "@lucide/vue";

const props = defineProps({
  student: { type: Object, required: true },
  enrollments: { type: Array, default: () => [] },
});

const expanded = ref(Object.fromEntries(props.enrollments.map((enrollment) => [enrollment.id, true])));
const pages = ref({});
const recordsPerPage = 5;
const initials = computed(() => (props.student?.name || "Student")
  .split(/\s+/).filter(Boolean).slice(0, 2).map((name) => name[0]).join("").toUpperCase());

function isPending(enrollment) {
  return String(enrollment.status || "").toLowerCase() === "pending";
}

function enrollmentLabel(status) {
  return ({ active: "Active", pending: "Pending Approval", completed: "Completed", rejected: "Rejected" })[String(status || "").toLowerCase()] || String(status || "Pending");
}

function enrollmentTone(status) {
  return ({ active: "active", pending: "pending", completed: "completed", rejected: "rejected" })[String(status || "").toLowerCase()] || "pending";
}

function attendanceTone(status) {
  const value = String(status || "").toLowerCase();
  return ["present", "absent", "late", "permission", "pending"].includes(value) ? value : "pending";
}

function toggle(id) {
  expanded.value = { ...expanded.value, [id]: !expanded.value[id] };
}

function attendanceRecords(enrollment) {
  return Array.isArray(enrollment.attendance) ? enrollment.attendance : [];
}

function attendanceCount(enrollment, status) {
  return attendanceRecords(enrollment).filter((record) => attendanceTone(record.status) === status).length;
}

function currentPage(enrollment) {
  return pages.value[enrollment.id] || 1;
}

function totalPages(enrollment) {
  return Math.max(1, Math.ceil(attendanceRecords(enrollment).length / recordsPerPage));
}

function visibleRecords(enrollment) {
  const page = currentPage(enrollment);
  const start = (page - 1) * recordsPerPage;
  return attendanceRecords(enrollment).slice(start, start + recordsPerPage);
}

function setPage(enrollment, page) {
  pages.value = { ...pages.value, [enrollment.id]: Math.min(Math.max(page, 1), totalPages(enrollment)) };
}

function logout() {
  router.post("/student-portal/logout");
}
</script>

<template>
  <Head title="My Attendance" />

  <main class="min-h-screen bg-[#f7faff] text-slate-900">
    <header class="border-b border-slate-200 bg-white">
      <div class="mx-auto flex h-14 max-w-6xl items-center justify-between px-4 sm:px-6">
        <div class="flex items-center gap-2.5 font-bold text-slate-900">
          <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-white"><GraduationCap :size="21" /></span>
          <span>Student Portal</span>
        </div>
      </div>
    </header>

    <div class="mx-auto max-w-6xl px-4 py-5 sm:px-6 sm:py-8">
      <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
          <div class="flex min-w-0 items-center gap-4">
            <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-full bg-blue-100 text-2xl font-semibold text-blue-700">{{ initials }}</div>
            <div class="min-w-0">
              <h1 class="truncate text-2xl font-bold tracking-tight">{{ student.name }}</h1>
              <p class="mt-1 inline-flex items-center gap-2 text-sm text-slate-600"><Mail :size="16" />{{ student.email }}</p> <br>
              <p class="mt-2 inline-flex items-center gap-2 text-sm text-slate-600"><Phone :size="16" />{{ student.phone || "Phone not available" }}</p>
            </div>
          </div>
        </div>
      </section>

      <section class="mt-7">
        <h2 class="text-3xl font-bold tracking-tight">My Attendance</h2>
        <p class="mt-1 text-slate-600">Here are your enrolled classes and attendance records.</p>

        <div v-if="!enrollments.length" class="mt-5 rounded-xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center text-slate-600">You haven't joined any classes yet.</div>

        <div v-else class="mt-5 space-y-4">
          <article v-for="enrollment in enrollments" :key="enrollment.id" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <button type="button" class="flex w-full items-start justify-between gap-4 p-5 text-left transition hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-600" :aria-expanded="Boolean(expanded[enrollment.id])" @click="toggle(enrollment.id)">
              <div class="min-w-0">
                <h3 class="text-lg font-bold">{{ enrollment.class_title }}</h3>
                <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-slate-600">
                  <span class="inline-flex items-center gap-1.5"><UserRound :size="16" />{{ enrollment.instructor || "Instructor to be announced" }}</span>
                  <span class="hidden h-4 border-l border-slate-300 sm:block" />
                  <span>{{ enrollment.schedule }}</span>
                  <span class="hidden h-4 border-l border-slate-300 sm:block" />
                  <span class="inline-flex items-center gap-1.5"><MapPin :size="16" />{{ enrollment.room === "-" ? "Room to be announced" : enrollment.room }}</span>
                </div>
              </div>
              <div class="flex shrink-0 items-center gap-3">
                <span class="status-badge" :class="`enrollment-${enrollmentTone(enrollment.status)}`">{{ enrollmentLabel(enrollment.status) }}</span>
                <ChevronDown :size="20" class="text-slate-500 transition-transform" :class="{ 'rotate-180': expanded[enrollment.id] }" />
              </div>
            </button>

            <div v-show="expanded[enrollment.id]" class="border-t border-slate-200 px-5 py-4">
              <dl class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-5">
                <div class="rounded-lg bg-slate-100 px-3 py-2.5"><dt class="text-xs font-medium text-slate-600">Total</dt><dd class="mt-1 text-lg font-bold">{{ attendanceRecords(enrollment).length }}</dd></div>
                <div class="rounded-lg bg-green-50 px-3 py-2.5"><dt class="text-xs font-medium text-green-700">Present</dt><dd class="mt-1 text-lg font-bold text-green-700">{{ attendanceCount(enrollment, "present") }}</dd></div>
                <div class="rounded-lg bg-red-50 px-3 py-2.5"><dt class="text-xs font-medium text-red-700">Absent</dt><dd class="mt-1 text-lg font-bold text-red-700">{{ attendanceCount(enrollment, "absent") }}</dd></div>
                <div class="rounded-lg bg-blue-50 px-3 py-2.5"><dt class="text-xs font-medium text-blue-700">Permission</dt><dd class="mt-1 text-lg font-bold text-blue-700">{{ attendanceCount(enrollment, "permission") }}</dd></div>
                <div class="rounded-lg bg-orange-50 px-3 py-2.5"><dt class="text-xs font-medium text-orange-700">Late</dt><dd class="mt-1 text-lg font-bold text-orange-700">{{ attendanceCount(enrollment, "late") }}</dd></div>
              </dl>
              <p v-if="isPending(enrollment)" class="rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800">Your enrollment is waiting for instructor approval. Attendance records will become available after approval.</p>
              <p v-else-if="!enrollment.attendance?.length" class="py-3 text-sm text-slate-500">No attendance records yet.</p>
              <div v-else class="overflow-x-auto rounded-lg border border-slate-200">
                <table class="min-w-[560px] w-full text-left text-sm">
                  <thead class="bg-slate-100 text-xs font-semibold uppercase tracking-wide text-slate-600">
                    <tr><th class="w-16 px-4 py-3">#</th><th class="px-4 py-3">Date</th><th class="px-4 py-3">Time</th><th class="px-4 py-3">Status</th></tr>
                  </thead>
                  <tbody class="divide-y divide-slate-200">
                    <tr v-for="(record, index) in visibleRecords(enrollment)" :key="`${enrollment.id}-${record.date}-${index}`">
                      <td class="px-4 py-3 text-slate-600">{{ (currentPage(enrollment) - 1) * recordsPerPage + index + 1 }}</td>
                      <td class="px-4 py-3 font-medium">{{ record.date || "-" }}</td>
                      <td class="px-4 py-3 text-slate-600">{{ record.time || "-" }}</td>
                      <td class="px-4 py-3"><span class="status-badge" :class="`attendance-${attendanceTone(record.status)}`">{{ record.status }}</span></td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <nav v-if="totalPages(enrollment) > 1" class="mt-4 flex items-center justify-between gap-3" :aria-label="`Attendance pages for ${enrollment.class_title}`">
                <button type="button" class="min-h-10 rounded-lg border border-slate-300 px-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40" :disabled="currentPage(enrollment) === 1" @click="setPage(enrollment, currentPage(enrollment) - 1)">Previous</button>
                <span class="text-sm text-slate-600">Page {{ currentPage(enrollment) }} of {{ totalPages(enrollment) }}</span>
                <button type="button" class="min-h-10 rounded-lg border border-slate-300 px-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40" :disabled="currentPage(enrollment) === totalPages(enrollment)" @click="setPage(enrollment, currentPage(enrollment) + 1)">Next</button>
              </nav>
            </div>
          </article>
        </div>
      </section>

      <footer class="mt-7 flex items-start gap-3 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700">
        <Info class="mt-0.5 shrink-0" :size="22" />
        <p>If you see an incorrect attendance record, please contact your instructor or the registrar.</p>
      </footer>
    </div>
  </main>
</template>

<style scoped>
.status-badge { display: inline-flex; align-items: center; border-radius: 9999px; padding: .3rem .75rem; font-size: .75rem; font-weight: 700; white-space: nowrap; }
.enrollment-active, .attendance-present { background: #dcfce7; color: #15803d; }
.enrollment-pending, .attendance-pending { background: #fef3c7; color: #a16207; }
.enrollment-completed { background: #e5e7eb; color: #4b5563; }
.enrollment-rejected, .attendance-absent { background: #fee2e2; color: #dc2626; }
.attendance-late { background: #ffedd5; color: #c2410c; }
.attendance-permission { background: #dbeafe; color: #1d4ed8; }
</style>
