<template>
    <DashboardLayout>
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Courses</h1>
                    <p class="text-sm text-slate-500">Manage all courses</p>
                </div>
                <Link
                    href="/dashboard/course/courses/create"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2 whitespace-nowrap"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Course
                </Link>
            </div>

            <!-- Filters - One Row 4 Columns -->
            <div class="mb-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <!-- Search -->
                <div class="relative">
                    <input
                        v-model="filters.search"
                        type="text"
                        placeholder="Search courses..."
                        class="w-full rounded-lg border border-slate-200 pl-9 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        @input="applyFilters"
                    />
                    <svg class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>

                <!-- Category Filter -->
                <select
                    v-model="filters.category_id"
                    class="w-full rounded-lg border border-slate-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                    @change="applyFilters"
                >
                    <option value="">All Categories</option>
                    <option v-for="cat in allCategories" :key="cat.id" :value="cat.id">
                        {{ cat.name }}
                    </option>
                </select>

                <!-- Sub Category Filter -->
                <select
                    v-model="filters.sub_category_id"
                    class="w-full rounded-lg border border-slate-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                    @change="applyFilters"
                >
                    <option value="">All Sub Categories</option>
                    <option v-for="sub in filteredSubCategories" :key="sub.id" :value="sub.id">
                        {{ sub.name }}
                    </option>
                </select>

                <!-- Track Filter -->
                <select
                    v-model="filters.track_id"
                    class="w-full rounded-lg border border-slate-200 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                    @change="applyFilters"
                >
                    <option value="">All Tracks</option>
                    <option v-for="track in filteredTracks" :key="track.id" :value="track.id">
                        {{ track.name }}
                    </option>
                </select>
            </div>

            <!-- Results Count & Reset -->
            <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                <span class="text-sm text-slate-500">
                    Showing <strong class="text-slate-700">{{ filteredCourses.length }}</strong> of 
                    <strong class="text-slate-700">{{ courses.length }}</strong> courses
                </span>
                <button
                    v-if="hasActiveFilters"
                    @click="resetFilters"
                    class="text-sm text-blue-600 hover:text-blue-800 font-medium transition flex items-center gap-1"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Reset Filters
                </button>
            </div>

            <!-- Courses Table -->
            <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 px-4 py-3">#</th>
                                <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 px-4 py-3">Title</th>
                                <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 px-4 py-3">Category</th>
                                <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 px-4 py-3">Sub Category</th>
                                <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 px-4 py-3">Track</th>
                                <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 px-4 py-3">Level</th>
                                <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 px-4 py-3">Price</th>
                                <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 px-4 py-3">Certificate</th>
                                <th class="text-left text-xs font-semibold uppercase tracking-wider text-slate-500 px-4 py-3">Status</th>
                                <th class="text-right text-xs font-semibold uppercase tracking-wider text-slate-500 px-4 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            <tr v-for="(course, index) in filteredCourses" :key="course.id" class="hover:bg-slate-50 transition">
                                <td class="px-4 py-3 text-sm text-slate-600">{{ index + 1 }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-slate-100 overflow-hidden flex-shrink-0">
                                            <img
                                                v-if="course.thumbnail"
                                                :src="`/storage/${course.thumbnail}`"
                                                :alt="course.title"
                                                class="w-full h-full object-cover"
                                                @error="handleImageError"
                                            />
                                            <div v-else class="w-full h-full flex items-center justify-center text-slate-400">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                            </div>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-slate-900 line-clamp-1">{{ course.title }}</p>
                                            <p class="text-xs text-slate-500 line-clamp-1">{{ course.slug }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ course.track?.sub_category?.category?.name || 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ course.track?.sub_category?.name || 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm text-slate-600">{{ course.track?.name || 'N/A' }}</td>
                                <td class="px-4 py-3">
                                    <span
                                        v-if="course.level"
                                        :class="[
                                            'px-2 py-1 text-xs font-medium rounded-full',
                                            course.level === 'beginner' ? 'bg-green-100 text-green-700' :
                                            course.level === 'intermediate' ? 'bg-yellow-100 text-yellow-700' :
                                            'bg-red-100 text-red-700'
                                        ]"
                                    >
                                        {{ course.level }}
                                    </span>
                                    <span v-else class="text-sm text-slate-400">-</span>
                                </td>
                                <td class="px-4 py-3 text-sm font-semibold text-blue-600">${{ parseFloat(course.price || 0).toFixed(2) }}</td>
                                <td class="px-4 py-3">
                                    <span
                                        :class="[
                                            'px-2 py-1 text-xs font-medium rounded-full',
                                            course.certificate_available ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-500'
                                        ]"
                                    >
                                        {{ course.certificate_available ? 'Yes' : 'No' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        :class="[
                                            'px-2 py-1 text-xs font-medium rounded-full',
                                            course.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
                                        ]"
                                    >
                                        {{ course.status === 'active' ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <Link
                                            :href="`/dashboard/course/courses/${course.id}`"
                                            class="text-slate-500 hover:text-slate-700 transition p-1.5 rounded-lg hover:bg-slate-100"
                                            title="View"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </Link>
                                        <Link
                                            :href="`/dashboard/course/courses/${course.id}/edit`"
                                            class="text-blue-600 hover:text-blue-800 transition p-1.5 rounded-lg hover:bg-blue-50"
                                            title="Edit"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </Link>
                                        <button
                                            @click="confirmDelete(course)"
                                            class="text-red-600 hover:text-red-800 transition p-1.5 rounded-lg hover:bg-red-50"
                                            title="Delete"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredCourses.length === 0">
                                <td colspan="10" class="px-4 py-8 text-center text-sm text-slate-500">
                                    No courses found matching your filters.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Delete Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="showDeleteModal = false">
            <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold text-slate-900 mb-2">Delete Course</h3>
                <p class="text-sm text-slate-600 mb-6">
                    Are you sure you want to delete "{{ deleteItem?.title }}"? This action cannot be undone.
                </p>
                <div class="flex justify-end gap-3">
                    <button @click="showDeleteModal = false" class="px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 rounded-lg transition">
                        Cancel
                    </button>
                    <button @click="deleteCourse" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition">
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

// Filtered courses
const filteredCourses = ref([]);

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
        // Get all sub category IDs for this category
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

    // Search filter
    if (filters.value.search) {
        const searchTerm = filters.value.search.toLowerCase();
        filtered = filtered.filter(course =>
            course.title.toLowerCase().includes(searchTerm) ||
            (course.slug && course.slug.toLowerCase().includes(searchTerm))
        );
    }

    // Category filter
    if (filters.value.category_id) {
        filtered = filtered.filter(course =>
            course.track?.sub_category?.category_id === parseInt(filters.value.category_id)
        );
    }

    // Sub Category filter
    if (filters.value.sub_category_id) {
        filtered = filtered.filter(course =>
            course.track?.sub_category_id === parseInt(filters.value.sub_category_id)
        );
    }

    // Track filter
    if (filters.value.track_id) {
        filtered = filtered.filter(course =>
            course.course_track_id === parseInt(filters.value.track_id)
        );
    }

    filteredCourses.value = filtered;
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