<script setup>
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Breadcrumbs } from '@/components/ui/breadcrumbs';
import { PageHero } from '@/components/ui/page-hero';
import { useI18n } from '@/i18n';

const { t } = useI18n();

const props = defineProps({
  classList: Object,
});

const classList = computed(() => props.classList ?? {});
const classListId = computed(() => classList.value?.id || classList.value?.class_list_id || 'Unknown');
const classListLabel = computed(() => (classListId.value === 'Unknown' ? 'Unknown' : `#${classListId.value}`));
const classListEditHref = computed(() => (classListId.value !== 'Unknown' ? `/dashboard/class-list/${classListId.value}/edit` : '/dashboard/class-list'));
const classListDeleteHref = computed(() => (classListId.value !== 'Unknown' ? `/dashboard/class-list/${classListId.value}` : null));

const courseTitle = computed(() => classList.value?.course?.title || `Class ${classListLabel.value}`);
const lessonTitle = computed(() => classList.value?.lesson?.title || 'No lesson assigned');
const instructorName = computed(() => classList.value?.teacher?.name || classList.value?.teacher?.teacher_name || 'No instructor');
const classTypeName = computed(() => classList.value?.class_type?.type_name || 'Unspecified');
const termName = computed(() => classList.value?.term?.term_name || 'Not set');
const timeName = computed(() => classList.value?.time?.time_name || 'Not scheduled');
const roomNumber = computed(() => classList.value?.room?.room_number || 'Not assigned');
const buildingName = computed(() => classList.value?.building?.name || 'Unknown');
const floorName = computed(() => classList.value?.floor?.name || 'Unknown');
const teacherLabel = computed(() => instructorName.value);

const statusLabel = computed(() => {
  const status = classList.value.status ?? 'Unknown';
  return status === 'progress' || status === 'completed' || status === 'cancelled'
    ? status.charAt(0).toUpperCase() + status.slice(1)
    : 'Unknown';
});

