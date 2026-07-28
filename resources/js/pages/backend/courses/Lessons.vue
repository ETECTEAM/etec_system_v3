<template>
    <DashboardLayout>
        <div class="w-full">

            <!-- Breadcrumb -->
            <nav class="flex items-center gap-1.5 text-sm text-slate-400 mb-4">
                <span>{{ $t('Dashboard') }}</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span>{{ $t('Course') }}</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="text-slate-600 font-medium">{{ $t('Lessons') }}</span>
            </nav>

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-blue-600 flex items-center justify-center shadow-sm shadow-blue-200 shrink-0">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ $t('Lessons') }}</h1>
                        <p class="text-sm text-slate-500 mt-0.5">{{ $t('Read, create, update, and delete lesson records') }}</p>
                    </div>
                </div>

                <Link href="/dashboard/course/lessons/create"
                    class="inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl font-medium text-sm shadow-sm shadow-blue-200 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ $t('Add Lesson') }}
                </Link>
            </div>

            <!-- Stats strip -->
            <div class="grid grid-cols-2 gap-3 mb-6">
                <div class="bg-white rounded-xl border border-slate-200 px-4 py-3.5">
                    <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">{{ $t('Total') }}</p>
                    <p class="text-xl font-bold text-slate-900 mt-1">{{ lessons.length }}</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 px-4 py-3.5">
                    <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">{{ $t('Active') }}</p>
                    <p class="text-xl font-bold text-emerald-600 mt-1">{{ activeCount }}</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white border border-slate-200 rounded-xl p-4 mb-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- Search -->
                <div class="relative">
                    <input v-model="filters.search" type="text" :placeholder="$t('Search lessons...')"
                        class="w-full rounded-lg border border-slate-200 pl-9 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        @input="applyFilters" />
                    <svg class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <!-- Course Filter -->
                <select v-model="filters.course_id"
                    class="w-full rounded-lg border border-slate-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                    @change="applyFilters">
                    <option value="">{{ $t('All Courses') }}</option>
                    <option v-for="course in allCourses" :key="course.id" :value="course.id">{{ course.title }}</option>
                </select>

                <!-- Status Filter -->
                <select v-model="filters.status"
                    class="w-full rounded-lg border border-slate-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                    @change="applyFilters">
                    <option value="">{{ $t('All Status') }}</option>
                    <option value="active">{{ $t('Active') }}</option>
                    <option value="inactive">{{ $t('Inactive') }}</option>
                </select>

                <!-- Reset Button -->
                <button v-if="hasActiveFilters" @click="resetFilters"
                    class="px-4 py-2 text-sm font-medium text-blue-600 hover:text-blue-700 border border-blue-200 hover:border-blue-300 rounded-lg transition flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    {{ $t('Reset Filters') }}
                </button>
            </div>

            <!-- Results Count -->
            <div class="mb-3 text-sm text-slate-500">
                {{ $t('Showing') }} <strong class="text-slate-700">{{ paginatedLessons.length }}</strong> {{ $t('of') }}
                <strong class="text-slate-700">{{ filteredLessons.length }}</strong> {{ $t('lessons') }}
            </div>

            <!-- Lessons Table -->
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[640px]">
                        <thead class="bg-slate-50/80 border-b border-slate-200">
                            <tr>
                                <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 px-4 py-3.5 w-12">{{ $t('No') }}</th>
                                <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 px-4 py-3.5">{{ $t('Title') }}</th>
                                <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 px-4 py-3.5">{{ $t('Course') }}</th>
                                <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 px-4 py-3.5">{{ $t('Order') }}</th>
                                <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 px-4 py-3.5">{{ $t('Duration') }}</th>
                                <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 px-4 py-3.5">{{ $t('Status') }}</th>
                                <th class="text-right text-xs font-semibold uppercase tracking-wider text-slate-500 px-4 py-3.5">{{ $t('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="(lesson, index) in paginatedLessons" :key="lesson.id" class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-4 py-3.5 text-sm text-slate-500">{{ (currentPage - 1) * perPage + index + 1 }}</td>
                                <td class="px-4 py-3.5">
                                    <p class="text-sm font-medium text-slate-900">{{ lesson.title }}</p>
                                    <p v-if="lesson.description" class="text-xs text-slate-500 truncate max-w-xs">{{ lesson.description }}</p>
                                </td>
                                <td class="px-4 py-3.5 text-sm text-slate-600">{{ lesson.course?.title }}</td>
                                <td class="px-4 py-3.5 text-sm text-slate-600">{{ lesson.order_number || 0 }}</td>
                                <td class="px-4 py-3.5 text-sm text-slate-600">{{ lesson.duration || 0 }} {{ $t('min') }}</td>
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex items-center gap-1.5 text-xs font-medium"
                                        :class="lesson.status === 'active' ? 'text-emerald-700' : 'text-rose-700'">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="lesson.status === 'active' ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                                        {{ lesson.status === 'active' ? $t('Active') : $t('Inactive') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link :href="`/dashboard/course/lessons/${lesson.id}/edit`"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 transition">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            {{ $t('Edit') }}
                                        </Link>
                                        <button @click="confirmDelete(lesson)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-rose-700 bg-rose-50 hover:bg-rose-100 transition">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            {{ $t('Delete') }}
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty state: no lessons exist at all -->
                            <tr v-if="paginatedLessons.length === 0 && lessons.length === 0">
                                <td colspan="7" class="px-4 py-16 text-center">
                                    <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p class="text-sm font-medium text-slate-600">{{ $t('No lessons yet') }}</p>
                                    <p class="text-xs text-slate-400 mt-1">{{ $t('Create your first lesson to get started') }}</p>
                                    <Link href="/dashboard/course/lessons/create"
                                        class="inline-flex items-center gap-1.5 mt-4 text-sm font-medium text-blue-600 hover:text-blue-700">
                                        + {{ $t('Add Lesson') }}
                                    </Link>
                                </td>
                            </tr>

                            <!-- Empty state: filters found nothing -->
                            <tr v-else-if="paginatedLessons.length === 0">
                                <td colspan="7" class="px-4 py-16 text-center">
                                    <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    <p class="text-sm font-medium text-slate-600">{{ $t('No lessons match your filters') }}</p>
                                    <p class="text-xs text-slate-400 mt-1">{{ $t('Try adjusting or clearing your filters') }}</p>
                                    <button @click="resetFilters" class="inline-flex items-center gap-1.5 mt-4 text-sm font-medium text-blue-600 hover:text-blue-700">
                                        {{ $t('Reset Filters') }}
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <Pagination :current-page="currentPage" :total-pages="totalPages" :total-items="filteredLessons.length"
                :per-page="perPage" item-label="lessons" @update:current-page="goToPage"
                @update:per-page="onPerPageChange" />
        </div>

        <!-- Delete Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-[2px]"
            @click.self="showDeleteModal = false">
            <div class="bg-white rounded-2xl p-6 max-w-md w-full mx-4 shadow-xl">
                <div class="flex items-start gap-4 mb-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-rose-50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">{{ $t('Delete lesson') }}</h3>
                        <p class="text-sm text-slate-600 mt-1">
                            {{ $t('Are you sure you want to delete') }} "<span class="font-medium text-slate-900">{{ deleteItem?.title }}</span>"?
                            {{ $t('This action cannot be undone.') }}
                        </p>
                    </div>
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-100 pt-4">
                    <button @click="showDeleteModal = false"
                        class="px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 rounded-xl transition">
                        {{ $t('Cancel') }}
                    </button>
                    <button @click="deleteLesson"
                        class="px-4 py-2 text-sm font-medium text-white bg-rose-600 hover:bg-rose-700 rounded-xl transition">
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
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import Pagination from './Pagination.vue';

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

const onPerPageChange = (value) => {
    perPage.value = value;
    resetPagination();
};

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
