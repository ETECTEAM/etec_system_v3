<template>
    <DashboardLayout>
        <div class="w-full">

            <!-- Breadcrumb -->
            <nav class="flex items-center gap-1.5 text-sm text-slate-400 dark:text-gray-500 mb-4">
                <span>{{ $t('Dashboard') }}</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span>{{ $t('Course') }}</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="text-slate-600 dark:text-gray-300 font-medium">{{ $t('Sub Categories') }}</span>
            </nav>

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-blue-600 flex items-center justify-center shadow-sm shadow-blue-200 dark:shadow-none shrink-0">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 11V6a3 3 0 013-3z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900 dark:text-gray-100 tracking-tight">{{ $t('Sub Categories') }}</h1>
                        <p class="text-sm text-slate-500 dark:text-gray-400 mt-0.5">{{ $t('Read, create, update, and delete sub category records') }}</p>
                    </div>
                </div>

                <Link href="/dashboard/course/subcategories/create"
                    class="inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-500 text-white px-4 py-2.5 rounded-xl font-medium text-sm shadow-sm shadow-blue-200 dark:shadow-none transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ $t('Sub Category') }}
                </Link>
            </div>

            <!-- Stats strip -->
            <div class="grid grid-cols-2 gap-3 mb-6">
                <div class="bg-white dark:bg-gray-900 rounded-xl border border-slate-200 dark:border-gray-800 px-4 py-3.5">
                    <p class="text-xs font-medium text-slate-400 dark:text-gray-500 uppercase tracking-wide">{{ $t('Total') }}</p>
                    <p class="text-xl font-bold text-slate-900 dark:text-gray-100 mt-1">{{ subCategories.length }}</p>
                </div>
                <div class="bg-white dark:bg-gray-900 rounded-xl border border-slate-200 dark:border-gray-800 px-4 py-3.5">
                    <p class="text-xs font-medium text-slate-400 dark:text-gray-500 uppercase tracking-wide">{{ $t('Active') }}</p>
                    <p class="text-xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ activeCount }}</p>
                </div>
            </div>

            <!-- Search and Results Count -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-3">
                <div class="w-full sm:max-w-sm">
                    <div class="relative">
                        <input v-model="search" type="text" :placeholder="$t('Search sub categories...')"
                            class="w-full rounded-xl border border-slate-200 dark:border-gray-600 bg-white dark:bg-gray-800 pl-9 pr-4 py-2.5 text-sm text-slate-700 dark:text-gray-200 placeholder:text-slate-400 dark:placeholder:text-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-500/20 focus:border-transparent transition"
                            @input="resetPagination" />
                        <svg class="absolute left-3 top-3 w-4 h-4 text-slate-400 dark:text-gray-500" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Sub Categories Table -->
            <div class="bg-white dark:bg-gray-900 rounded-xl border border-slate-200 dark:border-gray-800 overflow-hidden shadow-sm">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-12">{{ $t('No') }}</TableHead>
                            <TableHead>{{ $t('Name') }}</TableHead>
                            <TableHead>{{ $t('Category') }}</TableHead>
                            <TableHead>{{ $t('Slug') }}</TableHead>
                            <TableHead>{{ $t('Status') }}</TableHead>
                            <TableHead class="text-right">{{ $t('Actions') }}</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="(subCategory, index) in paginatedSubCategories" :key="subCategory.id">
                            <TableCell class="text-sm text-slate-500 dark:text-gray-400">
                                {{ (currentPage - 1) * perPage + index + 1 }}
                            </TableCell>
                            <TableCell>
                                <span class="text-sm font-medium text-slate-900 dark:text-gray-100">{{ subCategory.name }}</span>
                            </TableCell>
                            <TableCell>
                                <span class="text-sm text-slate-600 dark:text-gray-300">{{ subCategory.category?.name }}</span>
                            </TableCell>
                            <TableCell>
                                <code class="text-xs bg-slate-100 dark:bg-gray-800 text-slate-600 dark:text-gray-300 px-2 py-1 rounded-md">{{ subCategory.slug }}</code>
                            </TableCell>
                            <TableCell>
                                <span class="inline-flex items-center gap-1.5 text-xs font-medium"
                                    :class="subCategory.status === 'active' ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-700 dark:text-rose-400'">
                                    <span class="w-1.5 h-1.5 rounded-full"
                                        :class="subCategory.status === 'active' ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                                    {{ subCategory.status === 'active' ? $t('Active') : $t('Inactive') }}
                                </span>
                            </TableCell>
                            <TableCell class="text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <Link :href="`/dashboard/course/subcategories/${subCategory.id}/edit`"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 dark:text-blue-400 dark:bg-blue-500/10 dark:hover:bg-blue-500/20 transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        {{ $t('Edit') }}
                                    </Link>
                                    <button @click="confirmDelete(subCategory)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-rose-700 bg-rose-50 hover:bg-rose-100 dark:text-rose-400 dark:bg-rose-500/10 dark:hover:bg-rose-500/20 transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        {{ $t('Delete') }}
                                    </button>
                                </div>
                            </TableCell>
                        </TableRow>

                        <!-- Empty state: no sub categories exist at all -->
                        <TableRow v-if="paginatedSubCategories.length === 0 && subCategories.length === 0">
                            <TableCell colspan="6" class="px-4 py-16 text-center">
                                <svg class="w-12 h-12 mx-auto text-slate-300 dark:text-gray-700 mb-3" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                                <p class="text-sm font-medium text-slate-600 dark:text-gray-300">{{ $t('No sub categories yet') }}</p>
                                <p class="text-xs text-slate-400 dark:text-gray-500 mt-1">{{ $t('Create your first sub category to start organizing courses') }}</p>
                                <Link href="/dashboard/course/subcategories/create"
                                    class="inline-flex items-center gap-1.5 mt-4 text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                                    + {{ $t('Sub Category') }}
                                </Link>
                            </TableCell>
                        </TableRow>

                        <!-- Empty state: search found nothing -->
                        <TableRow v-else-if="paginatedSubCategories.length === 0">
                            <TableCell colspan="6" class="px-4 py-16 text-center">
                                <svg class="w-12 h-12 mx-auto text-slate-300 dark:text-gray-700 mb-3" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <p class="text-sm font-medium text-slate-600 dark:text-gray-300">{{ $t('No results for ":search"', { search }) }}</p>
                                <p class="text-xs text-slate-400 dark:text-gray-500 mt-1">{{ $t('Try a different name or clear the search') }}</p>
                                <button @click="search = ''"
                                    class="inline-flex items-center gap-1.5 mt-4 text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                                    {{ $t('Clear search') }}
                                </button>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <!-- Pagination -->
                <div v-if="filteredSubCategories.length > 0"
                    class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800 dark:bg-gray-800/40">
                    <p class="text-sm text-slate-500 dark:text-gray-400">
                        {{ $t('Showing :from to :to of :total sub categories', { from: rangeStart, to: rangeEnd, total: filteredSubCategories.length }) }}
                    </p>

                    <Pagination
                        :current-page="currentPage"
                        :last-page="totalPages"
                        @page-change="goToPage"
                    />
                </div>
            </div>
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
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-gray-100">{{ $t('Delete sub category') }}</h3>
                        <p class="text-sm text-slate-600 dark:text-gray-400 mt-1">
                            {{ $t('Are you sure you want to delete') }} "<span class="font-medium text-slate-900 dark:text-gray-100">{{ deleteItem?.name }}</span>"?
                            {{ $t('This action cannot be undone.') }}
                        </p>
                        <p v-if="deleteItem?.tracks?.length > 0"
                            class="text-sm text-amber-700 bg-amber-50 dark:text-amber-400 dark:bg-amber-500/10 rounded-lg px-3 py-2 mt-3">
                            {{ $t('This sub category has :count track(s) that will also be deleted.', { count: deleteItem.tracks.length }) }}
                        </p>
                    </div>
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-100 dark:border-gray-800 pt-4">
                    <button @click="showDeleteModal = false"
                        class="px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:text-gray-200 dark:hover:bg-gray-800 rounded-xl transition">
                        {{ $t('Cancel') }}
                    </button>
                    <button @click="deleteSubCategory"
                        class="px-4 py-2 text-sm font-medium text-white bg-rose-600 hover:bg-rose-700 dark:bg-rose-600 dark:hover:bg-rose-500 rounded-xl transition">
                        {{ $t('Delete sub category') }}
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
import { Table, TableHeader, TableBody, TableCell, TableHead, TableRow } from '@/components/ui/table';
import { Pagination } from '@/components/ui/pagination';

