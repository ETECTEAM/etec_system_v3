<script setup>
import { computed, ref } from "vue";
import { UserPlus, Users, Clock3, CalendarDays, DoorOpen, BookOpen, Search } from "@lucide/vue";
import RegisterStudentModal from "./RegisterStudentModal.vue";
import EmptyState from "../../../../components/ui/empty-state/EmptyState.vue";
import SelectSearch from "@/components/ui/select-search/SelectSearch.vue";
import { useI18n } from "@/i18n";

const { t } = useI18n();

const props = defineProps({
  // Eligible classes only (open seats + recently started / upcoming),
  // presented via GetClassList::presentClass. Filtered server-side.
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

const courseOptions = computed(() => toOptions(props.classes.map((c) => c.course)));
const instructorOptions = computed(() => toOptions(props.classes.map((c) => c.teacher)));

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
    if (!q) return true;

    return [item.title, item.course, item.teacher, item.room, item.floor, item.term, item.time]
      .filter(Boolean)
      .some((field) => String(field).toLowerCase().includes(q));
  });
});

const hasActiveFilter = computed(() => !!(search.value || courseFilter.value || instructorFilter.value));

function clearFilters() {
  search.value = "";
  courseFilter.value = "";
  instructorFilter.value = "";
}

function formatDate(dateStr) {
  if (!dateStr) return "—";
  return new Date(`${dateStr}T00:00:00`).toLocaleDateString("en-GB", { day: "2-digit", month: "short", year: "numeric" });
}

// Whole days from today to the class start date (>0 future, 0 today, <0 past).
function daysToStart(dateStr) {
  if (!dateStr) return null;
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  return Math.round((new Date(`${dateStr}T00:00:00`) - today) / 86400000);
}

// "Starts in 5 Days" / "Started 2 Weeks Ago" — derived from the real start date.
function startBadge(item) {
  const d = daysToStart(item.start_date);
  const sky = "bg-sky-50 text-sky-700 ring-sky-600/20 dark:bg-sky-500/10 dark:text-sky-400";
  const emerald = "bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400";
  const blue = "bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-500/10 dark:text-blue-400";
  const amber = "bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-400";

  if (d === null) return { label: t("Starting Soon"), classes: sky };
  if (d > 1) return { label: t("Starts in :count Days", { count: d }), classes: sky };
  if (d === 1) return { label: t("Starts Tomorrow"), classes: sky };
  if (d === 0) return { label: t("Starts Today"), classes: emerald };

  const ago = -d;
  if (ago < 7) return { label: ago === 1 ? t("Started 1 Day Ago") : t("Started :count Days Ago", { count: ago }), classes: emerald };
  if (ago < 28) {
    const w = Math.floor(ago / 7);
    return { label: w === 1 ? t("Started 1 Week Ago") : t("Started :count Weeks Ago", { count: w }), classes: blue };
  }
  return { label: t("Started 1 Month Ago"), classes: amber };
}

function seatsLeft(item) {
  return Math.max((item.capacity ?? 0) - (item.students ?? 0), 0);
}
function fillPercent(item) {
  return item.capacity ? Math.round((item.students / item.capacity) * 100) : 0;
}
function isFull(item) {
  return (item.students ?? 0) >= (item.capacity ?? 0);
}

const inputClass = "w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-700 outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20";
</script>

