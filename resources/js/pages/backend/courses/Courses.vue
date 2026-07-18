<template>
    <DashboardLayout>
        <div class="w-full">

            <!-- Breadcrumb -->
            <nav class="flex items-center gap-1.5 text-sm text-slate-400 mb-4">
                <span>Dashboard</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span>Course</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="text-slate-600 font-medium">Courses</span>
            </nav>

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-blue-600 flex items-center justify-center shadow-sm shadow-blue-200 shrink-0">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s4.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Courses</h1>
                        <p class="text-sm text-slate-500 mt-0.5">Read, create, update, and delete course records</p>
                    </div>
                </div>

                <Link href="/dashboard/course/courses/create"
                    class="inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl font-medium text-sm shadow-sm shadow-blue-200 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Course
                </Link>
            </div>

            <!-- Stats strip -->
            <div class="grid grid-cols-3 gap-3 mb-6">
                <div class="bg-white rounded-xl border border-slate-200 px-4 py-3.5">
                    <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Total</p>
                    <p class="text-xl font-bold text-slate-900 mt-1">{{ courses.length }}</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 px-4 py-3.5">
                    <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">Active</p>
                    <p class="text-xl font-bold text-emerald-600 mt-1">{{ activeCount }}</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 px-4 py-3.5">
                    <p class="text-xs font-medium text-slate-400 uppercase tracking-wide">With Certificate</p>
                    <p class="text-xl font-bold text-slate-900 mt-1">{{ certificateCount }}</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white border border-slate-200 rounded-xl p-4 mb-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- Search -->
                <div class="relative">
                    <input v-model="filters.search" type="text" placeholder="Search courses..."
                        class="w-full rounded-lg border border-slate-200 pl-9 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        @input="applyFilters" />
                    <svg class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <!-- Category Filter -->
                <select v-model="filters.category_id"
                    class="w-full rounded-lg border border-slate-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                    @change="applyFilters">
                    <option value="">All Categories</option>
                    <option v-for="cat in allCategories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                </select>

                <!-- Sub Category Filter -->
                <select v-model="filters.sub_category_id"
                    class="w-full rounded-lg border border-slate-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                    @change="applyFilters">
                    <option value="">All Sub Categories</option>
                    <option v-for="sub in filteredSubCategories" :key="sub.id" :value="sub.id">{{ sub.name }}</option>
                </select>

                <!-- Track Filter -->
                <select v-model="filters.track_id"
                    class="w-full rounded-lg border border-slate-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                    @change="applyFilters">
                    <option value="">All Tracks</option>
                    <option v-for="track in filteredTracks" :key="track.id" :value="track.id">{{ track.name }}</option>
                </select>
            </div>

            <!-- Results Count & Reset -->
            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                <span class="text-sm text-slate-500">
                    Showing <strong class="text-slate-700">{{ paginatedCourses.length }}</strong> of
                    <strong class="text-slate-700">{{ filteredCourses.length }}</strong> courses
                </span>
                <button v-if="hasActiveFilters" @click="resetFilters"
                    class="text-sm text-blue-600 hover:text-blue-700 font-medium transition flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Reset Filters
                </button>
            </div>

            <!-- Courses Table -->
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50/80 border-b border-slate-200">
                            <tr>
                                <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 px-4 py-3.5 w-12">No</th>
                                <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 px-4 py-3.5">Title</th>
                                <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 px-4 py-3.5">Category</th>
                                <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 px-4 py-3.5">Sub Category</th>
                                <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 px-4 py-3.5">Track</th>
                                <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 px-4 py-3.5">Price</th>
                                <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 px-4 py-3.5">Certificate</th>
                                <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 px-4 py-3.5">Status</th>
                                <th class="text-right text-xs font-semibold uppercase tracking-wider text-slate-500 px-4 py-3.5">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="(course, index) in paginatedCourses" :key="course.id" class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-4 py-3.5 text-sm text-slate-500">{{ (currentPage - 1) * perPage + index + 1 }}</td>
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-slate-100 overflow-hidden flex-shrink-0">
                                            <img v-if="course.thumbnail" :src="`/storage/${course.thumbnail}`" :alt="course.title"
                                                class="w-full h-full object-cover" @error="handleImageError" />
                                            <div v-else class="w-full h-full flex items-center justify-center text-slate-400">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-slate-900 truncate">{{ course.title }}</p>
                                            <p class="text-xs text-slate-500 truncate">{{ course.slug }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 text-sm text-slate-600">{{ course.track?.sub_category?.category?.name || 'N/A' }}</td>
                                <td class="px-4 py-3.5 text-sm text-slate-600">{{ course.track?.sub_category?.name || 'N/A' }}</td>
                                <td class="px-4 py-3.5 text-sm text-slate-600">{{ course.track?.name || 'N/A' }}</td>
                                <td class="px-4 py-3.5 text-sm font-semibold text-slate-900">${{ parseFloat(course.price || 0).toFixed(2) }}</td>
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium"
                                        :class="course.certificate_available ? 'bg-violet-50 text-violet-700' : 'bg-slate-100 text-slate-500'">
                                        {{ course.certificate_available ? 'Yes' : 'No' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex items-center gap-1.5 text-xs font-medium"
                                        :class="course.status === 'active' ? 'text-emerald-700' : 'text-rose-700'">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="course.status === 'active' ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                                        {{ course.status === 'active' ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link :href="`/dashboard/course/courses/${course.id}/edit`"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 transition">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            Edit
                                        </Link>
                                        <button @click="confirmDelete(course)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-rose-700 bg-rose-50 hover:bg-rose-100 transition">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty state: no courses exist at all -->
                            <tr v-if="paginatedCourses.length === 0 && courses.length === 0">
                                <td colspan="9" class="px-4 py-16 text-center">
                                    <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s4.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                    <p class="text-sm font-medium text-slate-600">No courses yet</p>
                                    <p class="text-xs text-slate-400 mt-1">Create your first course to get started</p>
                                    <Link href="/dashboard/course/courses/create"
                                        class="inline-flex items-center gap-1.5 mt-4 text-sm font-medium text-blue-600 hover:text-blue-700">
                                        + Add Course
                                    </Link>
                                </td>
                            </tr>

                            <!-- Empty state: filters found nothing -->
                            <tr v-else-if="paginatedCourses.length === 0">
                                <td colspan="9" class="px-4 py-16 text-center">
                                    <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    <p class="text-sm font-medium text-slate-600">No courses match your filters</p>
                                    <p class="text-xs text-slate-400 mt-1">Try adjusting or clearing your filters</p>
                                    <button @click="resetFilters" class="inline-flex items-center gap-1.5 mt-4 text-sm font-medium text-blue-600 hover:text-blue-700">
                                        Reset Filters
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <Pagination :current-page="currentPage" :total-pages="totalPages" :total-items="filteredCourses.length"
                :per-page="perPage" item-label="courses" @update:current-page="goToPage"
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
                        <h3 class="text-lg font-semibold text-slate-900">Delete course</h3>
                        <p class="text-sm text-slate-600 mt-1">
                            Are you sure you want to delete "<span class="font-medium text-slate-900">{{ deleteItem?.title }}</span>"?
                            This action cannot be undone.
                        </p>
                    </div>
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-100 pt-4">
                    <button @click="showDeleteModal = false"
                        class="px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 rounded-xl transition">
                        Cancel
                    </button>
                    <button @click="deleteCourse"
                        class="px-4 py-2 text-sm font-medium text-white bg-rose-600 hover:bg-rose-700 rounded-xl transition">
                        Delete course
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
    courses: {
        type: Array,
        default: () => []
    },
    allCategories: {
        type: Array,
        default: () => []
    },
    allSubCategories: {
        type: Array,
        default: () => []
    },
    allTracks: {
        type: Array,
        default: () => []
    }
});

// Filters state
const filters = ref({
    search: '',
    category_id: '',
    sub_category_id: '',
    track_id: ''
});

// Check if any filters are active
const hasActiveFilters = computed(() => {
    return filters.value.search ||
           filters.value.category_id ||
           filters.value.sub_category_id ||
           filters.value.track_id;
});

// Pagination
const currentPage = ref(1);
const perPage = ref(10);

// Filtered courses
const filteredCourses = ref([]);

// Stats
const activeCount = computed(() =>
    props.courses.filter(c => c.status === 'active').length
);
const certificateCount = computed(() =>
    props.courses.filter(c => c.certificate_available).length
);

// Total pages
const totalPages = computed(() => {
    return Math.ceil(filteredCourses.value.length / perPage.value) || 1;
});

// Paginated courses
const paginatedCourses = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    const end = start + perPage.value;
    return filteredCourses.value.slice(start, end);
});

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

