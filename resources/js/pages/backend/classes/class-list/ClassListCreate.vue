<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Breadcrumbs } from '@/components/ui/breadcrumbs';
import { PageHero } from '@/components/ui/page-hero';
import { SelectSearch } from '@/components/ui/select-search';

const props = defineProps({
  teachers: Array,
  courses: Array,
  lessons: Array,
  terms: Array,
  times: Array,
  rooms: Array,
  classTypes: Array,
});

const form = useForm({
  teacher_id: '',
  course_id: '',
  lesson_id: '',
  term_id: '',
  time_id: '',
  room_id: '',
  class_type_id: '',
  status: 'upcoming',
});

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Class List', href: '/dashboard/class-list' },
  { label: 'Create', current: true },
];

const optionLabel = (item) => {
  return item?.name || item?.title || item?.term_name || item?.time_name || item?.room_number || item?.type_name || 'Unknown';
};

// Values are stringified so SelectSearch's strict === comparison between
// option.value and the v-model works.
const toOptions = (items, valueKey = 'id') =>
  (items || []).map((item) => ({ label: optionLabel(item), value: String(item[valueKey]) }));

const teacherOptions = computed(() => toOptions(props.teachers));
const courseOptions = computed(() => toOptions(props.courses));
const lessonOptions = computed(() => toOptions(props.lessons));
const termOptions = computed(() => toOptions(props.terms));
const timeOptions = computed(() => toOptions(props.times));
const roomOptions = computed(() => toOptions(props.rooms));
const classTypeOptions = computed(() => toOptions(props.classTypes, 'class_type_id'));

const statusOptions = [
  { label: 'Upcoming', value: 'upcoming' },
  { label: 'Active', value: 'active' },
  { label: 'Pre-End', value: 'pre_end' },
  { label: 'Ended', value: 'ended' },
  { label: 'Cancelled', value: 'cancelled' },
];

const selectClass = 'flex w-full h-11 items-center justify-between rounded-xl border border-slate-300 bg-white px-4 text-sm transition focus:border-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20 dark:disabled:bg-gray-900 dark:disabled:text-gray-500';

const submit = () => {
  form.post('/dashboard/class-list');
};
</script>

<template>
  <Head :title="$t('Create Class')" />
  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero
        eyebrow="Management"
        :title="$t('Create Class List Entry')"
        description="Schedule a new class with course, room, and teacher details."
      />

      <div class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8 dark:border-gray-800 dark:bg-gray-900">
        <form @submit.prevent="submit" class="grid gap-6 lg:grid-cols-2">
          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-200">{{ $t('Teacher') }} <span class="text-xs font-normal text-slate-400">{{ $t('(optional)') }}</span></span>
            <SelectSearch
              v-model="form.teacher_id"
              :options="teacherOptions"
              :placeholder="$t('Select teacher')"
              :button-class="selectClass"
            />
            <span v-if="form.errors.teacher_id" class="text-xs text-red-600 dark:text-red-400">{{ form.errors.teacher_id }}</span>
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
            <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-200">{{ $t('Term') }}</span>
            <SelectSearch
              v-model="form.term_id"
              :options="termOptions"
              :placeholder="$t('Select term')"
              :button-class="selectClass"
            />
            <span v-if="form.errors.term_id" class="text-xs text-red-600 dark:text-red-400">{{ form.errors.term_id }}</span>
          </label>

          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-200">{{ $t('Time') }}</span>
            <SelectSearch
              v-model="form.time_id"
              :options="timeOptions"
              :placeholder="$t('Select time')"
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
              href="/dashboard/class-list"
              class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
            >
              {{ $t('Cancel') }}
            </Link>
            <button
              type="submit"
              :disabled="form.processing"
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
