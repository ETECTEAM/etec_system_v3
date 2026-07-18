<script setup>
import { router } from "@inertiajs/vue3";
import { GraduationCap, ArrowLeft } from "@lucide/vue";
import DashboardLayout from "../../../layouts/DashboardLayout.vue";
import Breadcrumbs from "../../../components/ui/breadcrumbs/Breadcrumbs.vue";
import PageHero from "../../../components/ui/page-hero/PageHero.vue";
import ClassForm from "./components/ClassForm.vue";

defineProps({
  classData: {
    type: Object,
    required: true,
  },
  options: {
    type: Object,
    required: true,
  },
});

const breadcrumbItems = [
  { label: "Dashboard", href: "/dashboard" },
  { label: "Class List", href: "/dashboard/students" },
  { label: "Edit Class", current: true },
];

function back() {
  router.get("/dashboard/students");
}
</script>

<template>
  <DashboardLayout>
    <div class="w-full">
      <div class="space-y-4 sm:space-y-5">
        <Breadcrumbs :items="breadcrumbItems" />
        <PageHero />

        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-indigo-100 flex items-center justify-center shrink-0 dark:bg-indigo-500/10">
              <GraduationCap class="w-6 h-6 sm:w-7 sm:h-7 text-indigo-600 dark:text-indigo-400" />
            </div>
            <div>
              <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-gray-100">
                Edit Class
              </h1>
            </div>
          </div>
          <button @click="back" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-500">
            <ArrowLeft class="w-4 h-4" /> Back
          </button>
        </div>

        <ClassForm :classData="classData" :options="options" mode="edit" />
      </div>
    </div>
  </DashboardLayout>
</template>
