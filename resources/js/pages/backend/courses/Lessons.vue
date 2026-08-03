<template>
    <DashboardLayout>
        <div class="w-full">

            <!-- Breadcrumb -->
            <nav class="flex items-center gap-1.5 text-sm text-slate-400 dark:text-gray-500 mb-4">
                <span>{{ $t('Dashboard') }}</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="text-slate-600 dark:text-gray-300 font-medium">{{ $t('Lessons') }}</span>
            </nav>

            <PageHero
                eyebrow="Course Management"
                :title="$t('Lessons')"
                :description="$t('Read, create, update, and delete lesson records')"
                class="mb-6"
            />

            <!-- Card summary -->
            <div class="grid grid-cols-1 gap-3 mb-6 sm:grid-cols-2">
                <Card padding="px-4 py-3.5">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-gray-500">{{ $t('Total') }}</p>
                    <p class="mt-1 text-xl font-bold text-slate-900 dark:text-gray-100">{{ lessons.length }}</p>
                </Card>
                <Card padding="px-4 py-3.5">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-gray-500">{{ $t('Active') }}</p>
                    <p class="mt-1 text-xl font-bold text-emerald-600 dark:text-emerald-400">{{ activeCount }}</p>
                </Card>
            </div>

            <Card padding="p-0">
                <div class="border-b border-slate-200 px-6 py-5 dark:border-gray-800">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="min-w-0 shrink-0">
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400 dark:text-gray-500">{{ $t('Lesson Directory') }}</p>
                            <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">{{ $t('Read, create, update, and delete lesson records') }}</p>
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-3">
                            <div class="relative">
                                <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-gray-500" />
                                <input
                                    v-model="filters.search"
                                    type="text"
                                    :placeholder="$t('Search lessons...')"
                                    class="w-full rounded-xl border border-slate-300 py-2.5 pl-9 pr-3 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 sm:w-56 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                                >
                            </div>

                            <Link
                                href="/dashboard/course/lessons/create"
                                class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-blue-900 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-500"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                {{ $t('Lesson') }}
                            </Link>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <SelectSearch
                            v-model="filters.course_id"
                            :options="courseOptions"
                            :placeholder="$t('All Courses')"
                        />

                        <SelectSearch
                            v-model="filters.status"
                            :options="statusOptions"
                            :placeholder="$t('All Status')"
                        />
                    </div>

                    <div v-if="hasActiveFilters" class="mt-3 flex justify-end">
                        <button @click="resetFilters"
                            class="inline-flex items-center gap-1.5 text-sm font-medium text-blue-600 transition hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            {{ $t('Reset Filters') }}
                        </button>
                    </div>
                </div>

                <div class="relative">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-16">{{ $t('No') }}</TableHead>
                                <TableHead>{{ $t('Title') }}</TableHead>
                                <TableHead>{{ $t('Course') }}</TableHead>
                                <TableHead>{{ $t('Order') }}</TableHead>
                                <TableHead>{{ $t('Duration') }}</TableHead>
                                <TableHead>{{ $t('Status') }}</TableHead>
                                <TableHead class="text-right">{{ $t('Actions') }}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(lesson, index) in paginatedLessons" :key="lesson.id">
                                <TableCell class="text-sm text-slate-500 dark:text-gray-400">
                                    {{ (currentPage - 1) * perPage + index + 1 }}
                                </TableCell>
                                <TableCell>
                                    <p class="text-sm font-medium text-slate-900 dark:text-gray-100">{{ lesson.title }}</p>
                                    <p v-if="lesson.description" class="max-w-xs truncate text-xs text-slate-500 dark:text-gray-400">{{ lesson.description }}</p>
                                </TableCell>
                                <TableCell>
                                    <span class="text-sm text-slate-600 dark:text-gray-300">{{ lesson.course?.title }}</span>
                                </TableCell>
                                <TableCell>
                                    <span class="text-sm text-slate-600 dark:text-gray-300">{{ lesson.order_number || 0 }}</span>
                                </TableCell>
                                <TableCell>
                                    <span class="text-sm text-slate-600 dark:text-gray-300">{{ lesson.duration || 0 }} {{ $t('min') }}</span>
                                </TableCell>
                                <TableCell>
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="lesson.status === 'active' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' : 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400'">
                                        {{ lesson.status === 'active' ? $t('Active') : $t('Inactive') }}
                                    </span>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex justify-end gap-2">
                                        <Link
                                            :href="`/dashboard/course/lessons/${lesson.id}/edit`"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600 transition hover:bg-amber-100 dark:bg-amber-500/10 dark:text-amber-400 dark:hover:bg-amber-500/20"
                                            :title="$t('Edit')"
                                            :aria-label="$t('Edit lesson')"
                                        >
                                            <Pencil class="h-4 w-4" />
                                        </Link>

                                        <button
                                            type="button"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-600 transition hover:bg-red-100 dark:bg-red-500/10 dark:text-red-400 dark:hover:bg-red-500/20"
                                            :title="$t('Delete')"
                                            :aria-label="$t('Delete lesson')"
                                            @click="confirmDelete(lesson)"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </div>
                                </TableCell>
                            </TableRow>

                            <!-- Empty state: no lessons exist at all -->
                            <TableRow v-if="paginatedLessons.length === 0 && lessons.length === 0">
                                <TableCell colspan="7" class="px-4 py-16 text-center">
                                    <svg class="w-12 h-12 mx-auto text-slate-300 dark:text-gray-700 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p class="text-sm font-medium text-slate-600 dark:text-gray-300">{{ $t('No lessons yet') }}</p>
                                    <p class="text-xs text-slate-400 dark:text-gray-500 mt-1">{{ $t('Create your first lesson to get started') }}</p>
                                    <Link href="/dashboard/course/lessons/create"
                                        class="inline-flex items-center gap-1.5 mt-4 text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                                        + {{ $t('Lesson') }}
                                    </Link>
                                </TableCell>
                            </TableRow>

                            <!-- Empty state: filters found nothing -->
                            <TableRow v-else-if="paginatedLessons.length === 0">
                                <TableCell colspan="7" class="px-4 py-16 text-center">
                                    <svg class="w-12 h-12 mx-auto text-slate-300 dark:text-gray-700 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    <p class="text-sm font-medium text-slate-600 dark:text-gray-300">{{ $t('No lessons match your filters') }}</p>
                                    <p class="text-xs text-slate-400 dark:text-gray-500 mt-1">{{ $t('Try adjusting or clearing your filters') }}</p>
                                    <button @click="resetFilters"
                                        class="inline-flex items-center gap-1.5 mt-4 text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                                        {{ $t('Reset Filters') }}
                                    </button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Pagination -->
                <div v-if="filteredLessons.length > 0"
                    class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800 dark:bg-gray-800/40">
                    <p class="text-sm text-slate-500 dark:text-gray-400">
                        {{ $t('Showing :from to :to of :total lessons', { from: rangeStart, to: rangeEnd, total: filteredLessons.length }) }}
                    </p>

                    <Pagination
                        :current-page="currentPage"
                        :last-page="totalPages"
                        @page-change="goToPage"
                    />
                </div>
            </Card>
        </div>

        <!-- Delete Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-[2px]"
            @click.self="showDeleteModal = false">
            <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 max-w-md w-full mx-4 shadow-xl">
                <div class="flex items-start gap-4 mb-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-rose-50 dark:bg-rose-500/10 flex items-center justify-center">
                        <svg class="w-5 h-5 text-rose-600 dark:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-gray-100">{{ $t('Delete lesson') }}</h3>
                        <p class="text-sm text-slate-600 dark:text-gray-400 mt-1">
                            {{ $t('Are you sure you want to delete') }} "<span class="font-medium text-slate-900 dark:text-gray-100">{{ deleteItem?.title }}</span>"?
                            {{ $t('This action cannot be undone.') }}
                        </p>
                    </div>
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-100 dark:border-gray-800 pt-4">
                    <button @click="showDeleteModal = false"
                        class="px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:text-gray-200 dark:hover:bg-gray-800 rounded-xl transition">
                        {{ $t('Cancel') }}
                    </button>
                    <button @click="deleteLesson"
                        class="px-4 py-2 text-sm font-medium text-white bg-rose-600 hover:bg-rose-700 dark:bg-rose-600 dark:hover:bg-rose-500 rounded-xl transition">
                        {{ $t('Delete lesson') }}
                    </button>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { Pencil, Search, Trash2 } from '@lucide/vue';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Card } from '@/components/ui/card';
