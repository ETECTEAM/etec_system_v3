<script setup>
import { ref, computed, watch, onBeforeUnmount } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Breadcrumbs } from '@/components/ui/breadcrumbs';
import { PageHero } from '@/components/ui/page-hero';
import { Card } from '@/components/ui/card';
import { ActionMenu } from '@/components/ui/menu';
import { Pagination } from '@/components/ui/pagination';

const props = defineProps({
  classLists: Object,
  filters: Object,
  classTypes: Array,
  terms: Array,
  times: Array,
});

const search = ref(props.filters?.search ?? '');
const selectedType = ref(props.filters?.class_type ?? 'all');
const selectedTerm = ref(props.filters?.term ?? 'all');
const selectedTime = ref(props.filters?.time ?? 'all');
const selectedStatus = ref(props.filters?.status ?? 'all');
const searchTimer = ref(null);

const filteredData = computed(() => props.classLists?.data || []);

const classTypeOptions = computed(() => [
  'all',
  ...props.classTypes.map((item) => item.type_name),
]);

const termOptions = computed(() => [
  'all',
  ...props.terms.map((item) => item.term_name),
]);

const timeOptions = computed(() => [
  'all',
  ...props.times.map((item) => item.time_name),
]);

const paginationParams = () => ({
  search: search.value,
  status: selectedStatus.value,
  class_type: selectedType.value,
  term: selectedTerm.value,
  time: selectedTime.value,
});

const applyFilters = () => {
  router.get('/dashboard/class-list', paginationParams(), {
    preserveState: true,
    preserveScroll: true,
  });
};

const debounceSearch = () => {
  if (searchTimer.value) {
    clearTimeout(searchTimer.value);
  }
  searchTimer.value = window.setTimeout(() => {
    applyFilters();
  }, 300);
};

onBeforeUnmount(() => {
  if (searchTimer.value) {
    clearTimeout(searchTimer.value);
  }
});

const totalCount = computed(() => props.classLists?.total ?? 0);

const breadcrumbItems = [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Class List', current: true },
];

const deleteItem = (item) => {
  if (!confirm('Are you sure you want to delete this class?')) {
    return;
  }

  router.delete(`/dashboard/class-list/${item.id ?? item.class_list_id}`, {
    preserveScroll: true,
    onError: () => {
      alert('Unable to delete this class list entry.');
    },
  });
};

const handleAction = (action, item) => {
  switch (action) {
    case 'view':
      router.visit(`/dashboard/class-list/${item.id ?? item.class_list_id}`);
      break;
    case 'edit':
      router.visit(`/dashboard/class-list/${item.id ?? item.class_list_id}/edit`);
      break;
    case 'delete':
      deleteItem(item);
      break;
  }
};

const handlePageChange = (page) => {
  router.get('/dashboard/class-list', {
    search: search.value,
    status: selectedStatus.value,
    class_type: selectedType.value,
    term: selectedTerm.value,
    time: selectedTime.value,
    page,
  }, {
    preserveState: true,
    preserveScroll: true,
  });
};
</script>

