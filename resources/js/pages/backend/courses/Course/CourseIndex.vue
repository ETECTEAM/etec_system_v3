<template>
    <DashboardLayout>
        <div class="w-full">

            <Breadcrumbs :items="breadcrumbItems" />

            <PageHero
                eyebrow="Course Management"
                :title="$t('Courses')"
                :description="$t('Read, create, update, and delete course records')"
                class="mb-6"
            />

            <!-- Card summary -->
            <!-- <div class="grid grid-cols-1 gap-3 mb-6 sm:grid-cols-3">
                <Card padding="px-4 py-3.5">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-gray-500">{{ $t('Total') }}</p>
                    <p class="mt-1 text-xl font-bold text-slate-900 dark:text-gray-100">{{ courses.length }}</p>
                </Card>
                <Card padding="px-4 py-3.5">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400 dark:text-gray-500">{{ $t('Active') }}</p>
                    <p class="mt-1 text-xl font-bold text-emerald-600 dark:text-emerald-400">{{ activeCount }}</p>
                </Card>
            </div> -->

            <Card padding="p-0">
                <div class="border-b border-slate-200 px-6 py-5 dark:border-gray-800">
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 items-center w-full">
                        <div class="col-span-1">
                            <input
                                v-model="filters.search"
                                type="text"
                                :placeholder="$t('Search courses...')"
                                class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-700 outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                                @input="applyFilters"
                            >
                        </div>
                        <div class="col-span-1 sm:col-start-2 md:col-start-3 flex justify-end">
                            <Link
                                href="/dashboard/course/courses/create"
                                class="inline-flex items-center justify-center gap-1.5 w-full sm:w-auto rounded-xl bg-blue-900 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-500"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                {{ $t('Create Course') }}
                            </Link>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 border-b border-slate-200 dark:border-gray-800">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">
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
                                <TableHead class="w-2/5">{{ $t('Course Details') }}</TableHead>
                                <TableHead>{{ $t('Track Assignment') }}</TableHead>
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
                                    <div class="min-w-0">
                                        <input type="text" :value="course.title" :disabled="savingId === course.id" class="w-full min-w-0 rounded-lg border border-transparent bg-transparent -ml-2 px-2 py-1 text-sm font-semibold text-slate-900 transition hover:border-slate-300 focus:border-blue-600 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:hover:border-gray-500 dark:focus:border-blue-500 dark:focus:bg-gray-800 dark:focus:ring-blue-500/20" @change="saveCourseField(course, 'title', $event.target.value.trim())">
                                        <div class="mt-0.5 flex flex-wrap items-center gap-1.5">
                                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-600 dark:bg-gray-800 dark:text-gray-300">
                                                {{ course.track?.sub_category?.category?.name || $t('No Category') }}
                                            </span>
                                            <span class="text-slate-300 dark:text-gray-600">›</span>
                                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-600 dark:bg-gray-800 dark:text-gray-300">
                                                {{ course.track?.sub_category?.name || $t('No Sub Category') }}
                                            </span>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <select :value="course.course_track_id" :disabled="savingId === course.id" class="w-full max-w-xs rounded-xl border border-transparent bg-transparent px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:border-slate-300 focus:border-blue-600 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:border-gray-500 dark:focus:border-blue-500 dark:focus:bg-gray-800 dark:focus:ring-blue-500/20" @change="saveCourseField(course, 'course_track_id', parseInt($event.target.value))">
                                        <optgroup v-for="group in groupedTracks" :key="group.label" :label="group.label">
                                            <option v-for="t in group.tracks" :key="t.id" :value="t.id">{{ t.name }}</option>
                                        </optgroup>
                                    </select>
                                </TableCell>
                                <TableCell>
                                    <select :value="course.status" :disabled="savingId === course.id" :class="[
                                        'w-full max-w-[120px] rounded-lg border border-transparent bg-transparent px-2 py-1 text-sm font-medium transition hover:border-slate-300 focus:border-blue-600 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:hover:border-gray-500 dark:focus:border-blue-500 dark:focus:bg-gray-800 dark:focus:ring-blue-500/20',
                                        course.status === 'active' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-600 dark:text-gray-400'
                                    ]" @change="saveCourseField(course, 'status', $event.target.value)">
                                        <option value="active">{{ $t('Active') }}</option>
                                        <option value="inactive">{{ $t('Inactive') }}</option>
                                    </select>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex justify-end gap-2">
                                        <Link
                                            :href="`/dashboard/course/courses/${course.id}/edit`"
                                            class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700 transition hover:bg-amber-100 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-400 dark:hover:bg-amber-500/20"
                                        >
                                            {{ $t('Edit') }}
                                        </Link>
                                        <button
                                            type="button"
                                            class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-100 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-400 dark:hover:bg-rose-500/20"
                                            @click="confirmDelete(course)"
                                        >
                                            {{ $t('Delete') }}
                                        </button>
                                    </div>
                                </TableCell>
                            </TableRow>

                            <TableRow v-if="paginatedCourses.length === 0">
                                <TableCell colspan="5" class="py-16 text-center">
                                    {{ $t('No courses found.') }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Pagination -->
                <div
                    class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800 dark:bg-gray-800/40">
                    <p class="text-sm text-slate-500 dark:text-gray-400">
                        {{ $t('Showing :from-:to of :total courses', { from: rangeStart, to: rangeEnd, total: filteredCourses.length }) }}
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
import axios from 'axios';
import { ref, computed, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { Pencil, Trash2 } from '@lucide/vue';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Breadcrumbs } from '@/components/ui/breadcrumbs';
import { Card } from '@/components/ui/card';
import { PageHero } from '@/components/ui/page-hero';
import { Table, TableHeader, TableBody, TableCell, TableHead, TableRow } from '@/components/ui/table';
import { Pagination } from '@/components/ui/pagination';
import { SelectSearch } from '@/components/ui/select-search';
import { useI18n } from '@/i18n';
import { useToast } from 'vue-toastification';

const { t } = useI18n();
const toast = useToast();

const breadcrumbItems = [
    { label: 'Dashboard', href: '/dashboard' },
    { label: 'Courses', current: true },
];

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
const savingId = ref(null);

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

// Group tracks by Category > SubCategory for the select dropdown
const groupedTracks = computed(() => {
    return props.allSubCategories.map(sub => {
        const cat = props.allCategories.find(c => c.id === sub.category_id);
        const tracks = props.allTracks.filter(t => t.sub_category_id === sub.id);
        return {
            label: `${cat?.name || 'Unknown'} — ${sub.name}`,
            tracks
        };
    }).filter(group => group.tracks.length > 0);
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

async function saveCourseField(course, field, value) {
    const previous = course[field];
    if (value === previous || savingId.value !== null) return;
    savingId.value = course.id;
    try {
        const response = await axios.put(`/dashboard/course/courses/${course.id}`, {
            course_track_id: field === 'course_track_id' ? value : course.course_track_id,
            title: field === 'title' ? value : course.title,
            level: course.level,
            price: course.price ?? 0,
            status: field === 'status' ? value : course.status,
        });
        Object.assign(course, response.data.data ?? { [field]: value });
        course.temp_category_id = null;
        course.temp_sub_category_id = null;
        toast.success(t('Course updated.'));
    } catch (error) {
        course[field] = previous;
        toast.error(t(error.response?.data?.message ?? 'Failed to update course. Please try again.'));
    } finally {
        savingId.value = null;
    }
}

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
