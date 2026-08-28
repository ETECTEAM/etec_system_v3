<template>
    <Head :title="$t('Class List')" />
    <DashboardLayout>
        <div class="w-full">

            <!-- Breadcrumb -->
            <nav class="flex items-center gap-1.5 text-sm text-slate-400 dark:text-gray-500 mb-4">
                <span>{{ $t('Dashboard') }}</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="text-slate-600 dark:text-gray-300 font-medium">{{ $t('Class List') }}</span>
            </nav>

            <PageHero
                eyebrow="Class Management"
                :title="$t('Classes')"
                :description="$t('Manage class schedules, instructors, and enrollment.')"
                class="mb-6"
            />

            <Card padding="p-0">
                <div class="border-b border-slate-200 px-6 py-5 dark:border-gray-800">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="min-w-0 shrink-0">
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400 dark:text-gray-500">{{ $t('Classes') }}</p>
                            <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">{{ $t('Manage class schedules, instructors, and enrollment.') }}</p>
                        </div>

                        <Link
                            href="/dashboard/class-list/create"
                            class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-blue-900 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-500"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            {{ $t('Create Class') }}
                        </Link>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <div class="relative">
                            <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-gray-500" />
                            <input
                                v-model="search"
                                type="search"
                                :placeholder="$t('Search classes or instructors')"
                                class="w-full rounded-xl border border-slate-300 py-2.5 pl-9 pr-3 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 sm:w-64 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                                @input="debounceSearch"
                                @keyup.enter.prevent="applyFilters"
                            >
                        </div>

                        <div class="w-44">
                            <SelectSearch
                                v-model="selectedType"
                                :options="classTypeOptions"
                                :placeholder="$t('Type')"
                                :search-placeholder="$t('Search types...')"
                                :button-class="filterSelectClass"
                            />
                        </div>

                        <div class="w-44">
                            <SelectSearch
                                v-model="selectedTerm"
                                :options="termOptions"
                                :placeholder="$t('Term')"
                                :search-placeholder="$t('Search terms...')"
                                :button-class="filterSelectClass"
                            />
                        </div>

                        <div class="w-44">
                            <SelectSearch
                                v-model="selectedTime"
                                :options="timeOptions"
                                :placeholder="$t('Time')"
                                :search-placeholder="$t('Search times...')"
                                :button-class="filterSelectClass"
                            />
                        </div>

                        <div class="w-44">
                            <SelectSearch
                                v-model="selectedStatus"
                                :options="statusOptions"
                                :placeholder="$t('Status')"
                                :search-placeholder="$t('Search status...')"
                                :button-class="filterSelectClass"
                            />
                        </div>
                    </div>
                </div>

                <div class="relative overflow-x-auto">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-16">{{ $t('No') }}</TableHead>
                                <TableHead>{{ $t('Class') }}</TableHead>
                                <TableHead>{{ $t('Instructor') }}</TableHead>
                                <TableHead>{{ $t('Schedule') }}</TableHead>
                                <TableHead>{{ $t('Location') }}</TableHead>
                                <TableHead>{{ $t('Enrollment') }}</TableHead>
                                <TableHead>{{ $t('Status') }}</TableHead>
                                <TableHead class="text-right">{{ $t('Actions') }}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(item, index) in filteredData" :key="item.id">
                                <TableCell class="text-sm text-slate-500 dark:text-gray-400">
                                    {{ (classLists.current_page - 1) * classLists.per_page + index + 1 }}
                                </TableCell>
                                <TableCell>
                                    <div class="font-semibold text-slate-900 dark:text-gray-100">{{ item.title || $t('No title') }}</div>
                                    <div class="mt-1 text-xs text-slate-500 dark:text-gray-400">{{ $t('Class #:id', { id: item.id }) }} · {{ item.course?.title || $t('No course') }}</div>
                                    <div class="mt-2 flex flex-wrap items-center gap-1.5"><span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700 dark:bg-gray-800 dark:text-gray-300">{{ item.lesson?.title || $t('No lesson') }}</span><span class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">{{ item.class_type?.type_name || $t('Unknown') }}</span></div>
                                </TableCell>
                                <TableCell>
                                    <span class="font-medium text-slate-900 dark:text-gray-100">{{ item.teacher?.name || $t('No instructor') }}</span>
                                </TableCell>
                                <TableCell>
                                    <div class="font-semibold text-slate-900 dark:text-gray-100">{{ item.term?.term_name || $t('No term') }}</div>
                                    <div class="mt-1 text-xs text-slate-500 dark:text-gray-400">{{ item.time?.time_name || $t('No time') }}</div>
                                </TableCell>
                                <TableCell>
                                    <div class="font-semibold text-slate-900 dark:text-gray-100">{{ item.room?.floor?.building?.name || $t('No building') }}</div>
                                    <div class="mt-1 text-xs text-slate-500 dark:text-gray-400">{{ item.room?.floor?.name || $t('No floor') }} · {{ item.room?.room_number || $t('No room') }}</div>
                                </TableCell>
                                <TableCell>
                                    <div class="font-semibold text-slate-900 dark:text-gray-100">{{ item.current_students ?? 0 }} / {{ item.capacity ?? 0 }}</div>
                                    <div class="mt-1 text-xs text-slate-500 dark:text-gray-400">{{ $t('enrolled') }}</div>
                                </TableCell>
                                <TableCell>
                                    <span :class="['inline-flex rounded-full px-3 py-1 text-[11px] font-semibold', statusBadgeClass(item.status)]">
                                        {{ $t(statusLabel(item.status)) }}
                                    </span>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex justify-end gap-2">
                                        <Link
                                            :href="`/dashboard/class-list/${item.id}`"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-600 transition hover:bg-slate-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                                            :title="$t('View')"
                                            :aria-label="$t('View class')"
                                        >
                                            <Eye class="h-4 w-4" />
                                        </Link>

                                        <Link
                                            :href="`/dashboard/class-list/${item.id}/edit`"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600 transition hover:bg-amber-100 dark:bg-amber-500/10 dark:text-amber-400 dark:hover:bg-amber-500/20"
                                            :title="$t('Edit')"
                                            :aria-label="$t('Edit class')"
                                        >
                                            <Pencil class="h-4 w-4" />
                                        </Link>

                                        <button
                                            type="button"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-600 transition hover:bg-red-100 dark:bg-red-500/10 dark:text-red-400 dark:hover:bg-red-500/20"
                                            :title="$t('Delete')"
                                            :aria-label="$t('Delete class')"
                                            @click="confirmDelete(item)"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </div>
                                </TableCell>
                            </TableRow>

                            <!-- Empty state -->
                            <TableRow v-if="filteredData.length === 0">
                                <TableCell colspan="8" class="px-4 py-16 text-center">
                                    <svg class="w-12 h-12 mx-auto text-slate-300 dark:text-gray-700 mb-3" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                    <p class="text-sm font-medium text-slate-600 dark:text-gray-300">{{ $t('No classes found.') }}</p>
                                    <Link href="/dashboard/class-list/create"
                                        class="inline-flex items-center gap-1.5 mt-4 text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                                        + {{ $t('Create Class') }}
                                    </Link>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Pagination -->
                <div v-if="totalCount > 0"
                    class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800 dark:bg-gray-800/40">
                    <p class="text-sm text-slate-500 dark:text-gray-400">
                        {{ $t('Showing :from-:to of :total classes', { from: classLists.from ?? 0, to: classLists.to ?? 0, total: totalCount }) }}
                    </p>

                    <Pagination
                        :current-page="classLists.current_page"
                        :last-page="classLists.last_page"
                        @page-change="handlePageChange"
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
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-gray-100">{{ $t('Delete class') }}</h3>
                        <p class="text-sm text-slate-600 dark:text-gray-400 mt-1">
                            {{ $t('Are you sure you want to delete this class?') }}
                            {{ $t('This action cannot be undone.') }}
                        </p>
                    </div>
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-100 dark:border-gray-800 pt-4">
                    <button @click="showDeleteModal = false"
                        class="px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:text-gray-200 dark:hover:bg-gray-800 rounded-xl transition">
                        {{ $t('Cancel') }}
                    </button>
                    <button @click="deleteConfirmed"
                        class="px-4 py-2 text-sm font-medium text-white bg-rose-600 hover:bg-rose-700 dark:bg-rose-600 dark:hover:bg-rose-500 rounded-xl transition">
                        {{ $t('Delete class') }}
                    </button>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<script setup>
