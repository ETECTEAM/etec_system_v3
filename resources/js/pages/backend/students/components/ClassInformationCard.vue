<script setup>
import {
  GraduationCap,
  BookOpen,
  User,
  Building2,
  Layers,
  DoorOpen,
  Calendar,
  Clock,
  BadgeCheck,
  Users,
  Presentation,
} from "@lucide/vue";

defineProps({
  classData: {
    type: Object,
    default: null,
  },
});

function enrollmentPercent(capacity, enrolled) {
  if (!capacity || capacity === 0) return 0;
  return Math.min(Math.round((enrolled / capacity) * 100), 100);
}
</script>

<template>
  <!-- ─── Class Information Card ─── -->
  <div
    class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden"
  >
    <!-- Header -->
    <div
      class="flex items-center gap-3 border-b border-slate-100 bg-gradient-to-r from-indigo-50 to-white px-6 py-4"
    >
      <div
        class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-100"
      >
        <GraduationCap class="h-5 w-5 text-indigo-600" />
      </div>
      <div>
        <h2 class="text-lg font-semibold text-slate-900">
          Class Information
        </h2>
        <p class="text-xs text-slate-500">
          #{{ classData?.id }} &middot; {{ classData?.status }}
        </p>
      </div>
    </div>

    <!-- Body -->
    <div class="p-6">
      <div
        class="grid grid-cols-1 gap-x-8 gap-y-5 sm:grid-cols-2 lg:grid-cols-3"
      >
        <!-- Class Name -->
        <div class="flex items-start gap-3">
          <BookOpen class="mt-0.5 h-5 w-5 shrink-0 text-indigo-500" />
          <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
              Class Name
            </p>
            <p class="mt-0.5 font-semibold text-slate-900">
              {{ classData?.title ?? "—" }}
            </p>
          </div>
        </div>

        <!-- Lesson -->
        <div class="flex items-start gap-3">
          <Presentation class="mt-0.5 h-5 w-5 shrink-0 text-indigo-500" />
          <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
              Lesson
            </p>
            <p class="mt-0.5 font-semibold text-slate-900">
              {{ classData?.lesson ?? "—" }}
            </p>
          </div>
        </div>

        <!-- Teacher -->
        <div class="flex items-start gap-3">
          <User class="mt-0.5 h-5 w-5 shrink-0 text-indigo-500" />
          <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
              Teacher
            </p>
            <p class="mt-0.5 font-semibold text-slate-900">
              {{ classData?.teacher ?? "—" }}
            </p>
          </div>
        </div>

        <!-- Building -->
        <div class="flex items-start gap-3">
          <Building2 class="mt-0.5 h-5 w-5 shrink-0 text-indigo-500" />
          <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
              Building
            </p>
            <p class="mt-0.5 font-semibold text-slate-900">
              {{ classData?.building ?? "—" }}
            </p>
          </div>
        </div>

        <!-- Floor -->
        <div class="flex items-start gap-3">
          <Layers class="mt-0.5 h-5 w-5 shrink-0 text-indigo-500" />
          <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
              Floor
            </p>
            <p class="mt-0.5 font-semibold text-slate-900">
              {{ classData?.floor ?? "—" }}
            </p>
          </div>
        </div>

        <!-- Room -->
        <div class="flex items-start gap-3">
          <DoorOpen class="mt-0.5 h-5 w-5 shrink-0 text-indigo-500" />
          <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
              Room
            </p>
            <p class="mt-0.5 font-semibold text-slate-900">
              {{ classData?.room ?? "—" }}
            </p>
          </div>
        </div>

        <!-- Study Day -->
        <div class="flex items-start gap-3">
          <Calendar class="mt-0.5 h-5 w-5 shrink-0 text-indigo-500" />
          <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
              Study Day
            </p>
            <p class="mt-0.5 font-semibold text-slate-900">
              {{ classData?.term ?? "—" }}
            </p>
          </div>
        </div>

        <!-- Study Time -->
        <div class="flex items-start gap-3">
          <Clock class="mt-0.5 h-5 w-5 shrink-0 text-indigo-500" />
          <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
              Study Time
            </p>
            <p class="mt-0.5 font-semibold text-slate-900">
              {{ classData?.time ?? "—" }}
            </p>
          </div>
        </div>

        <!-- Status -->
        <div class="flex items-start gap-3">
          <BadgeCheck class="mt-0.5 h-5 w-5 shrink-0 text-indigo-500" />
          <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
              Status
            </p>
            <span
              class="mt-0.5 inline-block rounded-full bg-indigo-100 px-3 py-0.5 text-xs font-semibold text-indigo-700"
            >
              {{ classData?.status ?? "—" }}
            </span>
          </div>
        </div>

        <!-- Capacity -->
        <div class="flex items-start gap-3">
          <Users class="mt-0.5 h-5 w-5 shrink-0 text-indigo-500" />
          <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
              Capacity
            </p>
            <p class="mt-0.5 font-semibold text-slate-900">
              {{ classData?.capacity ?? "—" }} seats
            </p>
          </div>
        </div>

        <!-- Current Students -->
        <div class="flex items-start gap-3">
          <Users class="mt-0.5 h-5 w-5 shrink-0 text-emerald-500" />
          <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
              Current Students
            </p>
            <p class="mt-0.5 font-semibold text-slate-900">
              {{ classData?.students ?? 0 }} enrolled
            </p>
          </div>
        </div>
      </div>

      <!-- ─── Enrollment Progress Bar ─── -->
      <div class="mt-6 border-t border-slate-100 pt-5">
        <div class="flex items-center justify-between text-sm">
          <span class="font-medium text-slate-700">Enrollment Progress</span>
          <span class="font-semibold text-slate-900">
            {{ classData?.students ?? 0 }} / {{ classData?.capacity ?? 0 }}
            ({{ enrollmentPercent(classData?.capacity, classData?.students) }}%)
          </span>
        </div>
        <div class="mt-2 h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
          <div
            class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-indigo-400 transition-all duration-700"
            :style="{
              width: enrollmentPercent(classData?.capacity, classData?.students) + '%',
            }"
          />
        </div>
      </div>
    </div>
  </div>
</template>
