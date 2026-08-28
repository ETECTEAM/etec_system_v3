<template>
    <DashboardLayout>
        <div class="w-full">

            <!-- Breadcrumb -->
            <nav class="flex items-center gap-1.5 text-sm text-slate-400 dark:text-gray-500 mb-4">
                <span>{{ $t('Dashboard') }}</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="text-slate-600 dark:text-gray-300 font-medium">{{ $t('Schedules') }}</span>
            </nav>

            <PageHero
                eyebrow="Schedules Management"
                :title="$t('Schedules')"
                :description="$t('Read, create, update, and delete schedules records')"
                class="mb-6"
            />

            <Card padding="p-0">
                <div class="border-b border-slate-200 px-6 py-5 dark:border-gray-800">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="min-w-0 shrink-0">
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400 dark:text-gray-500">{{ $t('Schedule Directory') }}</p>
                            <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">{{ $t('Read, create, update, and delete schedules records') }}</p>
                        </div>

                        <Link
                            href="/dashboard/schdule/create"
                            class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-blue-900 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-500"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            {{ $t('Schedule') }}
                        </Link>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <div class="relative">
                            <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-gray-500" />
                            <input
                                v-model="filters.search"
                                type="text"
                                :placeholder="$t('Search schedules...')"
                                class="w-full rounded-xl border border-slate-300 py-2.5 pl-9 pr-3 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 sm:w-56 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                            >
                        </div>

                        <div class="w-48">
                            <SelectSearch
                                v-model="filters.class_type_id"
                                :options="classTypeOptions"
                                :placeholder="$t('All Class Types')"
                                :button-class="filterSelectClass"
                            />
                        </div>

                        <div class="w-48">
                            <SelectSearch
                                v-model="filters.term_id"
                                :options="termOptions"
                                :placeholder="$t('All Terms')"
                                :button-class="filterSelectClass"
                            />
                        </div>

                        <div class="w-48">
                            <SelectSearch
                                v-model="filters.time_id"
                                :options="timeOptions"
                                :placeholder="$t('All Times')"
                                :button-class="filterSelectClass"
                            />
                        </div>
                    </div>
                </div>

                <div class="relative">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-56">{{ $t('Class Type') }}</TableHead>
                                <TableHead class="w-48">{{ $t('Term') }}</TableHead>
                                <TableHead>{{ $t('Time Slots') }}</TableHead>
                                <TableHead class="text-right w-20">{{ $t('Actions') }}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <template v-for="group in scheduleGroups" :key="group.class_type_id">
                                <TableRow v-for="(schedule, index) in group.schedules" :key="schedule.id">
                                    <TableCell v-if="index === 0" :rowspan="group.schedules.length" class="align-top border-r border-slate-100 dark:border-gray-800">
                                        <span class="text-sm font-semibold text-slate-900 dark:text-gray-100">{{ group.class_type_name }}</span>
                                    </TableCell>

                                    <TableCell>
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-medium text-slate-600 dark:text-gray-300">{{ schedule.term_name }}</span>
                                            <Link
                                                :href="`/dashboard/schdule/${schedule.id}/edit`"
                                                class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-amber-50 text-amber-600 transition hover:bg-amber-100 dark:bg-amber-500/10 dark:text-amber-400 dark:hover:bg-amber-500/20"
                                                :title="$t('Edit')"
                                                :aria-label="$t('Edit schedule')"
                                            >
                                                <Pencil class="h-3.5 w-3.5" />
                                            </Link>
                                            <button
                                                type="button"
                                                class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-red-50 text-red-600 transition hover:bg-red-100 dark:bg-red-500/10 dark:text-red-400 dark:hover:bg-red-500/20"
                                                :title="$t('Delete')"
                                                :aria-label="$t('Delete schedule')"
                                                @click="confirmDeleteSchedule(schedule)"
                                            >
                                                <Trash2 class="h-3.5 w-3.5" />
                                            </button>
                                        </div>
                                    </TableCell>

                                    <TableCell>
                                        <div class="flex flex-wrap gap-1.5">
                                            <span
                                                v-for="t in schedule.times"
                                                :key="t.id"
                                                class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20"
                                            >
                                                {{ t.time_name }}
                                            </span>
                                            <span v-if="!schedule.times?.length" class="text-slate-400 text-xs dark:text-gray-500">
                                                {{ $t('No times selected') }}
                                            </span>
                                        </div>
                                    </TableCell>

                                    <TableCell v-if="index === 0" :rowspan="group.schedules.length" class="text-right align-top">
                                        <button
                                            type="button"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-600 transition hover:bg-red-100 dark:bg-red-500/10 dark:text-red-400 dark:hover:bg-red-500/20"
                                            :title="$t('Delete all schedules for this class type')"
                                            :aria-label="$t('Delete class type schedules')"
                                            @click="confirmDeleteGroup(group)"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </TableCell>
                                </TableRow>
                            </template>

                            <!-- Empty state -->
                            <TableRow v-if="!scheduleGroups.length">
                                <TableCell colspan="4" class="px-4 py-16 text-center">
                                    <svg class="w-12 h-12 mx-auto text-slate-300 dark:text-gray-700 mb-3" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                    <p class="text-sm font-medium text-slate-600 dark:text-gray-300">
                                        {{ filters.search ? $t('No results for ":search"', { search: filters.search }) : $t('No schedules found.') }}
                                    </p>
                                    <Link href="/dashboard/schdule/create"
                                        class="inline-flex items-center gap-1.5 mt-4 text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                                        + {{ $t('Schedule') }}
                                    </Link>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <div v-if="scheduleGroups.length > 0"
                    class="border-t border-slate-200 bg-slate-50 px-6 py-4 dark:border-gray-800 dark:bg-gray-800/40">
                    <p class="text-sm text-slate-500 dark:text-gray-400">
                        {{ $t(':count class types', { count: scheduleGroups.length }) }}
                    </p>
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
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-gray-100">
                            {{ deleteMode === 'group' ? $t('Delete class type schedules') : $t('Delete schedule') }}
                        </h3>
                        <p class="text-sm text-slate-600 dark:text-gray-400 mt-1">
                            <template v-if="deleteMode === 'group'">
                                {{ $t('Are you sure you want to delete all :count schedule(s) for ":name"?', { count: deleteItem?.schedules?.length ?? 0, name: deleteItem?.class_type_name }) }}
                            </template>
                            <template v-else>
                                {{ $t('Are you sure you want to delete this schedule?') }}
                            </template>
                            {{ $t('This action cannot be undone.') }}
                        </p>
                    </div>
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-100 dark:border-gray-800 pt-4">
                    <button @click="showDeleteModal = false"
                        class="px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:text-gray-200 dark:hover:bg-gray-800 rounded-xl transition">
                        {{ $t('Cancel') }}
                    </button>
                    <button @click="deleteMode === 'group' ? deleteGroup() : deleteSchedule()"
                        class="px-4 py-2 text-sm font-medium text-white bg-rose-600 hover:bg-rose-700 dark:bg-rose-600 dark:hover:bg-rose-500 rounded-xl transition">
                        {{ $t('Delete') }}
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
import { SelectSearch } from '@/components/ui/select-search';