const props = defineProps({
    subCategories: {
        type: Array,
        default: () => []
    }
});

// Search
const search = ref('');

// Pagination
const currentPage = ref(1);
const perPage = ref(10);

// Delete modal
const showDeleteModal = ref(false);
const deleteItem = ref(null);

// Stats
const activeCount = computed(() =>
    props.subCategories.filter(sub => sub.status === 'active').length
);

// Filtered sub categories
const filteredSubCategories = computed(() => {
    if (!search.value) return props.subCategories;
    return props.subCategories.filter(sub =>
        sub.name.toLowerCase().includes(search.value.toLowerCase()) ||
        (sub.category?.name && sub.category.name.toLowerCase().includes(search.value.toLowerCase()))
    );
});

// Total pages
const totalPages = computed(() => {
    return Math.ceil(filteredSubCategories.value.length / perPage.value) || 1;
});

// Paginated sub categories
const paginatedSubCategories = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    const end = start + perPage.value;
    return filteredSubCategories.value.slice(start, end);
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
    if (filteredSubCategories.value.length === 0) return 0;
    return (currentPage.value - 1) * perPage.value + 1;
});

const rangeEnd = computed(() => {
    return Math.min(currentPage.value * perPage.value, filteredSubCategories.value.length);
});

// Reset to page 1 when search changes
watch(search, () => {
    resetPagination();
});

// Confirm delete
const confirmDelete = (subCategory) => {
    deleteItem.value = subCategory;
    showDeleteModal.value = true;
};

const deleteSubCategory = () => {
    if (deleteItem.value) {
        router.delete(`/dashboard/course/subcategories/${deleteItem.value.id}`, {
            onSuccess: () => {
                showDeleteModal.value = false;
                deleteItem.value = null;
            },
            onError: (errors) => {
                alert(errors.message || 'Failed to delete sub category');
            }
        });
    }
};
</script>