const statusClasses = computed(() => {
  const status = classList.value.status;
  if (status === 'completed') {
    return 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400';
  }
  if (status === 'progress') {
    return 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400';
  }
  if (status === 'cancelled') {
    return 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400';
  }
  return 'bg-slate-100 text-slate-600 dark:bg-gray-800 dark:text-gray-300';
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

  if (!confirm(t('Are you sure you want to delete this class?'))) {
    return;
  }

  router.delete(classListDeleteHref.value, {
    preserveScroll: true,
  });
};
</script>

<template>
  <Head :title="`${$t('Class List')} ${classListLabel}`" />
  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />

      <PageHero
        eyebrow="Management"
        :title="courseTitle"
        :description="$t('Review the full schedule, instructor, and location details for this class.')"
      />

      <div class="grid gap-6 lg:grid-cols-[1.5fr_1fr]">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
          <div class="flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 dark:text-gray-400">{{ $t('Class summary') }}</p>
              <h2 class="mt-3 text-2xl font-semibold text-slate-900 dark:text-gray-100">{{ courseTitle || 'Untitled class' }}</h2>
              <p class="mt-2 text-sm text-slate-600 dark:text-gray-300">{{ $t(lessonTitle) }}</p>
            </div>
            <div class="space-y-2 text-right">
              <div class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-slate-700 dark:bg-gray-800 dark:text-gray-300">
                <span>{{ $t('ID') }}</span>
                <span class="rounded-full bg-slate-200 px-2 py-1 text-slate-800 dark:bg-gray-700 dark:text-gray-200">{{ classListId }}</span>
              </div>
              <div :class="['inline-flex items-center gap-2 rounded-full px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em]', statusClasses]">
                <span>{{ $t('Status') }}</span>
                <span>{{ $t(statusLabel) }}</span>
              </div>
            </div>
          </div>

          <div class="mt-8 grid gap-6 sm:grid-cols-2">
            <div class="rounded-3xl bg-slate-50 p-5 dark:bg-gray-800">
              <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400 dark:text-gray-500">{{ $t('Instructor') }}</p>
              <p class="mt-3 text-base font-semibold text-slate-900 dark:text-gray-100">{{ $t(teacherLabel) }}</p>
            </div>
            <div class="rounded-3xl bg-slate-50 p-5 dark:bg-gray-800">
              <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400 dark:text-gray-500">{{ $t('Students') }}</p>
              <p class="mt-3 text-base font-semibold text-slate-900 dark:text-gray-100">{{ classList.student_count ?? 0 }}</p>
            </div>
            <div class="rounded-3xl bg-slate-50 p-5 dark:bg-gray-800">
              <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400 dark:text-gray-500">{{ $t('Class type') }}</p>
              <p class="mt-3 text-base font-semibold text-slate-900 dark:text-gray-100">{{ $t(classTypeName) }}</p>
            </div>
            <div class="rounded-3xl bg-slate-50 p-5 dark:bg-gray-800">
              <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400 dark:text-gray-500">{{ $t('Term') }}</p>
              <p class="mt-3 text-base font-semibold text-slate-900 dark:text-gray-100">{{ $t(termName) }}</p>
            </div>
          </div>

          <div class="mt-8 grid gap-4 rounded-3xl border border-slate-200 bg-white p-6 text-sm text-slate-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div>
                <p class="text-xs uppercase text-slate-400 dark:text-gray-500">{{ $t('Time') }}</p>
                <p class="mt-2 text-base text-slate-900 dark:text-gray-100">{{ $t(timeName) }}</p>
              </div>
              <div>
                <p class="text-xs uppercase text-slate-400 dark:text-gray-500">{{ $t('Room') }}</p>
                <p class="mt-2 text-base text-slate-900 dark:text-gray-100">{{ $t(roomNumber) }}</p>
              </div>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div>
                <p class="text-xs uppercase text-slate-400 dark:text-gray-500">{{ $t('Building') }}</p>
                <p class="mt-2 text-base text-slate-900 dark:text-gray-100">{{ $t(buildingName) }}</p>
              </div>
              <div>
                <p class="text-xs uppercase text-slate-400 dark:text-gray-500">{{ $t('Floor') }}</p>
                <p class="mt-2 text-base text-slate-900 dark:text-gray-100">{{ $t(floorName) }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="space-y-6">
          <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h3 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-gray-400">{{ $t('Quick details') }}</h3>
            <div class="mt-6 space-y-4 text-sm text-slate-600 dark:text-gray-300">
              <div class="flex items-start justify-between gap-4">
                <span class="text-slate-400 dark:text-gray-500">{{ $t('Course') }}</span>
                <span class="font-medium text-slate-900 dark:text-gray-100">{{ classList.course?.title || '—' }}</span>
              </div>
              <div class="flex items-start justify-between gap-4">
                <span class="text-slate-400 dark:text-gray-500">{{ $t('Lesson') }}</span>
                <span class="font-medium text-slate-900 dark:text-gray-100">{{ classList.lesson?.title || '—' }}</span>
              </div>
              <div class="flex items-start justify-between gap-4">
                <span class="text-slate-400 dark:text-gray-500">{{ $t('Teacher') }}</span>
                <span class="font-medium text-slate-900 dark:text-gray-100">{{ classList.teacher?.name || classList.teacher?.teacher_name || '—' }}</span>
              </div>
              <div class="flex items-start justify-between gap-4">
                <span class="text-slate-400 dark:text-gray-500">{{ $t('Status') }}</span>
                <span class="font-medium text-slate-900 dark:text-gray-100">{{ $t(statusLabel) }}</span>
              </div>
              <div class="flex items-start justify-between gap-4">
                <span class="text-slate-400 dark:text-gray-500">{{ $t('Students') }}</span>
                <span class="font-medium text-slate-900 dark:text-gray-100">{{ classList.student_count ?? 0 }}</span>
              </div>
            </div>
          </div>

          <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h3 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-gray-400">{{ $t('Actions') }}</h3>
            <div class="mt-5 flex flex-col gap-3">
              <Link
                :href="classListEditHref"
                class="inline-flex w-full items-center justify-center rounded-xl bg-blue-800 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-500"
              >
                {{ $t('Edit class') }}
              </Link>
              <button
                type="button"
                @click="deleteItem"
                class="inline-flex w-full items-center justify-center rounded-xl bg-rose-800 px-4 py-3 text-sm font-semibold text-white transition hover:bg-rose-600 dark:bg-rose-600 dark:hover:bg-rose-500"
              >
                {{ $t('Delete class') }}
              </button>
              <Link
                href="/dashboard/class-list"
                class="inline-flex w-full items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
              >
                {{ $t('Back to list') }}
              </Link>
            </div>
          </div>
        </div>
      </div>
    </section>
  </DashboardLayout>
</template>
