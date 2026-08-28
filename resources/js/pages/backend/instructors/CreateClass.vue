<script setup>
import { computed, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Breadcrumbs } from '@/components/ui/breadcrumbs';
import { PageHero } from '@/components/ui/page-hero';
import { SelectSearch } from '@/components/ui/select-search';

const props = defineProps({
  courses: Array,
  lessons: Array,
  rooms: Array,
  // Class Type -> Term -> Time, already narrowed to the slots this instructor
  // is free for (see InstructorClassService::formOptions).
  scheduleGroups: { type: Array, default: () => [] },
});

const form = useForm({
  title: '',
  course_id: '',
  lesson_id: '',
  class_type_id: '',
  term_id: '',
  time_id: '',
  room_id: '',
  capacity: 20,
  status: 'upcoming',
  attendance_latitude: '',
  attendance_longitude: '',
  attendance_radius_meters: '',
});

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Create Class', current: true },
];

const optionLabel = (item) =>
  item?.name || item?.title || item?.room_number || 'Unknown';

const toOptions = (items, valueKey = 'id') =>
  (items || []).map((item) => ({ label: optionLabel(item), value: String(item[valueKey]) }));

const courseOptions = computed(() => toOptions(props.courses));
const lessonOptions = computed(() => toOptions(props.lessons));
const roomOptions = computed(() => toOptions(props.rooms));

// --- Class Type -> Term -> Time cascade, sourced from scheduleGroups ---
const classTypeOptions = computed(() =>
  props.scheduleGroups.map((group) => ({
    label: group.class_type_name,
    value: String(group.class_type_id),
  })),
);

const selectedGroup = computed(() =>
  props.scheduleGroups.find((group) => String(group.class_type_id) === String(form.class_type_id)),
);

const termOptions = computed(() =>
  (selectedGroup.value?.schedules ?? []).map((schedule) => ({
    label: schedule.term_name,
    value: String(schedule.term_id),
  })),
);

const selectedSchedule = computed(() =>
  (selectedGroup.value?.schedules ?? []).find(
    (schedule) => String(schedule.term_id) === String(form.term_id),
  ),
);

const timeOptions = computed(() =>
  (selectedSchedule.value?.times ?? []).map((time) => ({
    label: time.time_name,
    value: String(time.id),
  })),
);

const noSlots = computed(() => props.scheduleGroups.length === 0);

// Changing a parent clears its children — the child's valid options come from
// the newly selected parent.
watch(() => form.class_type_id, () => {
  form.term_id = '';
  form.time_id = '';
});
watch(() => form.term_id, () => {
  form.time_id = '';
});

const statusOptions = [
  { label: 'Upcoming', value: 'upcoming' },
  { label: 'Active', value: 'active' },
  { label: 'Pre-End', value: 'pre_end' },
  { label: 'Ended', value: 'ended' },
  { label: 'Cancelled', value: 'cancelled' },
];

const selectClass = 'flex w-full h-11 items-center justify-between rounded-xl border border-slate-300 bg-white px-4 text-sm transition focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20 dark:disabled:bg-gray-900 dark:disabled:text-gray-500';

const submit = () => {
  form.post('/dashboard/instructor/classes');
};
</script>

<template>
  <Head :title="$t('Create Class')" />
  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero
        eyebrow="My Classes"
        :title="$t('Create Class')"
        description="Schedule a new class for yourself with course, room, and time details."
      />

      <div class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8 dark:border-gray-800 dark:bg-gray-900">
        <p
          v-if="noSlots"
          class="mb-6 rounded-xl border border-dashed border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-300"
        >
          {{ $t('You have no available schedule slots. Set your working hours under Busy Time, or ask an admin to update your availability.') }}
        </p>

        <form @submit.prevent="submit" class="grid gap-6 lg:grid-cols-2">
          <label class="block lg:col-span-2">
            <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-200">{{ $t('Title') }}</span>
            <input
              type="text"
              v-model="form.title"
              class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
            />
            <span v-if="form.errors.title" class="text-xs text-red-600 dark:text-red-400">{{ form.errors.title }}</span>
          </label>

          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-200">{{ $t('Course') }}</span>
            <SelectSearch
              v-model="form.course_id"
              :options="courseOptions"
              :placeholder="$t('Select course')"
              :button-class="selectClass"
            />
            <span v-if="form.errors.course_id" class="text-xs text-red-600 dark:text-red-400">{{ form.errors.course_id }}</span>
          </label>

          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-200">{{ $t('Lesson') }}</span>
            <SelectSearch
              v-model="form.lesson_id"
              :options="lessonOptions"
              :placeholder="$t('Select lesson')"
              :button-class="selectClass"
            />
            <span v-if="form.errors.lesson_id" class="text-xs text-red-600 dark:text-red-400">{{ form.errors.lesson_id }}</span>
          </label>

          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-200">{{ $t('Class Type') }}</span>
            <SelectSearch
              v-model="form.class_type_id"
              :options="classTypeOptions"
              :placeholder="$t('Select class type')"
              :button-class="selectClass"
            />
            <span v-if="form.errors.class_type_id" class="text-xs text-red-600 dark:text-red-400">{{ form.errors.class_type_id }}</span>
          </label>

          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-200">{{ $t('Term') }}</span>
            <SelectSearch
              v-model="form.term_id"
              :options="termOptions"
              :disabled="!form.class_type_id"
              :placeholder="form.class_type_id ? $t('Select term') : $t('Select class type first')"
              :button-class="selectClass"
            />
            <span v-if="form.errors.term_id" class="text-xs text-red-600 dark:text-red-400">{{ form.errors.term_id }}</span>
          </label>

          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-200">{{ $t('Time') }}</span>
            <SelectSearch
              v-model="form.time_id"
              :options="timeOptions"
              :disabled="!form.term_id"
              :placeholder="form.term_id ? $t('Select time') : $t('Select term first')"
              :button-class="selectClass"
            />
            <span v-if="form.errors.time_id" class="text-xs text-red-600 dark:text-red-400">{{ form.errors.time_id }}</span>
          </label>

          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-200">{{ $t('Room') }}</span>
            <SelectSearch
              v-model="form.room_id"
              :options="roomOptions"
              :placeholder="$t('Select room')"
              :button-class="selectClass"
            />
            <span v-if="form.errors.room_id" class="text-xs text-red-600 dark:text-red-400">{{ form.errors.room_id }}</span>
          </label>

          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-200">{{ $t('Capacity') }}</span>
            <input
              type="number"
              min="1"
              v-model="form.capacity"
              class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
            />
            <span v-if="form.errors.capacity" class="text-xs text-red-600 dark:text-red-400">{{ form.errors.capacity }}</span>
          </label>

          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-200">{{ $t('Status') }}</span>
            <SelectSearch
              v-model="form.status"
              :options="statusOptions"
              :placeholder="$t('Select status')"
              :button-class="selectClass"
            />
            <span v-if="form.errors.status" class="text-xs text-red-600 dark:text-red-400">{{ form.errors.status }}</span>
          </label>

          <div class="lg:col-span-2 flex flex-wrap items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-gray-800">
            <Link
              href="/dashboard"
              class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
            >
              {{ $t('Cancel') }}
            </Link>
            <button
              type="submit"
              :disabled="form.processing || noSlots"
              class="rounded-xl bg-blue-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:opacity-70 dark:bg-blue-600 dark:hover:bg-blue-500"
            >
              {{ form.processing ? $t('Saving...') : $t('Save Class') }}
            </button>
          </div>
        </form>
      </div>
    </section>
  </DashboardLayout>
</template>
