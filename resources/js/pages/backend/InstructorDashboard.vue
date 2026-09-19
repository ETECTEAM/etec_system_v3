<script setup>
import { computed, ref } from "vue";
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import axios from "axios";
import {
  Award,
  BookOpen,
  GraduationCap,
  Mars,
  Presentation,
  RefreshCw,
  Search,
  TriangleAlert,
  Users,
  Venus,
} from "@lucide/vue";

import DashboardLayout from "../../layouts/DashboardLayout.vue";
import ClassCrad from "../../components/ui/card/ClassCrad.vue";
import { useI18n } from "../../i18n";
import { useToast } from "@/composables/useToast";

const { t } = useI18n();
const toast = useToast();

const props = defineProps({
  instructorData: {
    type: Object,
    default: null,
  },
  classes: {
    type: Array,
    default: () => [],
  },
  summary: {
    type: Object,
    default: () => ({
      total_classes: 0,
      total_students: 0,
      male_students: 0,
      female_students: 0,
    }),
  },
  attendanceBlock: {
    type: Object,
    default: null,
  },
});

const page = usePage();
const search = ref("");
const requestingUnblock = ref(false);
const csvInput = ref(null);
const csvClass = ref(null);
const csvImporting = ref(false);

function requestAttendanceUnblock() {
  requestingUnblock.value = true;

  router.post("/dashboard/instructor/attendance-block/request", {}, {
    preserveScroll: true,
    onFinish: () => {
      requestingUnblock.value = false;
    },
  });
}

const instructorDisplayName = (name, fallback = t("Instructor")) => {
  const displayName = String(name ?? "").split(/[·•]/u)[0].trim();

  return displayName || fallback;
};

const instructorName = computed(() => instructorDisplayName(page.props.auth?.user?.name));
const instructorId = computed(() => page.props.auth?.user?.id ?? "-");

// Adding a class needs the create-classes permission: "Classes -> Create" on Role & Permission
// (every instructor), or granted to this one instructor on User & Permission. The server checks the same.
const canCreateClasses = computed(() => (page.props.auth?.permissions ?? []).includes("create-classes"));

const filteredClasses = computed(() => {
  const query = search.value.trim().toLowerCase();

  if (!query) return props.classes;

  return props.classes.filter((classData) => {
    return [
      classData.title,
      classData.course,
      classData.lesson,
      classData.building,
      classData.room,
      String(classData.id),
    ].some((value) => String(value ?? "").toLowerCase().includes(query));
  });
});

const stats = computed(() => [
  {
    label: t("Total Class"),
    value: props.summary.total_classes ?? 0,
    icon: Presentation,
    color: "text-blue-600",
    bg: "bg-blue-50 dark:bg-blue-500/10",
  },
  {
    label: t("Total Student"),
    value: props.summary.total_students ?? 0,
    icon: Users,
    color: "text-emerald-600",
    bg: "bg-emerald-50 dark:bg-emerald-500/10",
  },
  {
    label: t("Male Student"),
    value: props.summary.male_students ?? 0,
    icon: Mars,
    color: "text-cyan-500",
    bg: "bg-cyan-50 dark:bg-cyan-500/10",
  },
  {
    label: t("Female Student"),
    value: props.summary.female_students ?? 0,
    icon: Venus,
    color: "text-rose-500",
    bg: "bg-rose-50 dark:bg-rose-500/10",
  },
]);

function refresh() {
  router.reload({ only: ["classes", "summary"], preserveScroll: true });
}

function importLegacyCsv(classData) {
  csvClass.value = classData;
  csvInput.value?.click();
}

async function onCsvSelected(event) {
  const file = event.target.files?.[0];
  event.target.value = "";
  if (!file || !csvClass.value) return;
  csvImporting.value = true;
  const data = new FormData();
  data.append("file", file);
  try {
    const response = await axios.post(`/dashboard/instructor/classes/${csvClass.value.id}/attendance/import-csv`, data);
    toast.success(`Imported ${response.data.summary.attendance_imported} attendance rows.`);
    refresh();
  } catch (error) {
    toast.error(error.response?.data?.message ?? "CSV import failed.");
  } finally {
    csvImporting.value = false;
    csvClass.value = null;
  }
}

// The class card is shared with the admin class list, which sends "View Class" to the
// super_admin-only enrollment page; instructors go to their own class screen instead.
function viewUrl(classData) {
  return `/dashboard/instructor/classes/${classData.id}/attendance`;
}

