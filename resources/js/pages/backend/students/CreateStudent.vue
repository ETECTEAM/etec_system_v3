<script setup>
import { router, useForm } from "@inertiajs/vue3";
import { ArrowLeft, Save, UserPlus } from "@lucide/vue";

import DashboardLayout from "../../../layouts/DashboardLayout.vue";
import Breadcrumbs from "../../../components/ui/breadcrumbs/Breadcrumbs.vue";
import PageHero from "../../../components/ui/page-hero/PageHero.vue";

const props = defineProps({
  classData: {
    type: Object,
    required: true,
  },
});

const breadcrumbItems = [
  { label: "Dashboard", href: "/dashboard" },
  { label: "Class List", href: "/dashboard/students" },
  { label: "View Class", href: `/dashboard/students/view/${props.classData.id}` },
  { label: "Add Student", current: true },
];

const form = useForm({
  name: "",
  gender: "",
  phone: "",
});

function submit() {
  form.post(`/dashboard/students/${props.classData.id}/students`, {
    preserveScroll: true,
    onSuccess: () => form.reset(),
  });
}

function viewClass() {
  router.get(`/dashboard/students/view/${props.classData.id}`);
}

function classList() {
  router.get("/dashboard/students");
}
</script>

<template>
  <DashboardLayout>
    <div class="w-full">
      <div class="space-y-4 sm:space-y-5">
        <Breadcrumbs :items="breadcrumbItems" />
        <PageHero title="Add Student" />

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div class="flex items-center gap-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-indigo-100 dark:bg-indigo-500/10">
              <UserPlus class="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
            </div>
            <div>
              <h1 class="text-xl font-bold text-slate-900 dark:text-gray-100">
                Add Student to {{ classData.title }}
              </h1>
              <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">
                #{{ classData.id }} &middot; {{ classData.students }} / {{ classData.capacity }} students
              </p>
            </div>
          </div>

          <div class="flex flex-wrap gap-3">
            <button
              type="button"
              @click="viewClass"
              class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800"
            >
              <ArrowLeft class="h-4 w-4" />
              View Class
            </button>
            <button
              type="button"
              @click="classList"
              class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-500"
            >
              Class List
            </button>
          </div>
        </div>

        <form
          @submit.prevent="submit"
          class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900 sm:p-6"
        >
          <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
            <div>
              <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-300">
                Student Name
              </label>
              <input
                v-model="form.name"
                type="text"
                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-indigo-500 dark:focus:ring-indigo-500/20"
                placeholder="Enter student name"
              />
              <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">
                {{ form.errors.name }}
              </p>
            </div>

            <div>
              <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-300">
                Gender
              </label>
              <select
                v-model="form.gender"
                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-indigo-500 dark:focus:ring-indigo-500/20"
              >
                <option value="">Select gender</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
              </select>
              <p v-if="form.errors.gender" class="mt-1 text-xs text-red-600">
                {{ form.errors.gender }}
              </p>
            </div>

            <div>
              <label class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-300">
                Phone
              </label>
              <input
                v-model="form.phone"
                type="text"
                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-indigo-500 dark:focus:ring-indigo-500/20"
                placeholder="Enter phone number"
              />
              <p v-if="form.errors.phone" class="mt-1 text-xs text-red-600">
                {{ form.errors.phone }}
              </p>
            </div>
          </div>

          <div class="mt-6 flex justify-end">
            <button
              type="submit"
              :disabled="form.processing"
              class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-70 dark:bg-blue-600 dark:hover:bg-blue-500"
            >
              <Save class="h-4 w-4" />
              {{ form.processing ? "Saving..." : "Save Student" }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </DashboardLayout>
</template>
