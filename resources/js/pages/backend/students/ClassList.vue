<script setup>
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";

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
    <div class="w-full">
      <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6">
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
          class="bg-indigo-600 text-white px-5 py-3 rounded-lg flex items-center gap-2 cursor-pointer self-start sm:self-auto whitespace-nowrap"
        >
          <Plus class="w-4 h-4" /> Add Class
        </button>
      </div>

      <div class="bg-white rounded-xl shadow p-3 sm:p-4 flex flex-col sm:flex-row gap-3 mb-6">
        <div class="relative flex-1 w-full">
          <input
            v-model="search"
            class="w-full border rounded-lg py-3 pl-4 pr-12"
            placeholder="Search classes..."
          />
          <Search class="absolute right-4 top-1/2 -translate-y-1/2 w-5" />
        </div>

        <div class="flex gap-2 sm:gap-3">
          <button
            @click="refresh"
            class="bg-gray-700 text-white px-4 sm:px-5 rounded-lg flex items-center gap-2 whitespace-nowrap"
          >
            <RotateCcw class="w-4 h-4" /> Reset
          </button>

          <button
            @click="viewMode = 'card'"
            :class="[
              'px-3 sm:px-4 py-3 rounded-lg border flex items-center gap-2 whitespace-nowrap',
              viewMode === 'card' ? 'bg-indigo-600 text-white' : 'bg-white',
            ]"
          >
            <LayoutGrid class="w-4 h-4" /> Card
          </button>

          <button
            @click="viewMode = 'table'"
            :class="[
              'px-3 sm:px-4 py-3 rounded-lg border flex items-center gap-2 whitespace-nowrap',
              viewMode === 'table' ? 'bg-indigo-600 text-white' : 'bg-white',
            ]"
          >
            <Table2 class="w-4 h-4" /> Table
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
    </div>
  </DashboardLayout>
</template>