const props = defineProps({
    scheduleGroups: {
        type: Array,
        default: () => [],
    },
    filters: Object,
    classTypes: Array,
    terms: Array,
    times: Array,
});

const filterSelectClass = 'flex w-full items-center justify-between rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-left text-sm transition focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:focus:border-blue-500 dark:focus:ring-blue-500/20';

const classTypeOptions = computed(() =>
    (props.classTypes || []).map((ct) => ({ label: ct.type_name, value: String(ct.class_type_id) }))
);

const termOptions = computed(() =>
    (props.terms || []).map((t) => ({ label: t.term_name, value: String(t.id) }))
);

const timeOptions = computed(() =>
    (props.times || []).map((t) => ({ label: t.time_name, value: String(t.id) }))
);

// Filters (server-driven: class type / term / time selection needs DB-level filtering)
const filters = ref({
    search: props.filters.search ?? '',
    class_type_id: props.filters.class_type_id ?? '',
    term_id: props.filters.term_id ?? '',
    time_id: props.filters.time_id ?? '',
});

let timeout = null;

watch(filters, (value) => {
    clearTimeout(timeout);

    timeout = setTimeout(() => {
        router.get(
            '/dashboard/schdule',
            {
                search: value.search,
                class_type_id: value.class_type_id,
                term_id: value.term_id,
                time_id: value.time_id,
            },
            {
                preserveState: true,
                replace: true,
                preserveScroll: true,
            }
        );
    }, 400);
}, { deep: true });

// Delete modal (shared between single-schedule and whole-class-type-group delete)
const showDeleteModal = ref(false);
const deleteMode = ref('schedule'); // 'schedule' | 'group'
const deleteItem = ref(null);

const confirmDeleteSchedule = (schedule) => {
    deleteMode.value = 'schedule';
    deleteItem.value = schedule;
    showDeleteModal.value = true;
};

const confirmDeleteGroup = (group) => {
    deleteMode.value = 'group';
    deleteItem.value = group;
    showDeleteModal.value = true;
};

const deleteSchedule = () => {
    if (deleteItem.value) {
        router.delete(`/dashboard/schdule/${deleteItem.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteModal.value = false;
                deleteItem.value = null;
            },
        });
    }
};

const deleteGroup = () => {
    if (deleteItem.value) {
        router.delete(`/dashboard/schdule/class-type/${deleteItem.value.class_type_id}`, {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteModal.value = false;
                deleteItem.value = null;
            },
        });
    }
};
</script>
