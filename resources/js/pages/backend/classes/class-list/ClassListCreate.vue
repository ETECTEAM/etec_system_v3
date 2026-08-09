<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Breadcrumbs } from '@/components/ui/breadcrumbs';
import { PageHero } from '@/components/ui/page-hero';

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
  title: '',
  teacher_id: '',
  course_id: '',
  lesson_id: '',
  term_id: '',
  time_id: '',
  room_id: '',
  class_type_id: '',
  capacity: 20,
  status: 'upcoming',
});

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Class List', href: '/dashboard/class-list' },
  { label: 'Create', current: true },
];

const optionLabel = (item) => {
  return item?.name || item?.teacher_name || item?.course_name || item?.lesson_name || item?.term_name || item?.time_name || item?.room_number || item?.type_name || 'Unknown';
};

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
            <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-200">{{ $t('Teacher') }} <span class="text-xs font-normal text-slate-400">{{ $t('(optional)') }}</span></span>
            <select
              v-model="form.teacher_id"
              class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
            >
              <option value="">{{ $t('Select teacher') }}</option>
              <option v-for="item in props.teachers || []" :key="item.id" :value="item.id">{{ optionLabel(item) }}</option>
            </select>
            <span v-if="form.errors.teacher_id" class="text-xs text-red-600 dark:text-red-400">{{ form.errors.teacher_id }}</span>
          </label>

          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-200">{{ $t('Course') }}</span>
            <select
              v-model="form.course_id"
              class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
            >
              <option value="">{{ $t('Select course') }}</option>
              <option v-for="item in props.courses || []" :key="item.id" :value="item.id">{{ optionLabel(item) }}</option>
            </select>
            <span v-if="form.errors.course_id" class="text-xs text-red-600 dark:text-red-400">{{ form.errors.course_id }}</span>
          </label>

          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-200">{{ $t('Lesson') }}</span>
            <select
              v-model="form.lesson_id"
              class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
            >
              <option value="">{{ $t('Select lesson') }}</option>
              <option v-for="item in props.lessons || []" :key="item.id" :value="item.id">{{ optionLabel(item) }}</option>
            </select>
            <span v-if="form.errors.lesson_id" class="text-xs text-red-600 dark:text-red-400">{{ form.errors.lesson_id }}</span>
          </label>

          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-200">{{ $t('Term') }}</span>
            <select
              v-model="form.term_id"
              class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
            >
              <option value="">{{ $t('Select term') }}</option>
              <option v-for="item in props.terms || []" :key="item.id" :value="item.id">{{ optionLabel(item) }}</option>
            </select>
            <span v-if="form.errors.term_id" class="text-xs text-red-600 dark:text-red-400">{{ form.errors.term_id }}</span>
          </label>

          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-200">{{ $t('Time') }}</span>
            <select
              v-model="form.time_id"
              class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
            >
              <option value="">{{ $t('Select time') }}</option>
              <option v-for="item in props.times || []" :key="item.id" :value="item.id">{{ optionLabel(item) }}</option>
            </select>
            <span v-if="form.errors.time_id" class="text-xs text-red-600 dark:text-red-400">{{ form.errors.time_id }}</span>
          </label>

          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-200">{{ $t('Room') }}</span>
            <select
              v-model="form.room_id"
              class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
            >
              <option value="">{{ $t('Select room') }}</option>
              <option v-for="item in props.rooms || []" :key="item.id" :value="item.id">{{ optionLabel(item) }}</option>
            </select>
            <span v-if="form.errors.room_id" class="text-xs text-red-600 dark:text-red-400">{{ form.errors.room_id }}</span>
          </label>

          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-200">{{ $t('Class Type') }}</span>
            <select
              v-model="form.class_type_id"
              class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
            >
              <option value="">{{ $t('Select class type') }}</option>
              <option v-for="item in props.classTypes || []" :key="item.class_type_id" :value="item.class_type_id">{{ optionLabel(item) }}</option>
            </select>
            <span v-if="form.errors.class_type_id" class="text-xs text-red-600 dark:text-red-400">{{ form.errors.class_type_id }}</span>
          </label>

          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-200">{{ $t('Capacity') }}</span>
            <input
              type="number"
              min="0"
              v-model="form.capacity"
              class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
            />
            <span v-if="form.errors.capacity" class="text-xs text-red-600 dark:text-red-400">{{ form.errors.capacity }}</span>
          </label>

          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700 dark:text-gray-200">{{ $t('Status') }}</span>
            <select
              v-model="form.status"
              class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
            >
              <option value="upcoming">{{ $t('Upcoming') }}</option>
              <option value="active">{{ $t('Active') }}</option>
              <option value="pre_end">{{ $t('Pre-End') }}</option>
              <option value="ended">{{ $t('Ended') }}</option>
              <option value="cancelled">{{ $t('Cancelled') }}</option>
            </select>
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