import { PageHero } from '@/components/ui/page-hero';
import { Table, TableHeader, TableBody, TableCell, TableHead, TableRow } from '@/components/ui/table';
import { Pagination } from '@/components/ui/pagination';
import { SelectSearch } from '@/components/ui/select-search';

const props = defineProps({
    lessons: {
        type: Array,
        default: () => []
    },
    allCourses: {
        type: Array,
        default: () => []
    }
});

// Filters state
const filters = ref({
    search: '',
    course_id: '',
    status: ''
});

// Pagination
const currentPage = ref(1);
const perPage = ref(10);

// Check if any filters are active
const hasActiveFilters = computed(() => {
    return filters.value.search ||
           filters.value.course_id ||
           filters.value.status;
});

// Filtered lessons
const filteredLessons = ref([]);

// Stats
const activeCount = computed(() =>
    props.lessons.filter(l => l.status === 'active').length
);

// Total pages
const totalPages = computed(() => {
    return Math.ceil(filteredLessons.value.length / perPage.value) || 1;
});

// Paginated lessons
const paginatedLessons = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    const end = start + perPage.value;
    return filteredLessons.value.slice(start, end);
});

// Options for the searchable filter dropdowns
const courseOptions = computed(() => [
    { label: 'All Courses', value: '' },
    ...props.allCourses.map(course => ({ label: course.title, value: String(course.id) }))
]);

