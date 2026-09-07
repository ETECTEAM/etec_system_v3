<script setup>
import { computed, ref } from "vue";
import { UserPlus, Users, Clock3, CalendarDays, DoorOpen, BookOpen, Clock, Search } from "@lucide/vue";
import RegisterStudentModal from "./RegisterStudentModal.vue";
import EmptyState from "../../../../components/ui/empty-state/EmptyState.vue";
import SelectSearch from "@/components/ui/select-search/SelectSearch.vue";
import { useI18n } from "@/i18n";

const { t } = useI18n();

const props = defineProps({
  // Classes that just started, presented via GetClassList::presentClass.
  classes: {
    type: Array,
    default: () => [],
  },
});

// The class targeted by "Register New Student".
const registerClass = ref(null);

const search = ref("");
const courseFilter = ref("");
const instructorFilter = ref("");
const statusFilter = ref("");

const courseOptions = computed(() => toOptions(props.classes.map((c) => c.course)));
const instructorOptions = computed(() => toOptions(props.classes.map((c) => c.teacher)));
const statusOptions = computed(() => toOptions(props.classes.map((c) => c.class_status_label)));

function toOptions(values) {
  return [...new Set(values.filter(Boolean))]
    .sort((a, b) => String(a).localeCompare(String(b)))
    .map((value) => ({ value, label: value }));
}

const filteredClasses = computed(() => {
  const q = search.value.trim().toLowerCase();

  return props.classes.filter((item) => {
    if (courseFilter.value && item.course !== courseFilter.value) return false;
    if (instructorFilter.value && item.teacher !== instructorFilter.value) return false;
    if (statusFilter.value && item.class_status_label !== statusFilter.value) return false;

    if (!q) return true;

    return [item.title, item.course, item.teacher, item.room, item.floor]
      .filter(Boolean)
      .some((field) => String(field).toLowerCase().includes(q));
  });
});

const hasActiveFilter = computed(
  () => !!(search.value || courseFilter.value || instructorFilter.value || statusFilter.value),
);

function clearFilters() {
  search.value = "";
  courseFilter.value = "";
  instructorFilter.value = "";
  statusFilter.value = "";
}

// Format a Y-m-d date string to "02 Sep 2026" in the local timezone.
function formatDate(dateStr) {
  if (!dateStr) return "";
  return new Date(`${dateStr}T00:00:00`).toLocaleDateString("en-GB", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  });
}

// Start status label + badge style for a class. Falls back to "No Start Date"
// when the class has no start date, and "Not Started" for future dates.
function startBadge(item) {
  if (!item.start_date) {
    return { label: t("No Start Date"), classes: "bg-slate-100 text-slate-500 ring-slate-400/20 dark:bg-gray-700 dark:text-gray-400 dark:ring-gray-500/20" };
  }
  if (item.start_weeks === 0) {
    return { label: t("Just Started"), classes: "bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-500/20" };
  }
  if (item.start_weeks === null || item.start_weeks === undefined) {
    return { label: t("Not Started"), classes: "bg-sky-50 text-sky-700 ring-sky-600/20 dark:bg-sky-500/10 dark:text-sky-400 dark:ring-sky-500/20" };
  }
  if (item.start_weeks <= 4) {
    const label = item.start_weeks === 1 ? t("Started 1 Week Ago") : t("Started :count Weeks Ago", { count: item.start_weeks });
    return { label, classes: "bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-500/10 dark:text-blue-400 dark:ring-blue-500/20" };
  }
  return { label: t("Started :count Weeks Ago", { count: item.start_weeks }), classes: "bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-400 dark:ring-amber-500/20" };
}

const selectClass = "w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20";
</script>