<template>
  <Head title="Class List" />
  <DashboardLayout>
    <section class="space-y-6">
      <Breadcrumbs :items="breadcrumbItems" />

      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <PageHero
          eyebrow="Management"
          title="Class List"
          description="Manage your scheduled classes, instructors, rooms, and time slots."
        />

        <div class="flex flex-col items-start gap-3 sm:items-end">
          <div class="rounded-3xl border border-slate-200 bg-slate-50 px-5 py-3 text-sm font-semibold text-slate-800">
            Total Class: {{ totalCount }}
          </div>
          <!-- <Link
            href="/dashboard/class-list/create"
            class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700"
          >
            + Add New Class
          </Link> -->
        </div>
      </div>

      <Card class="p-6">
        <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_200px] xl:grid-cols-[minmax(0,1fr)_repeat(4,200px)]">
          <div>
            <input
              v-model="search"
              @input="debounceSearch"
              @keyup.enter.prevent="applyFilters"
              type="search"
              placeholder="Search by class, instructor, or time"
              class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
            />
          </div>

          <select
            v-model="selectedType"
            @change="applyFilters"
            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
          >
            <option value="all">Type</option>
            <option v-for="type in classTypeOptions" :key="type" :value="type">{{ type }}</option>
          </select>

          <select
            v-model="selectedTerm"
            @change="applyFilters"
            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
          >
            <option value="all">Term</option>
            <option v-for="term in termOptions" :key="term" :value="term">{{ term }}</option>
          </select>

          <select
            v-model="selectedTime"
            @change="applyFilters"
            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
          >
            <option value="all">Time</option>
            <option v-for="time in timeOptions" :key="time" :value="time">{{ time }}</option>
          </select>

          <select
            v-model="selectedStatus"
            @change="applyFilters"
            class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 outline-none focus:border-blue-900 focus:ring-2 focus:ring-blue-100"
          >
            <option value="all">Status</option>
            <option value="progress">progress</option>
            <option value="completed">completed</option>
            <option value="cancelled">cancelled</option>
          </select>
        </div>
      </Card>

      <Card class="overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full text-left text-sm text-slate-700">
            <thead class="bg-slate-100 text-slate-700 uppercase text-xs tracking-wider">
              <tr>
                <th class="px-4 py-4">#</th>
                <th class="px-4 py-4">Class ID</th>
                <th class="px-4 py-4">Teacher</th>
                <th class="px-4 py-4">Course / Lesson</th>
                <th class="px-4 py-4">Term & Time</th>
                <th class="px-4 py-4">Building Floor & Room</th>
                <th class="px-4 py-4">Students</th>
                <th class="px-4 py-4">Status</th>
                <th class="px-4 py-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in filteredData" :key="item.id ?? item.class_list_id" class="border-t border-slate-200 hover:bg-slate-50">
                <td class="px-4 py-4 font-semibold text-slate-900">{{ index + 1 }}</td>
                <td class="px-4 py-4">
                  <span class="inline-flex rounded-full bg-blue-800 px-3 py-1 text-xs font-semibold text-white">ID: {{ item.id ?? item.class_list_id }}</span>
                </td>
                <td class="px-4 py-4">
                  <div class="flex items-center gap-2">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-blue-50 text-blue-700">👤</span>
                    <span class="font-medium text-slate-900">{{ item.teacher?.name || item.teacher?.teacher_name || 'No teacher' }}</span>
                  </div>
                </td>
                <td class="px-4 py-4">
                  <div class="font-semibold text-slate-900">{{ item.course?.title || 'No course' }}</div>
                  <div class="mt-1 text-xs text-slate-500">Lesson: <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">{{ item.lesson?.title || 'No lesson' }}</span></div>
                  <div class="mt-1 text-xs font-semibold text-emerald-700">Type: {{ item.class_type?.type_name || 'Unknown' }}</div>
                </td>
                <td class="px-4 py-4">
                  <div class="font-semibold text-slate-900">{{ item.term?.term_name || 'No term' }}</div>
                  <div class="mt-1 text-xs text-slate-500">{{ item.time?.time_name || 'No time' }}</div>
                </td>
                <td class="px-4 py-4">
                  <div class="font-semibold text-slate-900">{{ item.building?.name || 'No building' }}</div>
                  <div class="mt-1 text-xs text-slate-500">{{ item.floor?.name || 'No floor' }} · {{ item.room?.room_number || 'No room' }}</div>
                </td>
                <td class="px-4 py-4 font-semibold text-slate-900">{{ item.student_count ?? 0 }}</td>
                <td class="px-4 py-4">
                  <span :class="[
                    'inline-flex rounded-full px-3 py-1 text-[11px] font-semibold',
                    item.status === 'completed' ? 'bg-emerald-50 text-emerald-700' : '',
                    item.status === 'progress' ? 'bg-blue-50 text-blue-700' : '',
                    item.status === 'cancelled' ? 'bg-rose-50 text-rose-700' : '',
                  ]">
                    {{ item.status ? item.status.charAt(0).toUpperCase() + item.status.slice(1) : 'Unknown' }}
                  </span>
                </td>
                <td class="px-4 py-4 text-right">
                  <ActionMenu
                    :items="[
                      { key: 'view', label: 'View' },
                      { key: 'edit', label: 'Edit' },
                      { key: 'delete', label: 'Delete'},
                    ]"
                    @select="(action) => handleAction(action.key, item)"
                  />
                </td>
              </tr>

              <tr v-if="filteredData.length === 0">
                <td colspan="9" class="px-4 py-12 text-center text-slate-500">No classes found.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
          <p class="text-sm text-slate-500">
            Showing {{ classLists.from ?? 0 }}–{{ classLists.to ?? 0 }} of {{ classLists.total ?? 0 }} entries
          </p>

          <Pagination
            v-if="classLists?.links?.length"
            :current-page="classLists.current_page"
            :last-page="classLists.last_page"
            :disabled="false"
            @page-change="handlePageChange"
          />
        </div>
      </Card>
    </section>
  </DashboardLayout>
</template>
