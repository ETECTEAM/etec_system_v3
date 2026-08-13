<script setup>
import { computed, ref } from "vue";
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import {
  BookOpen,
  Building2,
  CalendarDays,
  Clock3,
  DoorOpen,
  Eye,
  GraduationCap,
  Mars,
  MoreVertical,
  Presentation,
  RefreshCw,
  Search,
  Users,
  Venus,
} from "@lucide/vue";

import DashboardLayout from "../../layouts/DashboardLayout.vue";

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
});

const page = usePage();
const search = ref("");
const openMenu = ref(null);

const instructorName = computed(() => page.props.auth?.user?.name ?? "Instructor");
const instructorId = computed(() => page.props.auth?.user?.id ?? "-");

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
    label: "Total Class",
    value: props.summary.total_classes ?? 0,
    icon: Presentation,
    color: "text-blue-600",
    bg: "bg-blue-50 dark:bg-blue-500/10",
  },
  {
    label: "Total Student",
    value: props.summary.total_students ?? 0,
    icon: Users,
    color: "text-emerald-600",
    bg: "bg-emerald-50 dark:bg-emerald-500/10",
  },
  {
    label: "Male Student",
    value: props.summary.male_students ?? 0,
    icon: Mars,
    color: "text-cyan-500",
    bg: "bg-cyan-50 dark:bg-cyan-500/10",
  },
  {
    label: "Female Student",
    value: props.summary.female_students ?? 0,
    icon: Venus,
    color: "text-rose-500",
    bg: "bg-rose-50 dark:bg-rose-500/10",
  },
]);

function refresh() {
  router.reload({ only: ["classes", "summary"], preserveScroll: true });
}

function viewClass(classData) {
  router.get(`/dashboard/instructor/classes/${classData.id}/attendance`);
}

function attendance(classData) {
  router.get(`/dashboard/instructor/classes/${classData.id}/attendance`);
}
</script>

