<script setup>
import { ref, computed } from "vue";
import { Head, router } from "@inertiajs/vue3";

import { Search, RotateCcw, Plus, LayoutGrid, Table2 } from "@lucide/vue";

import DashboardLayout from "../../../layouts/DashboardLayout.vue";
import ClassCrad from "../../../components/ui/card/ClassCrad.vue";
import ClassTable from "./components/ClassTable.vue";
import Breadcrumbs from "../../../components/ui/breadcrumbs/Breadcrumbs.vue";
import PageHero from "../../../components/ui/page-hero/PageHero.vue";

const search = ref("");
const viewMode = ref("card");
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
]);

const filteredClasses = computed(() => {
  if (!search.value) return classes.value;

  return classes.value.filter((item) =>
    (item.title + item.lesson + item.building + item.room)
      .toLowerCase()
      .includes(search.value.toLowerCase())
  );
});

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Class List', current: true },
]

function refresh() {
  search.value = "";
}

function goCreateClass() {
  router.visit("/dashboard/students/create");
}
</script>
<template>
  <DashboardLayout>
    <div class="max-w-7xl mx-auto py-6">
      <div class="flex justify-between items-center mb-6">
        <div>
          <Breadcrumbs :items="breadcrumbItems" />
          <PageHero
            eyebrow="EnRoll Management"
            title="Class List"
          />

          <p class="text-gray-500">Manage all classes</p>
        </div>

        <button
          @click="goCreateClass"
          class="bg-indigo-600 text-white px-5 py-3 rounded-lg flex items-center gap-2 cursor-pointer"
        >
          <Plus class="w-4 h-4" /> Add Class
        </button>
      </div>

      <div class="bg-white rounded-xl shadow p-4 flex gap-3 mb-6">
        <div class="relative flex-1">
          <input
            v-model="search"
            class="w-full border rounded-lg py-3 pl-4 pr-12"
            placeholder="Search..."
          />
          <Search class="absolute right-4 top-1/2 -translate-y-1/2 w-5" />
        </div>

        <button
          @click="refresh"
          class="bg-gray-700 text-white px-5 rounded-lg flex items-center gap-2"
        >
          <RotateCcw class="w-4 h-4" /> Reset
        </button>

        <button
          @click="viewMode = 'card'"
          :class="[
            'px-4 py-3 rounded-lg border flex items-center gap-2',
            viewMode === 'card' ? 'bg-indigo-600 text-white' : 'bg-white',
          ]"
        >
          <LayoutGrid class="w-4 h-4" /> Card
        </button>

        <button
          @click="viewMode = 'table'"
          :class="[
            'px-4 py-3 rounded-lg border flex items-center gap-2',
            viewMode === 'table' ? 'bg-indigo-600 text-white' : 'bg-white',
          ]"
        >
          <Table2 class="w-4 h-4" /> Table
        </button>
      </div>

      <!-- Card View -->
      <div
        v-if="viewMode === 'card'"
        class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6"
      >
        <ClassCrad
          v-for="item in filteredClasses"
          :key="item.id"
          :classData="item"
          :count="item.notifications"
        />
      </div>

      <!-- Table View -->
      <div v-else>
        <ClassTable :items="filteredClasses" />
      </div>
    </div>
  </DashboardLayout>
</template>