// A class shared with this instructor ("Collapse Class") is taught by them but owned by
// someone else, so the owner-only actions are dropped rather than left to 403.
// "Register Student" is intentionally NOT here: enrollment is class-level, so either
// instructor on a collapsed class may add students (EnrollmentClassController's
// ensureInstructorCanManageClassStudents() allows the co-instructor too).
const OWNER_ONLY_ACTIONS = ["Edit Class", "Collapse Class", "Pre-End", "End"];

function hiddenItems(classData) {
  const instructorHiddenItems = ["Copy Class", "Switch Teacher"];

  return classData.is_owner ? instructorHiddenItems : [...instructorHiddenItems, ...OWNER_ONLY_ACTIONS];
}

// Appended to the shared action menu: attendance tracking is instructor-only, so it
// has no place on the admin class list the menu was built for.
function attendanceItem(classData) {
  if (String(classData.class_status ?? "").toLowerCase() !== "active") {
    return [];
  }

  return [
    {
      label: t("Attendance"),
      icon: Presentation,
      action: () => router.get(`/dashboard/instructor/classes/${classData.id}/attendance/track`),
    },
  ];
}

function openCertificateRequest(classData) {
  router.get(`/dashboard/instructor/classes/${classData.id}/certificate-request`);
}

function certificateItem(classData) {
  const requestedTypes = classData.certificate_request_types ?? [];
  const hasRequest = requestedTypes.length > 0;

  return [
    {
      label: hasRequest ? t("Certificate Requested") : t("Request Certificate"),
      icon: Award,
      disabled: hasRequest,
      action: () => openCertificateRequest(classData),
    },
  ];
}

function actionItems(classData) {
  return [
    {
      label: csvImporting.value && csvClass.value?.id === classData.id ? "Importing CSV..." : "Import Legacy CSV",
      icon: RefreshCw,
      disabled: csvImporting.value,
      action: () => importLegacyCsv(classData),
    },
    ...attendanceItem(classData),
    ...certificateItem(classData),
  ];
}
</script>

