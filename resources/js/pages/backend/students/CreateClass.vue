<script setup>
import { router } from "@inertiajs/vue3";
import { GraduationCap, ArrowLeft } from "@lucide/vue";
import DashboardLayout from "../../../layouts/DashboardLayout.vue";
import Breadcrumbs from "../../../components/ui/breadcrumbs/Breadcrumbs.vue";
import PageHero from "../../../components/ui/page-hero/PageHero.vue";
import ClassForm from "./components/ClassForm.vue";

defineProps({
  options: {
    type: Object,
    required: true,
  },
});

const breadcrumbItems = [
  { label: "Dashboard", href: "/dashboard" },
  { label: "Class List", href: "/dashboard/students" },
  { label: "Add Class", current: true },
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
            <PageHero/>
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-indigo-100 flex items-center justify-center shrink-0 dark:bg-indigo-500/10">
                        <GraduationCap class="w-6 h-6 sm:w-7 sm:h-7 text-indigo-600 dark:text-indigo-400"/>
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-gray-100">
                            Create New Class
                        </h1>
                    </div>
                </div>
                <button @click="back" class="inline-flex items-center justify-center gap-2 rounded-xl  bg-blue-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-500">
                    <ArrowLeft class="w-4 h-4"/> Back
                </button>
            </div>

            <!-- Card -->

            <div class="bg-white rounded-2xl sm:rounded-3xl shadow-xl border border-slate-200 p-4 sm:p-6 lg:p-8 dark:bg-gray-900 dark:border-gray-800">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-5 lg:gap-6">

                    <!-- Title -->
                    <div>
                        <label class="font-semibold mb-2 block">
                            Class Title
                        </label>
                        <input
                            v-model="form.title"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:ring-2 focus:ring-indigo-500 outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                            placeholder="Web Design + React.js"/>
                    </div>

                    <!-- Lesson -->

                    <!-- <div>
                        <label class="font-semibold mb-2 block">
                            Lesson
                        </label>
                        <input
                            v-model="form.lesson"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                            placeholder="Bootstrap"/>
                    </div> -->

                    <!-- Status -->
                    <div>
                        <label class="font-semibold mb-2 block">
                            Status
                        </label>
                        <select v-model="form.status" class="w-full rounded-xl border border-slate-300 px-4 py-3 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                            <option value="">
                                Select Status
                            </option>
                            <option>
                                Physical Class
                            </option>
                            <option>
                                Online Class
                            </option>
                        </select>
                    </div>

                    <!-- Building -->

                    <div>
                        <label class="font-semibold mb-2 block">
                            Building
                        </label>

                        <select v-model="form.building" class="w-full rounded-xl border border-slate-300 px-4 py-3 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                            <option>
                                Building A
                            </option>
                            <option>
                                Building B
                            </option>
                            <option>
                                Building C
                            </option>
                        </select>
                    </div>

                    <!-- Floor -->
                    <div>
                        <label class="font-semibold mb-2 block">
                            Floor
                        </label>
                        <select v-model="form.floor" class="w-full rounded-xl border border-slate-300 px-4 py-3 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                            <option>
                                Floor 1
                            </option>
                            <option>
                                Floor 2
                            </option>
                            <option>
                                Floor 3
                            </option>
                        </select>
                    </div>

                    <!-- Room -->
                    <div>
                        <label class="font-semibold mb-2 block">
                            Room
                        </label>
                        <input v-model="form.room" class="w-full rounded-xl border border-slate-300 px-4 py-3 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200" placeholder="B101"/>
                    </div>

                    <!-- Term -->
                    <div>
                        <label class="font-semibold mb-2 block">
                            Study Days
                        </label>
                        <input  v-model="form.term" class="w-full rounded-xl border border-slate-300 px-4 py-3 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200" placeholder="Mon & Thu"/>
                    </div>

                    <!-- Time -->
                    <div>
                        <label class="font-semibold mb-2 block">
                            Study Time
                        </label>
                        <input v-model="form.time" class="w-full rounded-xl border border-slate-300 px-4 py-3 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200" placeholder="09:00 AM - 10:30 AM"/>
                    </div>

                    <!-- Capacity -->
                    <div>
                        <label class="font-semibold mb-2 block">
                            Capacity
                        </label>
                        <input type="number" min="1" v-model="form.capacity" class="w-full rounded-xl border border-slate-300 px-4 py-3 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"/>
                    </div>

                    <div>
                        <label class="font-semibold mb-2 block">Price</label>
                        <input type="number" min="1" v-model="form.price" class="w-full rounded-xl border border-slate-300 px-4 py-3 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"/>
                    </div>

                    <div>
                        <label class="font-semibold mb-2 block">Start EnRoll</label>
                        <input type="date" min="1" v-model="form.startEnroll" class="w-full rounded-xl border border-slate-300 px-4 py-3 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"/>
                    </div>

                    <div>
                        <label class="font-semibold mb-2 block">Start Date</label>
                        <input type="date" min="1" v-model="form.startDate" class="w-full rounded-xl border border-slate-300 px-4 py-3 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"/>
                    </div>
                </div>
                <!-- Footer -->

                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 sm:gap-4 mt-6 sm:mt-8 lg:mt-10">

                    <button
                        @click="back"
                        class="inline-flex items-center justify-center gap-2 rounded-xl  bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">

                        Cancel

                    </button>

                    <button
                        @click="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-xl  bg-blue-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-500">

                        <Save class="w-4 h-4"/>

                        Save Class

                    </button>

                </div>

            </div>
          <button @click="back" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-500">
            <ArrowLeft class="w-4 h-4" /> Back
          </button>
        </div>

        <ClassForm :options="options" mode="create" />
    </div>
  </DashboardLayout>
</template>
