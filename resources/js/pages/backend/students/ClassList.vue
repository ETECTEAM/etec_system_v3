<script setup>
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";

import { Search, RotateCcw, Plus, LayoutGrid, Table2 } from "@lucide/vue";

import DashboardLayout from "../../../layouts/DashboardLayout.vue";
import ClassCrad from "../../../components/ui/card/ClassCrad.vue";
import ClassTable from "./components/ClassTable.vue";
import Breadcrumbs from "../../../components/ui/breadcrumbs/Breadcrumbs.vue";
import PageHero from "../../../components/ui/page-hero/PageHero.vue";
import DepositSummaryCard from "./components/DepositSummaryCard.vue";

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

const search = ref(props.filters.search ?? "");
const filteredClasses = computed(() => props.classes?.data ?? []);
let searchTimer = null;

function runSearch() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    router.get(
      "/dashboard/students",
      { search: search.value },
      { preserveState: true, replace: true }
    );
  }, 350);
}

const breadcrumbItems = [
  { label: "Dashboard", href: "/dashboard" },
  { label: "Class List", current: true },
];

function refresh() {
  search.value = "";
  router.get("/dashboard/students", {}, { preserveState: true, replace: true });
}

function goCreateClass() {
  router.visit("/dashboard/students/create");
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
          <PageHero title="Class List" />
        </div>

        <button
          @click="goCreateClass"
          class="inline-flex items-center justify-center rounded-xl bg-blue-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-70 dark:bg-blue-600 dark:hover:bg-blue-500"
        >
          <Plus class="w-4 h-4" /> Add EnRoll
        </button>
      </div>

      <!-- depositSummary  -->
      <div class="mt-6">
        <DepositSummaryCard :depositSummary="depositSummary" />
      </div>

      <div
        class="flex flex-col gap-4 border-b border-slate-100 px-6 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800"
      >
        <!-- Title -->
        <div>
          <h1 class="text-xl font-bold text-slate-800 dark:text-gray-100">All Class</h1>
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
              placeholder="Search student..."
              class="w-full rounded-xl border border-slate-300 py-2 pl-10 pr-4 text-sm outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:border-indigo-500 dark:focus:ring-indigo-500/20"
            />
          </div>

          <!-- Reset -->
          <button
            @click="refresh"
            class="inline-flex items-center justify-center gap-2 rounded-xl  bg-blue-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-500"
          >
            <RotateCcw class="h-4 w-4" />
            Reset
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
            Card
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
            Table
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