const statusOptions = computed(() => [
    { label: 'All Status', value: '' },
    { label: 'Active', value: 'active' },
    { label: 'Inactive', value: 'inactive' }
]);

// Apply filters
const applyFilters = () => {
    let filtered = [...props.lessons];

    // Search filter
    if (filters.value.search) {
        const searchTerm = filters.value.search.toLowerCase();
        filtered = filtered.filter(lesson =>
            lesson.title.toLowerCase().includes(searchTerm) ||
            (lesson.description && lesson.description.toLowerCase().includes(searchTerm))
        );
    }

    // Course filter
    if (filters.value.course_id) {
        filtered = filtered.filter(lesson =>
            lesson.course_id === parseInt(filters.value.course_id)
        );
    }

    // Status filter
    if (filters.value.status) {
        filtered = filtered.filter(lesson =>
            lesson.status === filters.value.status
        );
    }

    filteredLessons.value = filtered;
    currentPage.value = 1;
};

// Navigation methods
const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

const resetPagination = () => {
    currentPage.value = 1;
};

// Range shown in the "Showing X to Y of Z" strip below the table
const rangeStart = computed(() => {
    if (filteredLessons.value.length === 0) return 0;
    return (currentPage.value - 1) * perPage.value + 1;
});

const rangeEnd = computed(() => {
    return Math.min(currentPage.value * perPage.value, filteredLessons.value.length);
});

// Watch for changes
watch([() => props.lessons, filters], () => {
    applyFilters();
}, { deep: true, immediate: true });

// Reset filters
const resetFilters = () => {
    filters.value = {
        search: '',
        course_id: '',
        status: ''
    };
};

const showDeleteModal = ref(false);
const deleteItem = ref(null);

const confirmDelete = (lesson) => {
    deleteItem.value = lesson;
    showDeleteModal.value = true;
};

const deleteLesson = () => {
    if (deleteItem.value) {
        router.delete(`/dashboard/course/lessons/${deleteItem.value.id}`, {
            onSuccess: () => {
                showDeleteModal.value = false;
                deleteItem.value = null;
            },
            onError: (errors) => {
                alert(errors.message || 'Failed to delete lesson');
            }
        });
    }
};
</script>