// Filter sub categories based on selected category
const filteredSubCategories = computed(() => {
    if (!filters.value.category_id) {
        return props.allSubCategories;
    }
    return props.allSubCategories.filter(sub =>
        sub.category_id === parseInt(filters.value.category_id)
    );
});

// Filter tracks based on selected sub category
const filteredTracks = computed(() => {
    let tracks = props.allTracks;

    if (filters.value.sub_category_id) {
        tracks = tracks.filter(track =>
            track.sub_category_id === parseInt(filters.value.sub_category_id)
        );
    } else if (filters.value.category_id) {
        const subIds = props.allSubCategories
            .filter(sub => sub.category_id === parseInt(filters.value.category_id))
            .map(sub => sub.id);
        tracks = tracks.filter(track =>
            subIds.includes(track.sub_category_id)
        );
    }

    return tracks;
});

// Apply filters
const applyFilters = () => {
    let filtered = [...props.courses];

    if (filters.value.search) {
        const searchTerm = filters.value.search.toLowerCase();
        filtered = filtered.filter(course =>
            course.title.toLowerCase().includes(searchTerm) ||
            (course.slug && course.slug.toLowerCase().includes(searchTerm))
        );
    }

    if (filters.value.category_id) {
        filtered = filtered.filter(course =>
            course.track?.sub_category?.category_id === parseInt(filters.value.category_id)
        );
    }

    if (filters.value.sub_category_id) {
        filtered = filtered.filter(course =>
            course.track?.sub_category_id === parseInt(filters.value.sub_category_id)
        );
    }

    if (filters.value.track_id) {
        filtered = filtered.filter(course =>
            course.course_track_id === parseInt(filters.value.track_id)
        );
    }

    filteredCourses.value = filtered;
    resetPagination();
};

// Watch for changes
watch([() => props.courses, filters], () => {
    applyFilters();
}, { deep: true, immediate: true });

// Watch for category change to reset sub category and track
watch(() => filters.value.category_id, () => {
    filters.value.sub_category_id = '';
    filters.value.track_id = '';
});

// Watch for sub category change to reset track
watch(() => filters.value.sub_category_id, () => {
    filters.value.track_id = '';
});

// Handle image error
const handleImageError = (event) => {
    event.target.style.display = 'none';
};

// Reset filters
const resetFilters = () => {
    filters.value = {
        search: '',
        category_id: '',
        sub_category_id: '',
        track_id: ''
    };
};

const showDeleteModal = ref(false);
const deleteItem = ref(null);

const confirmDelete = (course) => {
    deleteItem.value = course;
    showDeleteModal.value = true;
};

const deleteCourse = () => {
    if (deleteItem.value) {
        router.delete(`/dashboard/course/courses/${deleteItem.value.id}`, {
            onSuccess: () => {
                showDeleteModal.value = false;
                deleteItem.value = null;
            },
            onError: (errors) => {
                alert(errors.message || 'Failed to delete course');
            }
        });
    }
};
</script>
