<script setup>
import { computed, ref } from "vue";
import { router } from "@inertiajs/vue3";
import { Search, RotateCcw, Plus, LayoutGrid, Table2, UserPlus } from "@lucide/vue";
import DashboardLayout from "../../../layouts/DashboardLayout.vue";
import ClassCrad from "../../../components/ui/card/ClassCrad.vue";
import ClassTable from "./components/ClassTable.vue";
import Breadcrumbs from "../../../components/ui/breadcrumbs/Breadcrumbs.vue";
import PageHero from "../../../components/ui/page-hero/PageHero.vue";
import DepositSummaryCard from "./components/DepositSummaryCard.vue";

// const search = ref("");
// const viewMode = ref("card");
const classes = ref([
  {
    id: 366,
    title: "Web Design + React.js",
    lesson: "Bootstrap",
    building: "Building B",
    floor: "Floor 1",
    room: "ETEC B102",
    status: "Physical Class",
    term: "Mon & Thu",
    time: "09:00 am - 10:30 am",
    students: 8,
    capacity: 20,
    notifications: 5,
  },
  {
    id: 367,
    title: "Laravel 12",
    lesson: "Authentication",
    building: "Building A",
    floor: "Floor 2",
    room: "ETEC A203",
    status: "Physical Class",
    term: "Tue & Fri",
    time: "10:45 am - 12:15 pm",
    students: 15,
    capacity: 25,
    notifications: 2,
  },
  {
    id: 368,
    title: "UI/UX Design",
    lesson: "Figma Prototype",
    building: "Building C",
    floor: "Floor 3",
    room: "ETEC C301",
    status: "Online Class",
    term: "Wednesday",
    time: "01:30 pm - 03:00 pm",
    students: 22,
    capacity: 30,
    notifications: 1,
  },
  {
    id: 369,
    title: "Java Programming",
    lesson: "Collections Framework",
    building: "Building B",
    floor: "Floor 2",
    room: "ETEC B205",
    status: "Physical Class",
    term: "Saturday",
    time: "08:00 am - 11:00 am",
    students: 18,
    capacity: 20,
    notifications: 3,
  },
]);
const viewMode = ref("card");

const props = defineProps({
  classes: {
    type: Object,
    default: () => ({ data: [] }),
  },
  filters: {
    type: Object,
    default: () => ({ search: "" }),
  },
  depositSummary: {
    type: Object,
    default: null,
  },
});

// const search = ref(props.filters.search ?? "");
const filteredClasses = computed(() => props.classes?.data ?? []);
let searchTimer = null;

function runSearch() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    router.get(
      "/dashboard/enroll",
      { search: search.value },
      { preserveState: true, replace: true }
    );
  }, 350);
}

const search = ref(props.filters?.search ?? "");
// const viewMode = ref("card");

const breadcrumbItems = [
  { label: "Dashboard", href: "/dashboard" },
  { label: "Class List", current: true },
];

function refresh() {
    search.value = "";
    router.visit("/dashboard/enroll", { preserveState: true });
}

function goCreateClass() {
    router.visit("/dashboard/enroll/create");
}

function goRegisterStudent() {
    router.visit("/dashboard/enroll/students/create");
}

function onSearch() {
    router.visit("/dashboard/enroll", {
        data: { search: search.value || null },
        preserveState: true,
        replace: true,
    });
}
</script>

<template>
  <DashboardLayout>
    <div class="w-full">
      <div
        class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6"
      >
        <div>
          <Breadcrumbs :items="breadcrumbItems" class="mb-4" />
          <PageHero :title="$t('Class List')" />
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <button
            @click="goRegisterStudent"
            class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
          >
            <UserPlus class="w-4 h-4" /> {{ $t('Register Student') }}
          </button>

          <button
            @click="goCreateClass"
            class="inline-flex items-center justify-center rounded-xl bg-blue-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-70 dark:bg-blue-600 dark:hover:bg-blue-500"
          >
            <Plus class="w-4 h-4" /> {{ $t('Add Enroll') }}
          </button>
        </div>
      </div>

      <!-- depositSummary  -->
      <div class="mt-6">
        <DepositSummaryCard :depositSummary="depositSummary" />
      </div>

      <div
        class="flex flex-col gap-4 px-6 py-4 sm:flex-row sm:items-center sm:justify-between"
      >
        <!-- Title -->
        <div>
          <h1 class="text-xl font-bold text-slate-800 dark:text-gray-100">{{ $t('All Class') }}</h1>
        </div>

        <!-- Actions -->
        <div class="flex flex-wrap items-center gap-3">
          <!-- Search -->
          <div class="relative w-full sm:w-64">
            <Search
              class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-gray-500"
            />
            <input
              v-model="search"
              type="text"
              :placeholder="$t('Search student...')"
              class="w-full rounded-xl border border-slate-300 py-2 pl-10 pr-4 text-sm outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:border-indigo-500 dark:focus:ring-indigo-500/20"
            />
          </div>

          <!-- Reset -->
          <button
            @click="refresh"
            class="inline-flex items-center justify-center gap-2 rounded-xl  bg-blue-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-500"
          >
            <RotateCcw class="h-4 w-4" />
            {{ $t('Reset') }}
          </button>

          <!-- Card -->
          <button
            @click="viewMode = 'card'"
            :class="[
              'inline-flex items-center justify-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold shadow-sm transition',
              viewMode === 'card'
                ? 'border-blue-900 bg-blue-800 text-white'
                : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700',
            ]"
          >
            <LayoutGrid class="h-4 w-4" />
            {{ $t('Card') }}
          </button>

          <!-- Table -->
          <button
            @click="viewMode = 'table'"
            :class="[
              'inline-flex items-center justify-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold shadow-sm transition',
              viewMode === 'table'
                ? 'border-blue-900 bg-blue-800 text-white'
                : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700',
            ]"
          >
            <Table2 class="h-4 w-4" />
            {{ $t('Table') }}
          </button>
        </div>
      </div>

      <!-- Card View -->
      <div
        v-if="viewMode === 'card'"
        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5 sm:gap-5"
      >
        <ClassCrad
          v-for="item in filteredClasses"
          :key="item.id"
          :classData="item"
          :count="item.notifications"
        />
      </div>

      <!-- Table View -->
      <div v-else class="w-full overflow-x-auto">
        <ClassTable :items="filteredClasses" />
      </div>

      <div v-if="classes?.links?.length > 3" class="mt-6 flex flex-wrap justify-center gap-2">
        <button
          v-for="link in classes.links"
          :key="link.label"
          type="button"
          :disabled="!link.url"
          @click="link.url && router.visit(link.url, { preserveState: true })"
          v-html="link.label"
          :class="[
            'rounded-lg border px-3 py-2 text-sm',
            link.active
              ? 'border-blue-900 bg-blue-900 text-white'
              : 'border-slate-300 bg-white text-slate-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300',
            !link.url ? 'cursor-not-allowed opacity-50' : 'hover:bg-slate-100 dark:hover:bg-gray-800',
          ]"
        ></button>
      </div>
    </div>
  </DashboardLayout>
</template>
