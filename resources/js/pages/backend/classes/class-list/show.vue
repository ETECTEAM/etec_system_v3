<script setup>
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Breadcrumbs } from '@/components/ui/breadcrumbs';
import { PageHero } from '@/components/ui/page-hero';

const props = defineProps({
  classList: Object,
});

const classList = computed(() => props.classList ?? {});
const classListId = computed(() => classList.value.id ?? classList.value.class_list_id ?? 'Unknown');
const classListLabel = computed(() => `#${classListId.value}`);
const classListEditHref = computed(() => (classListId.value !== 'Unknown' ? `/dashboard/class-list/${classListId.value}/edit` : '/dashboard/class-list'));
const classListDeleteHref = computed(() => (classListId.value !== 'Unknown' ? `/dashboard/class-list/${classListId.value}` : null));

const statusLabel = computed(() => {
  const status = classList.value.status ?? 'Unknown';
  return status === 'progress' || status === 'completed' || status === 'cancelled'
    ? status.charAt(0).toUpperCase() + status.slice(1)
    : 'Unknown';
});

const statusClasses = computed(() => {
  const status = classList.value.status;
  if (status === 'completed') {
    return 'bg-emerald-50 text-emerald-700';
  }
  if (status === 'progress') {
    return 'bg-blue-50 text-blue-700';
  }
  if (status === 'cancelled') {
    return 'bg-rose-50 text-rose-700';
  }
  return 'bg-slate-100 text-slate-600';
});

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Class List', href: '/dashboard/class-list' },
  { label: classListLabel.value, current: true },
];

const deleteItem = () => {
  if (!classListDeleteHref.value) {
    return;
  }

  if (!confirm('Are you sure you want to delete this class?')) {
    return;
  }

  router.delete(classListDeleteHref.value, {
    preserveScroll: true,
  });
};
</script>

<template>
  <Head :title="`Class List ${classListLabel.value}`" />
  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />

      <PageHero
        eyebrow="Management"
        :title="`Class ${classListLabel.value}`"
        description="Review the full schedule, instructor, and location details for this class."
      />

      <div class="grid gap-6 lg:grid-cols-[1.5fr_1fr]">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
          <div class="flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Class summary</p>
              <h2 class="mt-3 text-2xl font-semibold text-slate-900">{{ classList.course?.course_name || 'Untitled class' }}</h2>
              <p class="mt-2 text-sm text-slate-600">{{ classList.lesson?.lesson_name || 'No lesson assigned' }}</p>
            </div>
            <div class="space-y-2 text-right">
              <div class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-slate-700">
                <span>ID</span>
                <span class="rounded-full bg-slate-200 px-2 py-1 text-slate-800">{{ classListId.value }}</span>
              </div>
              <div :class="['inline-flex items-center gap-2 rounded-full px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em]', statusClasses]">
                <span>Status</span>
                <span>{{ statusLabel }}</span>
              </div>
            </div>
          </div>

          <div class="mt-8 grid gap-6 sm:grid-cols-2">
            <div class="rounded-3xl bg-slate-50 p-5">
              <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Instructor</p>
              <p class="mt-3 text-base font-semibold text-slate-900">{{ classList.teacher?.name || classList.teacher?.teacher_name || 'No instructor' }}</p>
            </div>
            <div class="rounded-3xl bg-slate-50 p-5">
              <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Students</p>
              <p class="mt-3 text-base font-semibold text-slate-900">{{ classList.student_count ?? 0 }}</p>
            </div>
            <div class="rounded-3xl bg-slate-50 p-5">
              <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Class type</p>
              <p class="mt-3 text-base font-semibold text-slate-900">{{ classList.class_type?.type_name || 'Unspecified' }}</p>
            </div>
            <div class="rounded-3xl bg-slate-50 p-5">
              <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Term</p>
              <p class="mt-3 text-base font-semibold text-slate-900">{{ classList.term?.term_name || 'Not set' }}</p>
            </div>
          </div>

          <div class="mt-8 grid gap-4 rounded-3xl border border-slate-200 bg-white p-6 text-sm text-slate-600">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div>
                <p class="text-xs uppercase text-slate-400">Time</p>
                <p class="mt-2 text-base text-slate-900">{{ classList.time?.time_name || 'Not scheduled' }}</p>
              </div>
              <div>
                <p class="text-xs uppercase text-slate-400">Room</p>
                <p class="mt-2 text-base text-slate-900">{{ classList.room?.room_number || 'Not assigned' }}</p>
              </div>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div>
                <p class="text-xs uppercase text-slate-400">Building</p>
                <p class="mt-2 text-base text-slate-900">{{ classList.building?.name || 'Unknown' }}</p>
              </div>
              <div>
                <p class="text-xs uppercase text-slate-400">Floor</p>
                <p class="mt-2 text-base text-slate-900">{{ classList.floor?.name || 'Unknown' }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="space-y-6">
          <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Quick details</h3>
            <div class="mt-6 space-y-4 text-sm text-slate-600">
              <div class="flex items-start justify-between gap-4">
                <span class="text-slate-400">Course</span>
                <span class="font-medium text-slate-900">{{ classList.course?.course_name || '—' }}</span>
              </div>
              <div class="flex items-start justify-between gap-4">
                <span class="text-slate-400">Lesson</span>
                <span class="font-medium text-slate-900">{{ classList.lesson?.lesson_name || '—' }}</span>
              </div>
              <div class="flex items-start justify-between gap-4">
                <span class="text-slate-400">Teacher</span>
                <span class="font-medium text-slate-900">{{ classList.teacher?.name || classList.teacher?.teacher_name || '—' }}</span>
              </div>
              <div class="flex items-start justify-between gap-4">
                <span class="text-slate-400">Status</span>
                <span class="font-medium text-slate-900">{{ statusLabel }}</span>
              </div>
              <div class="flex items-start justify-between gap-4">
                <span class="text-slate-400">Students</span>
                <span class="font-medium text-slate-900">{{ classList.student_count ?? 0 }}</span>
              </div>
            </div>
          </div>

          <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Actions</h3>
            <div class="mt-5 flex flex-col gap-3">
              <Link
                :href="classListEditHref"
                class="inline-flex w-full items-center justify-center rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-700"
              >
                Edit class
              </Link>
              <button
                type="button"
                @click="deleteItem"
                class="inline-flex w-full items-center justify-center rounded-xl bg-rose-500 px-4 py-3 text-sm font-semibold text-white transition hover:bg-rose-600"
              >
                Delete class
              </button>
              <Link
                href="/dashboard/class-list"
                class="inline-flex w-full items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
              >
                Back to list
              </Link>
            </div>
          </div>
        </div>
      </div>
    </section>
  </DashboardLayout>
</template>