<template>
  <Head :title="$t('Instructor Dashboard')" />

  <DashboardLayout>
    <input ref="csvInput" type="file" accept=".csv,text/csv" class="hidden" @change="onCsvSelected" />
    <section class="space-y-5 sm:space-y-6">
      <div
        v-if="attendanceBlock"
        class="flex flex-col gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-300 sm:flex-row sm:items-center sm:justify-between"
      >
        <div class="flex items-start gap-2">
          <TriangleAlert class="mt-0.5 h-5 w-5 shrink-0" />
          <div>
            <p class="font-semibold">{{ $t("Your account is blocked from tracking attendance.") }}</p>
            <p class="mt-0.5 text-red-700 dark:text-red-400">{{ attendanceBlock.reason }}</p>
          </div>
        </div>
        <button
          v-if="!attendanceBlock.pending_review"
          type="button"
          :disabled="requestingUnblock"
          class="inline-flex h-9 shrink-0 items-center justify-center rounded-lg bg-red-700 px-4 text-sm font-semibold text-white transition hover:bg-red-800 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-red-600 dark:hover:bg-red-500"
          @click="requestAttendanceUnblock"
        >
          {{ $t("Request to track again") }}
        </button>
        <span v-else class="shrink-0 rounded-lg bg-red-100 px-4 py-2 text-sm font-semibold text-red-800 dark:bg-red-500/20 dark:text-red-300">
          {{ $t("Request pending admin review") }}
        </span>
      </div>

      <div
        v-if="!instructorData"
        class="rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:p-10"
      >
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 dark:bg-gray-800 sm:h-16 sm:w-16">
          <Users class="h-7 w-7 text-slate-400 dark:text-gray-500 sm:h-8 sm:w-8" />
        </div>
        <h2 class="text-base font-semibold text-slate-900 dark:text-gray-100 sm:text-lg">
          {{ $t("Instructor profile not completed yet.") }}
        </h2>
        <p class="mt-1 text-xs text-slate-500 dark:text-gray-400 sm:text-sm">
          {{ $t("Please complete your profile to view your dashboard.") }}
        </p>
        <Link
          href="/dashboard/instructor/profile"
          class="mt-5 inline-flex h-10 items-center gap-2 rounded-lg bg-blue-900 px-5 text-sm font-semibold text-white transition hover:bg-blue-800"
        >
          {{ $t("Complete Profile") }}
        </Link>
      </div>

      <template v-else>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700 dark:text-blue-400">
              {{ $t("Attendance System") }}
            </p>
            <h1 class="mt-1 text-xl font-black text-slate-950 dark:text-gray-100 sm:text-3xl">
              {{ $t("Manage your classes") }}
            </h1>
            <p class="mt-1 text-xs font-medium text-slate-500 dark:text-gray-400 sm:text-sm">
              {{ $t('Name') }}: {{ instructorName }} · {{ $t('ID') }}: {{ instructorId }}
            </p>
          </div>
          <Link
            v-if="canCreateClasses"
            href="/dashboard/instructor/classes/create"
            class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-blue-900 px-4 text-sm font-semibold text-white transition hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-500"
          >
            <GraduationCap class="h-4 w-4" />
            {{ $t("Add Class") }}
          </Link>
          <span
            v-else
            class="inline-flex h-10 cursor-not-allowed items-center justify-center gap-2 rounded-lg bg-slate-200 px-4 text-sm font-semibold text-slate-500 dark:bg-gray-800 dark:text-gray-500"
            :title="$t('An admin needs to approve your account before you can create a class.')"
          >
            <GraduationCap class="h-4 w-4" />
            {{ $t("Add Class") }}
          </span>
        </div>

        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
          <div
            v-for="stat in stats"
            :key="stat.label"
            class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:p-5"
          >
            <div class="flex items-center justify-between gap-3">
              <div>
                <p class="text-[11px] font-bold text-slate-500 dark:text-gray-400 sm:text-sm">{{ stat.label }}</p>
                <p class="mt-1 text-2xl font-black tabular-nums text-slate-950 dark:text-gray-100 sm:text-3xl">
                  {{ stat.value }}
                </p>
              </div>
              <div :class="['grid h-10 w-10 shrink-0 place-items-center rounded-xl sm:h-12 sm:w-12', stat.bg]">
                <component :is="stat.icon" :class="['h-5 w-5 sm:h-7 sm:w-7', stat.color]" />
              </div>
            </div>
          </div>
        </div>

        <div class="border-t border-slate-200 pt-4 dark:border-gray-800">
          <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div class="flex flex-col gap-2 sm:flex-row">
              <label class="relative block w-full sm:w-80">
                <Search class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
                <input
                  v-model="search"
                  type="search"
                  :placeholder="$t('Search Class...')"
                  class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 pr-10 text-sm outline-none transition focus:border-blue-700 focus:ring-4 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:focus:ring-blue-500/10"
                />
              </label>
              <button
                type="button"
                class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-slate-700 px-4 text-sm font-semibold text-white transition hover:bg-slate-800 dark:bg-gray-700 dark:hover:bg-gray-600"
                @click="refresh"
              >
                <RefreshCw class="h-4 w-4" />
                {{ $t("Refresh") }}
              </button>
            </div>
          </div>
        </div>

        <div v-if="filteredClasses.length" class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
          <div
            v-for="classData in filteredClasses"
            :key="classData.id"
            class="relative"
            :class="{ 'rounded-2xl ring-2 ring-red-400 dark:ring-red-500/60': classData.id === attendanceBlock?.triggered_study_class_id }"
          >
            <span
              v-if="classData.id === attendanceBlock?.triggered_study_class_id"
              class="absolute -top-2 left-3 z-10 inline-flex items-center gap-1 rounded-full bg-red-600 px-2.5 py-0.5 text-[11px] font-semibold text-white shadow"
            >
              <TriangleAlert class="h-3 w-3" />
              {{ $t("Caused the block") }}
            </span>
            <ClassCrad
              :classData="classData"
              :viewUrl="viewUrl(classData)"
              :extraItems="actionItems(classData)"
              :showInstructor="false"
              :instructorSummary="true"
              :hiddenItems="hiddenItems(classData)"
            />
          </div>
        </div>

        <div v-else class="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center dark:border-gray-700 dark:bg-gray-900">
          <BookOpen class="mx-auto h-10 w-10 text-slate-300 dark:text-gray-600" />
          <h2 class="mt-3 text-base font-bold text-slate-900 dark:text-gray-100">{{ $t("No classes found") }}</h2>
          <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">{{ $t("Classes assigned to your instructor ID will show here.") }}</p>
        </div>
      </template>
    </section>
  </DashboardLayout>
</template>
