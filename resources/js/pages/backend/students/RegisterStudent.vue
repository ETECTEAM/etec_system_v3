<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import { ArrowLeft, UserPlus, Users, Clock3, CalendarDays, DoorOpen, BookOpen } from "@lucide/vue";
import DashboardLayout from "../../../layouts/DashboardLayout.vue";
import Breadcrumbs from "../../../components/ui/breadcrumbs/Breadcrumbs.vue";
import PageHero from "../../../components/ui/page-hero/PageHero.vue";
import RegisterStudentModal from "./components/RegisterStudentModal.vue";
import EmptyState from "../../../components/ui/empty-state/EmptyState.vue";

const props = defineProps({
  // Classes that just started, presented via GetClassList::presentClass.
  classes: {
    type: Array,
    default: () => [],
  },
});

const breadcrumbItems = [
  { label: "Dashboard", href: "/dashboard" },
  { label: "Class List", href: "/dashboard/enroll" },
  { label: "Register Student", current: true },
];

// The class targeted by "Register New Student".
const registerClass = ref(null);

function classList() {
  router.get("/dashboard/enroll");
}
</script>

<template>
  <DashboardLayout>
    <div class="w-full">
      <div class="space-y-6">
        <Breadcrumbs :items="breadcrumbItems" />

        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
          <PageHero
            eyebrow="Class Management"
            :title="$t('Register Student')"
            :description="$t('Classes that just started — pick one and register a new student.')"
          />

          <button
            type="button"
            @click="classList"
            class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800"
          >
            <ArrowLeft class="h-4 w-4" />
            {{ $t('Class List') }}
          </button>
        </div>

        <div v-if="classes.length > 0" class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3">
          <div
            v-for="item in classes"
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
          title="No started classes"
          :description="$t('No class has started yet. Create or open a class first.')"
        />
      </div>

      <RegisterStudentModal
        :show="!!registerClass"
        :class-id="registerClass?.id"
        :class-title="registerClass?.title"
        :seats-left="registerClass ? Math.max(0, (registerClass.capacity ?? 0) - (registerClass.students ?? 0)) : null"
        @close="registerClass = null"
      />
    </div>
  </DashboardLayout>
</template>
