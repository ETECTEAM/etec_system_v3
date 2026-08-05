<template>
    <DashboardLayout>
        <div class="w-full">

            <!-- Breadcrumb -->
            <nav class="flex items-center gap-1.5 text-sm text-slate-400 dark:text-gray-500 mb-4">
                <span>{{ $t('Dashboard') }}</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="text-slate-600 dark:text-gray-300 font-medium">{{ $t('Courses') }}</span>
            </nav>

            <PageHero
                eyebrow="Course Management"
                :title="$t('Courses')"
                :description="$t('Read, create, update, and delete course records')"
                class="mb-6"
            />

            <!-- Card summary -->
            <div class="grid grid-cols-1 gap-3 mb-6 sm:grid-cols-3">
                <Card padding="px-4 py-3.5">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-gray-500">{{ $t('Total') }}</p>
                    <p class="mt-1 text-xl font-bold text-slate-900 dark:text-gray-100">{{ courses.length }}</p>
                </Card>
                <Card padding="px-4 py-3.5">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-gray-500">{{ $t('Active') }}</p>
                    <p class="mt-1 text-xl font-bold text-emerald-600 dark:text-emerald-400">{{ activeCount }}</p>
                </Card>
                <Card padding="px-4 py-3.5">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-gray-500">{{ $t('With Certificate') }}</p>
                    <p class="mt-1 text-xl font-bold text-slate-900 dark:text-gray-100">{{ certificateCount }}</p>
                </Card>
            </div>

            <Card padding="p-0">
                <div class="border-b border-slate-200 px-6 py-5 dark:border-gray-800">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="min-w-0 shrink-0">
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400 dark:text-gray-500">{{ $t('Course Directory') }}</p>
                            <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">{{ $t('Read, create, update, and delete course records') }}</p>
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-3">
                            <div class="relative">
                                <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-gray-500" />
                                <input
                                    v-model="filters.search"
                                    type="text"
                                    :placeholder="$t('Search courses...')"
                                    class="w-full rounded-xl border border-slate-300 py-2.5 pl-9 pr-3 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 sm:w-56 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                                    @input="applyFilters"
                                >
                            </div>

                            <Link
                                href="/dashboard/course/courses/create"
                                class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-blue-900 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-500"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                {{ $t('Course') }}
                            </Link>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <SelectSearch
                            v-model="filters.category_id"
                            :options="categoryOptions"
                            :placeholder="$t('All Categories')"
                        />

                        <SelectSearch
                            v-model="filters.sub_category_id"
                            :options="subCategoryOptions"
                            :placeholder="$t('All Sub Categories')"
                        />

                        <SelectSearch
                            v-model="filters.track_id"
                            :options="trackOptions"
                            :placeholder="$t('All Tracks')"
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
                                <TableHead>{{ $t('Category') }}</TableHead>
                                <TableHead>{{ $t('Sub Category') }}</TableHead>
                                <TableHead>{{ $t('Track') }}</TableHead>
                                <TableHead>{{ $t('Certificate') }}</TableHead>
                                <TableHead>{{ $t('Status') }}</TableHead>
                                <TableHead class="text-right">{{ $t('Actions') }}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(course, index) in paginatedCourses" :key="course.id">
                                <TableCell class="text-sm text-slate-500 dark:text-gray-400">
                                    {{ (currentPage - 1) * perPage + index + 1 }}
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 shrink-0 overflow-hidden rounded-lg bg-slate-100 dark:bg-gray-800">
                                            <img v-if="course.thumbnail" :src="`/storage/${course.thumbnail}`" :alt="course.title"
                                                class="h-full w-full object-cover" @error="handleImageError">
                                            <div v-else class="flex h-full w-full items-center justify-center text-slate-400 dark:text-gray-500">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-medium text-slate-900 dark:text-gray-100">{{ course.title }}</p>
                                            <p class="truncate text-xs text-slate-500 dark:text-gray-400">{{ course.slug }}</p>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <span class="text-sm text-slate-600 dark:text-gray-300">{{ course.track?.sub_category?.category?.name || $t('N/A') }}</span>
                                </TableCell>
                                <TableCell>
                                    <span class="text-sm text-slate-600 dark:text-gray-300">{{ course.track?.sub_category?.name || $t('N/A') }}</span>
                                </TableCell>
                                <TableCell>
                                    <span class="text-sm text-slate-600 dark:text-gray-300">{{ course.track?.name || $t('N/A') }}</span>
                                </TableCell>
                                <TableCell>
                                    <span class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-medium"
                                        :class="course.certificate_available ? 'bg-violet-50 text-violet-700 dark:bg-violet-500/10 dark:text-violet-400' : 'bg-slate-100 text-slate-500 dark:bg-gray-800 dark:text-gray-400'">
                                        {{ course.certificate_available ? $t('Yes') : $t('No value') }}
                                    </span>
                                </TableCell>
                                <TableCell>
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="course.status === 'active' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' : 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400'">
                                        {{ course.status === 'active' ? $t('Active') : $t('Inactive') }}
                                    </span>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex justify-end gap-2">
                                        <Link
                                            :href="`/dashboard/course/courses/${course.id}/edit`"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600 transition hover:bg-amber-100 dark:bg-amber-500/10 dark:text-amber-400 dark:hover:bg-amber-500/20"
                                            :title="$t('Edit')"
                                            :aria-label="$t('Edit course')"
                                        >
                                            <Pencil class="h-4 w-4" />
                                        </Link>

                                        <button
                                            type="button"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-600 transition hover:bg-red-100 dark:bg-red-500/10 dark:text-red-400 dark:hover:bg-red-500/20"
                                            :title="$t('Delete')"
                                            :aria-label="$t('Delete course')"
                                            @click="confirmDelete(course)"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </div>
                                </TableCell>
                            </TableRow>

                            <!-- Empty state: no courses exist at all -->
                            <TableRow v-if="paginatedCourses.length === 0 && courses.length === 0">
                                <TableCell colspan="9" class="px-4 py-16 text-center">
                                    <svg class="w-12 h-12 mx-auto text-slate-300 dark:text-gray-700 mb-3" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s4.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                    </svg>
                                    <p class="text-sm font-medium text-slate-600 dark:text-gray-300">{{ $t('No courses yet') }}</p>
                                    <p class="text-xs text-slate-400 dark:text-gray-500 mt-1">{{ $t('Create your first course to get started') }}</p>
                                    <Link href="/dashboard/course/courses/create"
                                        class="inline-flex items-center gap-1.5 mt-4 text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                                        + {{ $t('Course') }}
                                    </Link>
                                </TableCell>
                            </TableRow>

                            <!-- Empty state: filters found nothing -->
                            <TableRow v-else-if="paginatedCourses.length === 0">
                                <TableCell colspan="9" class="px-4 py-16 text-center">
                                    <svg class="w-12 h-12 mx-auto text-slate-300 dark:text-gray-700 mb-3" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    <p class="text-sm font-medium text-slate-600 dark:text-gray-300">{{ $t('No courses match your filters') }}</p>
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
                <div v-if="filteredCourses.length > 0"
                    class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800 dark:bg-gray-800/40">
                    <p class="text-sm text-slate-500 dark:text-gray-400">
                        {{ $t('Showing :from to :to of :total courses', { from: rangeStart, to: rangeEnd, total: filteredCourses.length }) }}
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
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-gray-100">{{ $t('Delete course') }}</h3>
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
                    <button @click="deleteCourse"
                        class="px-4 py-2 text-sm font-medium text-white bg-rose-600 hover:bg-rose-700 dark:bg-rose-600 dark:hover:bg-rose-500 rounded-xl transition">
                        {{ $t('Delete course') }}
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

// Range shown in the "Showing X to Y of Z" strip below the table
const rangeStart = computed(() => {
    if (filteredCourses.value.length === 0) return 0;
    return (currentPage.value - 1) * perPage.value + 1;
});

const rangeEnd = computed(() => {
    return Math.min(currentPage.value * perPage.value, filteredCourses.value.length);
});

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

// Options for the searchable filter dropdowns
const categoryOptions = computed(() => [
    { label: 'All Categories', value: '' },
    ...props.allCategories.map(cat => ({ label: cat.name, value: String(cat.id) }))
]);

const subCategoryOptions = computed(() => [
    { label: 'All Sub Categories', value: '' },
    ...filteredSubCategories.value.map(sub => ({ label: sub.name, value: String(sub.id) }))
]);

const trackOptions = computed(() => [
    { label: 'All Tracks', value: '' },
    ...filteredTracks.value.map(track => ({ label: track.name, value: String(track.id) }))
]);

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