<template>
  <div class="space-y-5">
    <!-- Search + filters -->
    <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm dark:border-gray-800 dark:bg-gray-900">
      <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <div class="relative">
          <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
          <input
            v-model="search"
            type="text"
            :placeholder="$t('Search course, class, instructor, room, day, time')"
            :class="[inputClass, 'pl-9']"
          />
        </div>
        <SelectSearch v-model="courseFilter" :options="courseOptions" placeholder="All Courses" empty-text="No courses" />
        <SelectSearch v-model="instructorFilter" :options="instructorOptions" placeholder="All Instructors" empty-text="No instructors" />
      </div>
      <div v-if="hasActiveFilter" class="mt-3 flex items-center justify-between gap-3 text-xs text-slate-500 dark:text-gray-400">
        <span>{{ $t(':count of :total eligible classes', { count: filteredClasses.length, total: classes.length }) }}</span>
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
        <div class="flex min-w-0 items-start gap-3">
          <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 ring-1 ring-indigo-100 dark:bg-indigo-500/10 dark:text-indigo-400 dark:ring-indigo-500/20">
            <BookOpen class="h-5 w-5" />
          </div>
          <div class="min-w-0">
            <h3 class="truncate text-sm font-semibold text-slate-900 transition-colors group-hover:text-indigo-600 dark:text-gray-100 dark:group-hover:text-indigo-400">
              {{ item.title }}
            </h3>
            <p class="mt-0.5 truncate text-xs text-slate-500 dark:text-gray-400">{{ item.course }}</p>
          </div>
        </div>

        <div class="mt-4 space-y-2.5 text-sm">
          <div class="flex items-center justify-between gap-2">
            <span class="flex items-center gap-2 text-slate-500 dark:text-gray-400"><UserPlus class="h-4 w-4" /> {{ $t('Instructor') }}</span>
            <span class="truncate font-medium text-slate-800 dark:text-gray-200">{{ item.teacher }}</span>
          </div>
          <div class="flex items-center justify-between gap-2">
            <span class="flex items-center gap-2 text-slate-500 dark:text-gray-400"><DoorOpen class="h-4 w-4" /> {{ $t('Room') }}</span>
            <span class="truncate font-medium text-slate-800 dark:text-gray-200">{{ item.floor }} {{ item.room }}</span>
          </div>
          <div class="flex items-center justify-between gap-2">
            <span class="flex items-center gap-2 text-slate-500 dark:text-gray-400"><CalendarDays class="h-4 w-4" /> {{ $t('Days') }}</span>
            <span class="truncate font-medium text-slate-800 dark:text-gray-200">{{ item.term }}</span>
          </div>
          <div class="flex items-center justify-between gap-2">
            <span class="flex items-center gap-2 text-slate-500 dark:text-gray-400"><Clock3 class="h-4 w-4" /> {{ $t('Time') }}</span>
            <span class="truncate font-medium text-emerald-600 dark:text-emerald-400">{{ item.time }}</span>
          </div>

          <!-- Start date + badge -->
          <div class="flex items-start justify-between gap-2 border-t border-slate-100 pt-2.5 dark:border-gray-800">
            <span class="flex items-center gap-2 text-slate-500 dark:text-gray-400"><CalendarDays class="h-4 w-4" /> {{ $t('Start Date') }}</span>
            <span class="flex flex-col items-end gap-1">
              <span class="font-medium text-slate-800 dark:text-gray-200">{{ formatDate(item.start_date) }}</span>
              <span :class="['inline-flex shrink-0 items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset', startBadge(item).classes]">
                {{ startBadge(item).label }}
              </span>
            </span>
          </div>
        </div>

        <!-- Capacity -->
        <div class="mt-3 border-t border-slate-100 pt-3 dark:border-gray-800">
          <div class="mb-1.5 flex items-center justify-between">
            <span class="flex items-center gap-2 text-sm text-slate-500 dark:text-gray-400"><Users class="h-4 w-4" /> {{ $t('Students') }}</span>
            <span class="text-sm font-semibold tabular-nums text-slate-800 dark:text-gray-200">{{ item.students }} / {{ item.capacity }}</span>
          </div>
          <div class="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-gray-800">
            <div
              class="h-full rounded-full transition-all duration-500"
              :class="fillPercent(item) >= 90 ? 'bg-amber-500' : 'bg-blue-900 dark:bg-blue-500'"
              :style="{ width: Math.min(fillPercent(item), 100) + '%' }"
            ></div>
          </div>
          <div class="mt-1 flex items-center justify-between text-[11px] tabular-nums text-slate-400 dark:text-gray-500">
            <span>{{ fillPercent(item) }}% {{ $t('filled') }}</span>
            <span :class="seatsLeft(item) <= 2 && !isFull(item) ? 'font-semibold text-amber-600 dark:text-amber-400' : ''">
              {{ isFull(item) ? $t('Full') : $t(':count seats left', { count: seatsLeft(item) }) }}
            </span>
          </div>
        </div>

        <button
          type="button"
          :disabled="isFull(item)"
          @click="registerClass = item"
          class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:text-slate-500 dark:bg-blue-600 dark:hover:bg-blue-500 dark:disabled:bg-gray-700 dark:disabled:text-gray-400"
        >
          <UserPlus class="h-4 w-4" />
          {{ isFull(item) ? $t('Class Full') : $t('Register New Student') }}
        </button>
      </div>
    </div>

    <EmptyState
      v-else
      :title="hasActiveFilter ? $t('No classes match your filters') : $t('No classes open for registration')"
      :description="hasActiveFilter
        ? $t('Try a different search or clear the filters.')
        : $t('No class currently has open seats and a recent or upcoming start date.')"
    />

    <RegisterStudentModal
      :show="!!registerClass"
      :class-id="registerClass?.id"
      :class-title="registerClass?.title"
      :seats-left="registerClass ? seatsLeft(registerClass) : null"
      :class-info="registerClass
        ? {
            title: registerClass.title,
            teacher: registerClass.teacher,
            term: registerClass.term,
            time: registerClass.time,
            room: [registerClass.floor, registerClass.room].filter(Boolean).join(' '),
            startLabel: startBadge(registerClass).label,
            students: registerClass.students,
            capacity: registerClass.capacity,
          }
        : null"
      @close="registerClass = null"
    />
  </div>
</template>
