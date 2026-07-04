<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Breadcrumbs } from '@/components/ui/breadcrumbs';
import { PageHero } from '@/components/ui/page-hero';

const props = defineProps({
  classList: Object,
  teachers: Array,
  courses: Array,
  lessons: Array,
  terms: Array,
  times: Array,
  buildings: Array,
  floors: Array,
  rooms: Array,
  classTypes: Array,
});

const form = useForm({
  teacher_id: props.classList.teacher_id || '',
  course_id: props.classList.course_id || '',
  lesson_id: props.classList.lesson_id || '',
  term_id: props.classList.term_id || '',
  time_id: props.classList.time_id || '',
  building_id: props.classList.building_id || '',
  floor_id: props.classList.floor_id || '',
  room_id: props.classList.room_id || '',
  class_type_id: props.classList.class_type_id || '',
  student_count: props.classList.student_count || 0,
  status: props.classList.status || 'progress',
});

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Class List', href: '/dashboard/class-list' },
  { label: 'Edit', current: true },
];

const optionLabel = (item) => {
  return item?.name || item?.title || item?.teacher_name || item?.course_name || item?.lesson_name || item?.term_name || item?.time_name || item?.room_number || item?.type_name || 'Unknown';
};

const submit = () => {
  form.put(`/dashboard/class-list/${props.classList?.id ?? props.classList?.class_list_id}`);
};
</script>

<template>
  <Head :title="`Edit Class #${props.classList?.id ?? props.classList?.class_list_id}`" />
  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />
      <PageHero
        eyebrow="Management"
        title="Edit Class"
        description="Update this class schedule and assignment details."
      />

      <div class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <form @submit.prevent="submit" class="grid gap-6 lg:grid-cols-2">
          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700">Teacher</span>
            <select
              v-model="form.teacher_id"
              class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
            >
              <option value="">Select teacher</option>
              <option v-for="item in props.teachers || []" :key="item.id" :value="item.id">{{ optionLabel(item) }}</option>
            </select>
            <span v-if="form.errors.teacher_id" class="text-xs text-red-600">{{ form.errors.teacher_id }}</span>
          </label>

          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700">Course</span>
            <select
              v-model="form.course_id"
              class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
            >
              <option value="">Select course</option>
              <option v-for="item in props.courses || []" :key="item.id" :value="item.id">{{ optionLabel(item) }}</option>
            </select>
            <span v-if="form.errors.course_id" class="text-xs text-red-600">{{ form.errors.course_id }}</span>
          </label>

          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700">Lesson</span>
            <select
              v-model="form.lesson_id"
              class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
            >
              <option value="">Select lesson</option>
              <option v-for="item in props.lessons || []" :key="item.id" :value="item.id">{{ optionLabel(item) }}</option>
            </select>
            <span v-if="form.errors.lesson_id" class="text-xs text-red-600">{{ form.errors.lesson_id }}</span>
          </label>

          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700">Term</span>
            <select
              v-model="form.term_id"
              class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
            >
              <option value="">Select term</option>
              <option v-for="item in props.terms || []" :key="item.id" :value="item.id">{{ optionLabel(item) }}</option>
            </select>
            <span v-if="form.errors.term_id" class="text-xs text-red-600">{{ form.errors.term_id }}</span>
          </label>

          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700">Time</span>
            <select
              v-model="form.time_id"
              class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
            >
              <option value="">Select time</option>
              <option v-for="item in props.times || []" :key="item.id" :value="item.id">{{ optionLabel(item) }}</option>
            </select>
            <span v-if="form.errors.time_id" class="text-xs text-red-600">{{ form.errors.time_id }}</span>
          </label>

          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700">Building</span>
            <select
              v-model="form.building_id"
              class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
            >
              <option value="">Select building</option>
              <option v-for="item in props.buildings || []" :key="item.id" :value="item.id">{{ optionLabel(item) }}</option>
            </select>
            <span v-if="form.errors.building_id" class="text-xs text-red-600">{{ form.errors.building_id }}</span>
          </label>

          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700">Floor</span>
            <select
              v-model="form.floor_id"
              class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
            >
              <option value="">Select floor</option>
              <option v-for="item in props.floors || []" :key="item.id" :value="item.id">{{ optionLabel(item) }}</option>
            </select>
            <span v-if="form.errors.floor_id" class="text-xs text-red-600">{{ form.errors.floor_id }}</span>
          </label>

          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700">Room</span>
            <select
              v-model="form.room_id"
              class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
            >
              <option value="">Select room</option>
              <option v-for="item in props.rooms || []" :key="item.id" :value="item.id">{{ optionLabel(item) }}</option>
            </select>
            <span v-if="form.errors.room_id" class="text-xs text-red-600">{{ form.errors.room_id }}</span>
          </label>

          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700">Class Type</span>
            <select
              v-model="form.class_type_id"
              class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
            >
              <option value="">Select class type</option>
              <option v-for="item in props.classTypes || []" :key="item.class_type_id" :value="item.class_type_id">{{ optionLabel(item) }}</option>
            </select>
            <span v-if="form.errors.class_type_id" class="text-xs text-red-600">{{ form.errors.class_type_id }}</span>
          </label>

          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700">Student Count</span>
            <input
              type="number"
              min="0"
              v-model="form.student_count"
              class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
            />
            <span v-if="form.errors.student_count" class="text-xs text-red-600">{{ form.errors.student_count }}</span>
          </label>

          <label class="block">
            <span class="mb-2 block text-sm font-semibold text-slate-700">Status</span>
            <select
              v-model="form.status"
              class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
            >
              <option value="progress">In Progress</option>
              <option value="completed">Completed</option>
              <option value="cancelled">Cancelled</option>
            </select>
            <span v-if="form.errors.status" class="text-xs text-red-600">{{ form.errors.status }}</span>
          </label>

          <div class="lg:col-span-2 flex flex-wrap items-center justify-end gap-3 pt-4 border-t border-slate-100">
            <Link
              href="/dashboard/class-list"
              class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
            >
              Cancel
            </Link>
            <button
              type="submit"
              :disabled="form.processing"
              class="rounded-xl bg-blue-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-800 disabled:opacity-70"
            >
              {{ form.processing ? 'Saving...' : 'Save Changes' }}
            </button>
          </div>
        </form>
      </div>
    </section>
  </DashboardLayout>
</template>
