<template>
    <DashboardLayout>
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Lessons</h1>
                    <p class="text-sm text-slate-500">Manage course lessons</p>
                </div>
                <Link
                    href="/dashboard/course/lessons/create"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Lesson
                </Link>
            </div>

            <!-- Filters - One Row 4 Columns -->
            <div class="mb-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- Search -->
                <div class="relative">
                    <input
                        v-model="filters.search"
                        type="text"
                        placeholder="Search lessons..."
                        class="w-full rounded-lg border border-slate-200 pl-9 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        @input="applyFilters"
                    />
                    <svg class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>

                <!-- Course Filter -->
                <select
                    v-model="filters.course_id"
                    class="w-full rounded-lg border border-slate-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                    @change="applyFilters"
                >
                    <option value="">All Courses</option>
                    <option v-for="course in allCourses" :key="course.id" :value="course.id">
                        {{ course.title }}
                    </option>
                </select>

                <!-- Status Filter -->
                <select
                    v-model="filters.status"
                    class="w-full rounded-lg border border-slate-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                    @change="applyFilters"
                >
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>

                <!-- Reset Button -->
                <button
                    v-if="hasActiveFilters"
                    @click="resetFilters"
                    class="px-4 py-2 text-sm font-medium text-blue-600 hover:text-blue-800 border border-blue-200 hover:border-blue-300 rounded-lg transition flex items-center justify-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Reset Filters
                </button>
            </div>

            <!-- Results Count -->
            <div class="mb-4 text-sm text-slate-500">
                Showing <strong class="text-slate-700">{{ paginatedLessons.length }}</strong> of 
                <strong class="text-slate-700">{{ filteredLessons.length }}</strong> lessons
            </div>

            <!-- Lessons Table -->
            <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 px-4 py-3">#</th>
                            <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 px-4 py-3">Title</th>
                            <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 px-4 py-3">Course</th>
                            <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 px-4 py-3">Order</th>
                            <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 px-4 py-3">Duration</th>
                            <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 px-4 py-3">Status</th>
                            <th class="text-right text-xs font-semibold uppercase tracking-wider text-slate-500 px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr v-for="(lesson, index) in paginatedLessons" :key="lesson.id" class="hover:bg-slate-50 transition">
                            <td class="px-4 py-3 text-sm text-slate-600">{{ (currentPage - 1) * perPage + index + 1 }}</td>
                            <td class="px-4 py-3">
                                <div>
                                    <p class="text-sm font-medium text-slate-900">{{ lesson.title }}</p>
                                    <p v-if="lesson.description" class="text-xs text-slate-500 line-clamp-1">{{ lesson.description }}</p>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ lesson.course?.title }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ lesson.order_number || 0 }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ lesson.duration || 0 }} min</td>
                            <td class="px-4 py-3">
                                <span
                                    :class="[
                                        'px-2 py-1 text-xs font-medium rounded-full',
                                        lesson.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
                                    ]"
                                >
                                    {{ lesson.status === 'active' ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <Link
                                        :href="`/dashboard/course/lessons/${lesson.id}/edit`"
                                        class="text-blue-600 hover:text-blue-800 transition p-1.5 rounded-lg hover:bg-blue-50"
                                        title="Edit"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </Link>
                                    <button @click="confirmDelete(lesson)" class="text-red-600 hover:text-red-800 transition p-1.5 rounded-lg hover:bg-red-50" title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="paginatedLessons.length === 0">
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500">
                                No lessons found.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="filteredLessons.length > 0" class="mt-6 flex flex-wrap items-center justify-between gap-4">
                <div class="text-sm text-slate-500">
                    Showing 
                    <span class="font-medium text-slate-700">{{ (currentPage - 1) * perPage + 1 }}</span> 
                    to 
                    <span class="font-medium text-slate-700">{{ Math.min(currentPage * perPage, filteredLessons.length) }}</span> 
                    of 
                    <span class="font-medium text-slate-700">{{ filteredLessons.length }}</span> 
                    results
                </div>
                
                <div class="flex items-center gap-2">
                    <button
                        @click="prevPage"
                        :disabled="currentPage === 1"
                        :class="[
                            'px-3 py-2 rounded-lg text-sm font-medium transition',
                            currentPage === 1 
                                ? 'bg-slate-100 text-slate-400 cursor-not-allowed' 
                                : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50'
                        ]"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>
                    
                    <!-- Page Numbers -->
                    <div class="flex items-center gap-1">
                        <button
                            v-for="page in visiblePages"
                            :key="page"
                            @click="goToPage(page)"
                            :class="[
                                'px-3 py-2 rounded-lg text-sm font-medium transition min-w-[36px]',
                                page === currentPage
                                    ? 'bg-blue-600 text-white'
                                    : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50'
                            ]"
                        >
                            {{ page }}
                        </button>
                    </div>
                    
                    <button
                        @click="nextPage"
                        :disabled="currentPage === totalPages"
                        :class="[
                            'px-3 py-2 rounded-lg text-sm font-medium transition',
                            currentPage === totalPages 
                                ? 'bg-slate-100 text-slate-400 cursor-not-allowed' 
                                : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50'
                        ]"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                    
                    <!-- Per Page Selector -->
                    <select
                        v-model="perPage"
                        class="ml-2 rounded-lg border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                        @change="resetPagination"
                    >
                        <option :value="5">5</option>
                        <option :value="10">10</option>
                        <option :value="25">25</option>
                        <option :value="50">50</option>
                        <option :value="100">100</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Delete Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="showDeleteModal = false">
            <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold text-slate-900 mb-2">Delete Lesson</h3>
                <p class="text-sm text-slate-600 mb-6">
                    Are you sure you want to delete "{{ deleteItem?.title }}"? This action cannot be undone.
                </p>
                <div class="flex justify-end gap-3">
                    <button @click="showDeleteModal = false" class="px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 rounded-lg transition">
                        Cancel
                    </button>
                    <button @click="deleteLesson" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition">
                        Delete
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

// Visible page numbers
const visiblePages = computed(() => {
    const total = totalPages.value;
    const current = currentPage.value;
    const delta = 2;
    const range = [];
    
    for (let i = 1; i <= total; i++) {
        if (
            i === 1 ||
            i === total ||
            Math.abs(i - current) <= delta
        ) {
            range.push(i);
        }
    }
    
    const result = [];
    let prev = 0;
    for (const page of range) {
        if (page - prev > 1) {
            result.push('...');
        }
        result.push(page);
        prev = page;
    }
    
    return result;
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
    if (page === '...') return;
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

const prevPage = () => {
    if (currentPage.value > 1) {
        currentPage.value--;
    }
};

const nextPage = () => {
    if (currentPage.value < totalPages.value) {
        currentPage.value++;
    }
};

const resetPagination = () => {
    currentPage.value = 1;
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