<template>
  <Head title="Instructor Dashboard" />

  <DashboardLayout>
    <section class="space-y-5 sm:space-y-6">
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
          Complete Profile
        </Link>
      </div>

      <template v-else>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700 dark:text-blue-400">
              Attendance System
            </p>
            <h1 class="mt-1 text-xl font-black text-slate-950 dark:text-gray-100 sm:text-3xl">
              Manage your classes
            </h1>
            <p class="mt-1 text-xs font-medium text-slate-500 dark:text-gray-400 sm:text-sm">
              Name: {{ instructorName }} · ID: {{ instructorId }}
            </p>
          </div>
          <Link
            href="/dashboard/instructor/classes/create"
            class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-blue-900 px-4 text-sm font-semibold text-white transition hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-500"
          >
            <GraduationCap class="h-4 w-4" />
            Add Class
          </Link>
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
                  placeholder="Search Class..."
                  class="h-10 w-full rounded-lg border border-slate-300 bg-white px-3 pr-10 text-sm outline-none transition focus:border-blue-700 focus:ring-4 focus:ring-blue-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:focus:ring-blue-500/10"
                />
              </label>
              <button
                type="button"
                class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-slate-700 px-4 text-sm font-semibold text-white transition hover:bg-slate-800 dark:bg-gray-700 dark:hover:bg-gray-600"
                @click="refresh"
              >
                <RefreshCw class="h-4 w-4" />
                Refresh
              </button>
            </div>
          </div>
        </div>

        <div v-if="filteredClasses.length" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <article
            v-for="classData in filteredClasses"
            :key="classData.id"
            class="relative rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md dark:border-gray-800 dark:bg-gray-900 dark:hover:border-blue-500/40 sm:p-5"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="flex min-w-0 items-center gap-3">
                <div class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-blue-50 text-blue-900 dark:bg-blue-500/10 dark:text-blue-400">
                  <BookOpen class="h-5 w-5" />
                </div>
                <div class="min-w-0">
                  <h2 class="truncate text-base font-black text-slate-950 dark:text-gray-100 sm:text-lg">
                    {{ classData.title }}
                  </h2>
                  <p class="mt-1 text-xs font-semibold text-slate-500 dark:text-gray-400">
                    Class id: <span class="rounded-md bg-blue-900 px-2 py-0.5 text-white">#{{ classData.id }}</span>
                  </p>
                </div>
              </div>

              <button
                type="button"
                class="rounded-lg p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 dark:hover:bg-gray-800 dark:hover:text-gray-100"
                @click="openMenu = openMenu === classData.id ? null : classData.id"
              >
                <MoreVertical class="h-5 w-5" />
              </button>

              <div
                v-if="openMenu === classData.id"
                class="absolute right-4 top-14 z-10 w-48 overflow-hidden rounded-lg border border-slate-200 bg-white py-1 shadow-lg dark:border-gray-700 dark:bg-gray-900"
              >
                <button type="button" class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm font-semibold hover:bg-slate-50 dark:hover:bg-gray-800" @click="viewClass(classData)">
                  <Eye class="h-4 w-4" /> View Student
                </button>
                <button type="button" class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm font-semibold hover:bg-slate-50 dark:hover:bg-gray-800" @click="attendance(classData)">
                  <Presentation class="h-4 w-4" /> Attendance
                </button>
              </div>
            </div>

            <dl class="mt-4 divide-y divide-slate-200 text-sm dark:divide-gray-800">
              <div class="grid grid-cols-5 gap-3 py-2">
                <dt class="col-span-2 flex items-center gap-2 font-black"><BookOpen class="h-4 w-4 text-slate-400" /> Class Lessons:</dt>
                <dd class="col-span-3 truncate font-semibold text-slate-700 dark:text-gray-300">{{ classData.lesson }}</dd>
              </div>
              <div class="grid grid-cols-5 gap-3 py-2">
                <dt class="col-span-2 flex items-center gap-2 font-black"><Building2 class="h-4 w-4 text-slate-400" /> Building:</dt>
                <dd class="col-span-3 truncate font-semibold text-slate-700 dark:text-gray-300">{{ classData.building }}</dd>
              </div>
              <div class="grid grid-cols-5 gap-3 py-2">
                <dt class="col-span-2 flex items-center gap-2 font-black"><DoorOpen class="h-4 w-4 text-slate-400" /> Floor & Room:</dt>
                <dd class="col-span-3 truncate font-semibold text-blue-900 dark:text-blue-400">{{ classData.floor }} - Room: {{ classData.room }}</dd>
              </div>
              <div class="grid grid-cols-5 gap-3 py-2">
                <dt class="col-span-2 font-black">Status:</dt>
                <dd class="col-span-3 font-semibold text-blue-900 dark:text-blue-400">{{ classData.status }}</dd>
              </div>
              <div class="grid grid-cols-5 gap-3 py-2">
                <dt class="col-span-2 flex items-center gap-2 font-black"><CalendarDays class="h-4 w-4 text-slate-400" /> Term:</dt>
                <dd class="col-span-3 font-semibold text-slate-700 dark:text-gray-300">{{ classData.term }}</dd>
              </div>
              <div class="grid grid-cols-5 gap-3 py-2">
                <dt class="col-span-2 flex items-center gap-2 font-black"><Clock3 class="h-4 w-4 text-slate-400" /> Time:</dt>
                <dd class="col-span-3 font-black text-emerald-600 dark:text-emerald-400">{{ classData.time }}</dd>
              </div>
              <div class="grid grid-cols-5 gap-3 py-2">
                <dt class="col-span-2 flex items-center gap-2 font-black"><Users class="h-4 w-4 text-slate-400" /> Total Stu:</dt>
                <dd class="col-span-3 font-semibold text-slate-700 dark:text-gray-300">{{ classData.students }}</dd>
              </div>
            </dl>

            <button
              type="button"
              class="mt-4 flex h-10 w-full items-center justify-center rounded-lg border border-slate-300 bg-slate-50 text-sm font-semibold text-blue-900 transition hover:border-blue-300 hover:bg-blue-50 dark:border-gray-700 dark:bg-gray-950 dark:text-blue-400 dark:hover:border-blue-500/50"
              @click="viewClass(classData)"
            >
              View Class
            </button>
          </article>
        </div>

        <div v-else class="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center dark:border-gray-700 dark:bg-gray-900">
          <BookOpen class="mx-auto h-10 w-10 text-slate-300 dark:text-gray-600" />
          <h2 class="mt-3 text-base font-bold text-slate-900 dark:text-gray-100">No classes found</h2>
          <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">Classes assigned to your instructor ID will show here.</p>
        </div>
      </template>
    </section>
  </DashboardLayout>
</template>