<template>
  <div class="space-y-5">
    <!-- Search + filters -->
    <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
      <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="relative sm:col-span-2 lg:col-span-1">
          <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
          <input
            v-model="search"
            type="text"
            :placeholder="$t('Search class, course, instructor, room')"
            :class="[selectClass, 'pl-9']"
          />
        </div>
        <SelectSearch v-model="courseFilter" :options="courseOptions" placeholder="All Courses" empty-text="No courses" />
        <SelectSearch v-model="instructorFilter" :options="instructorOptions" placeholder="All Instructors" empty-text="No instructors" />
        <SelectSearch v-model="statusFilter" :options="statusOptions" placeholder="All Status" empty-text="No status" />
      </div>
      <div v-if="hasActiveFilter" class="mt-3 flex items-center justify-between gap-3 text-xs text-slate-500 dark:text-gray-400">
        <span>{{ $t(':count of :total classes', { count: filteredClasses.length, total: classes.length }) }}</span>
        <button type="button" class="font-semibold text-blue-600 hover:underline dark:text-blue-400" @click="clearFilters">
          {{ $t('Clear filters') }}
        </button>
      </div>
    </div>

    <div v-if="filteredClasses.length > 0" class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
      <div
        v-for="item in filteredClasses"
        :key="item.id"
        class="group relative flex flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:shadow-xl dark:border-gray-800 dark:bg-gray-900"
      >
        <div class="flex items-start gap-3 min-w-0">
          <div
            class="shrink-0 flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 ring-1 ring-indigo-100 dark:bg-indigo-500/10 dark:text-indigo-400 dark:ring-indigo-500/20"
          >
            <BookOpen class="h-5 w-5" />
          </div>
          <div class="min-w-0">
            <h3 class="truncate text-sm font-semibold text-slate-900 transition-colors group-hover:text-indigo-600 dark:text-gray-100 dark:group-hover:text-indigo-400">
              {{ item.title }}
            </h3>
            <p class="mt-0.5 truncate text-xs text-slate-500 dark:text-gray-400">
              {{ item.course }}
            </p>
          </div>
        </div>

        <div class="mt-4 space-y-2.5 text-sm">
          <div class="flex items-center justify-between gap-2">
            <span class="flex items-center gap-2 text-slate-500 dark:text-gray-400">
              <UserPlus class="h-4 w-4" />
              {{ $t('Instructor') }}
            </span>
            <span class="truncate font-medium text-slate-800 dark:text-gray-200">{{ item.teacher }}</span>
          </div>

          <div class="flex items-center justify-between gap-2">
            <span class="flex items-center gap-2 text-slate-500 dark:text-gray-400">
              <DoorOpen class="h-4 w-4" />
              {{ $t('Room') }}
            </span>
            <span class="truncate font-medium text-slate-800 dark:text-gray-200">{{ item.floor }} {{ item.room }}</span>
          </div>

          <div class="flex items-center justify-between gap-2">
            <span class="flex items-center gap-2 text-slate-500 dark:text-gray-400">
              <CalendarDays class="h-4 w-4" />
              {{ $t('Days') }}
            </span>
            <span class="truncate font-medium text-slate-800 dark:text-gray-200">{{ item.term }}</span>
          </div>

          <div class="flex items-center justify-between gap-2">
            <span class="flex items-center gap-2 text-slate-500 dark:text-gray-400">
              <Clock3 class="h-4 w-4" />
              {{ $t('Time') }}
            </span>
            <span class="truncate font-medium text-emerald-600 dark:text-emerald-400">{{ item.time }}</span>
          </div>

          <div class="flex items-center justify-between gap-2">
            <span class="flex items-center gap-2 text-slate-500 dark:text-gray-400">
              <CalendarDays class="h-4 w-4" />
              {{ $t('Start Date') }}
            </span>
            <span class="truncate font-medium text-slate-800 dark:text-gray-200">{{ item.start_date ? formatDate(item.start_date) : "—" }}</span>
          </div>

          <div class="flex items-center justify-between gap-2">
            <span class="flex items-center gap-2 text-slate-500 dark:text-gray-400">
              <Clock class="h-4 w-4" />
              {{ $t('Status') }}
            </span>
            <span
              :class="[
                'inline-flex shrink-0 items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset',
                startBadge(item).classes,
              ]"
            >{{ startBadge(item).label }}</span>
          </div>

          <div class="flex items-center justify-between gap-2">
            <span class="flex items-center gap-2 text-slate-500 dark:text-gray-400">
              <Users class="h-4 w-4" />
              {{ $t('Students') }}
            </span>
            <span class="truncate font-semibold text-slate-800 tabular-nums dark:text-gray-200">
              {{ item.students }} / {{ item.capacity }}
            </span>
          </div>
        </div>

        <button
          type="button"
          @click="registerClass = item"
          class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-500"
        >
          <UserPlus class="h-4 w-4" />
          {{ $t('Register New Student') }}
        </button>
      </div>
    </div>

    <EmptyState
      v-else
      :title="hasActiveFilter ? $t('No classes match your filters') : $t('No started classes')"
      :description="hasActiveFilter
        ? $t('Try a different search or clear the filters.')
        : $t('No class has started yet. Create or open a class first.')"
    />

    <RegisterStudentModal
      :show="!!registerClass"
      :class-id="registerClass?.id"
      :class-title="registerClass?.title"
      :seats-left="registerClass ? Math.max(0, (registerClass.capacity ?? 0) - (registerClass.students ?? 0)) : null"
      @close="registerClass = null"
    />
  </div>
</template>
