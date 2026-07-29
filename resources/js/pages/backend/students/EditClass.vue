<script setup>
import { router } from "@inertiajs/vue3";
import { ArrowLeft } from "@lucide/vue";
import DashboardLayout from "../../../layouts/DashboardLayout.vue";
import Breadcrumbs from "../../../components/ui/breadcrumbs/Breadcrumbs.vue";
import PageHero from "../../../components/ui/page-hero/PageHero.vue";
import ClassForm from "./components/ClassForm.vue";

defineProps({
  classData: {
    type: Object,
    default: null,
  },
  options: {
    type: Object,
    default: () => ({}),
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
        <div class="space-y-6">
            <Breadcrumbs :items="breadcrumbItems" />

            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
              <PageHero eyebrow="Class Management" :title="$t('Edit Class')" :description="$t('Update class information.')" />

              <button @click="back" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-500">
                <ArrowLeft class="w-4 h-4" /> {{ $t('Back') }}
              </button>
            </div>

            <div v-if="!classData" class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-slate-500 shadow-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400">
              {{ $t('Loading class data...') }}
            </div>

            <ClassForm v-else :classData="classData" :options="options" mode="edit" />
        </div>
    </div>
  </DashboardLayout>
</template>