import { ref, computed, watch, onBeforeUnmount } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye, Pencil, Search, Trash2 } from '@lucide/vue';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { PageHero } from '@/components/ui/page-hero';
import { Card } from '@/components/ui/card';
import { Table, TableHeader, TableBody, TableCell, TableHead, TableRow } from '@/components/ui/table';
import { Pagination } from '@/components/ui/pagination';
import { SelectSearch } from '@/components/ui/select-search';

const props = defineProps({
    classLists: Object,
    filters: Object,
    classTypes: Array,
    terms: Array,
    times: Array,
});

const search = ref(props.filters?.search ?? '');
const selectedType = ref(props.filters?.class_type ?? '');
const selectedTerm = ref(props.filters?.term ?? '');
const selectedTime = ref(props.filters?.time ?? '');
const selectedStatus = ref(props.filters?.status ?? '');
const searchTimer = ref(null);

const filteredData = computed(() => props.classLists?.data || []);

const filterSelectClass = 'flex w-full items-center justify-between rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-left text-sm transition focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20';

const classTypeOptions = computed(() => props.classTypes.map((item) => ({ label: item.type_name, value: item.type_name })));
const termOptions = computed(() => props.terms.map((item) => ({ label: item.term_name, value: item.term_name })));
const timeOptions = computed(() => props.times.map((item) => ({ label: item.time_name, value: item.time_name })));
const statusOptions = [
    { label: 'upcoming', value: 'upcoming' },
    { label: 'active', value: 'active' },
    { label: 'pre_end', value: 'pre_end' },
    { label: 'ended', value: 'ended' },
    { label: 'cancelled', value: 'cancelled' },
];

const statusBadgeClass = (status) => ({
    'bg-slate-100 text-slate-600 dark:bg-gray-800 dark:text-gray-300': status === 'upcoming',
    'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400': status === 'active',
    'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400': status === 'pre_end',
    'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400': status === 'ended',
    'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400': status === 'cancelled',
});

const statusLabel = (status) => (status ? status.replace('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase()) : 'Unknown');

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

watch([selectedType, selectedTerm, selectedTime, selectedStatus], applyFilters);

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

const handlePageChange = (page) => {
    router.get('/dashboard/class-list', { ...paginationParams(), page }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Delete modal
const showDeleteModal = ref(false);
const deleteItem = ref(null);

const confirmDelete = (item) => {
    deleteItem.value = item;
    showDeleteModal.value = true;
};

const deleteConfirmed = () => {
    if (deleteItem.value) {
        router.delete(`/dashboard/class-list/${deleteItem.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteModal.value = false;
                deleteItem.value = null;
            },
            onError: () => {
                alert('Unable to delete this class list entry.');
            },
        });
    }
};
</script>
