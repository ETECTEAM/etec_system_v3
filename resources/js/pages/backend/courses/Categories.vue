<template>
    <DashboardLayout>
        <div class="w-full">

            <!-- Breadcrumb -->
            <nav class="flex items-center gap-1.5 text-sm text-slate-400 dark:text-gray-500 mb-4">
                <span>{{ $t('Dashboard') }}</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="text-slate-600 dark:text-gray-300 font-medium">{{ $t('Categories') }}</span>
            </nav>

            <PageHero
                eyebrow="Course Management"
                :title="$t('Categories')"
                :description="$t('Organize all learning categories')"
                class="mb-6"
            />

            <Card padding="p-0">
                <div class="border-b border-slate-200 px-6 py-5 dark:border-gray-800">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="min-w-0 shrink-0">
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400 dark:text-gray-500">{{ $t('Category Directory') }}</p>
                            <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">{{ $t('Organize all learning categories') }}</p>
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-3">
                            <div class="relative">
                                <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 dark:text-gray-500" />
                                <input
                                    v-model="search"
                                    type="text"
                                    :placeholder="$t('Search by name...')"
                                    class="w-full rounded-xl border border-slate-300 py-2.5 pl-9 pr-3 text-sm text-slate-700 outline-none transition placeholder:text-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 sm:w-56 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                                    @input="resetPagination"
                                >
                            </div>

                            <Link
                                href="/dashboard/course/categories/create"
                                class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-blue-900 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-500"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                {{ $t('New Category') }}
                            </Link>
                        </div>
                    </div>
                </div>

                <div class="relative">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-16">{{ $t('No') }}</TableHead>
                                <TableHead>{{ $t('Category') }}</TableHead>
                                <TableHead>{{ $t('Sub Categories') }}</TableHead>
                                <TableHead>{{ $t('Status') }}</TableHead>
                                <TableHead class="text-right">{{ $t('Actions') }}</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(category, index) in paginatedCategories" :key="category.id">
                                <TableCell class="text-sm text-slate-500 dark:text-gray-400">
                                    {{ (currentPage - 1) * perPage + index + 1 }}
                                </TableCell>
                                <TableCell>
                                    <span class="text-sm font-medium text-slate-900 dark:text-gray-100">{{ category.name }}</span>
                                </TableCell>
                                <TableCell>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-slate-100 dark:bg-gray-800 text-slate-600 dark:text-gray-300">
                                        {{ category.sub_categories?.length || 0 }}
                                    </span>
                                </TableCell>
                                <TableCell>
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" :class="category.status === 'active' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' : 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400'">
                                        {{ category.status === 'active' ? $t('Active') : $t('Inactive') }}
                                    </span>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex justify-end gap-2">
                                        <Link
                                            :href="`/dashboard/course/categories/${category.id}/edit`"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600 transition hover:bg-amber-100 dark:bg-amber-500/10 dark:text-amber-400 dark:hover:bg-amber-500/20"
                                            :title="$t('Edit')"
                                            :aria-label="$t('Edit category')"
                                        >
                                            <Pencil class="h-4 w-4" />
                                        </Link>

                                        <button
                                            type="button"
                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-600 transition hover:bg-red-100 dark:bg-red-500/10 dark:text-red-400 dark:hover:bg-red-500/20"
                                            :title="$t('Delete')"
                                            :aria-label="$t('Delete category')"
                                            @click="confirmDelete(category)"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </div>
                                </TableCell>
                            </TableRow>

                            <!-- Empty state: no categories exist at all -->
                            <TableRow v-if="paginatedCategories.length === 0 && categories.length === 0">
                                <TableCell colspan="5" class="px-4 py-16 text-center">
                                    <svg class="w-12 h-12 mx-auto text-slate-300 dark:text-gray-700 mb-3" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                    <p class="text-sm font-medium text-slate-600 dark:text-gray-300">{{ $t('No categories yet') }}</p>
                                    <p class="text-xs text-slate-400 dark:text-gray-500 mt-1">{{ $t('Create your first category to start organizing courses') }}</p>
                                    <Link href="/dashboard/course/categories/create"
                                        class="inline-flex items-center gap-1.5 mt-4 text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                                        + {{ $t('New Category') }}
                                    </Link>
                                </TableCell>
                            </TableRow>

                            <!-- Empty state: search found nothing -->
                            <TableRow v-else-if="paginatedCategories.length === 0">
                                <TableCell colspan="5" class="px-4 py-16 text-center">
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
                </div>

                <!-- Pagination -->
                <div v-if="filteredCategories.length > 0"
                    class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800 dark:bg-gray-800/40">
                    <p class="text-sm text-slate-500 dark:text-gray-400">
                        {{ $t('Showing :from to :to of :total categories', { from: rangeStart, to: rangeEnd, total: filteredCategories.length }) }}
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
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-gray-100">{{ $t('Delete category') }}</h3>
                        <p class="text-sm text-slate-600 dark:text-gray-400 mt-1">
                            {{ $t('Are you sure you want to delete') }} "<span class="font-medium text-slate-900 dark:text-gray-100">{{ deleteItem?.name }}</span>"?
                            {{ $t('This action cannot be undone.') }}
                        </p>
                        <p v-if="deleteItem?.sub_categories?.length > 0"
                            class="text-sm text-amber-700 bg-amber-50 dark:text-amber-400 dark:bg-amber-500/10 rounded-lg px-3 py-2 mt-3">
                            {{ $t('This category has :count sub-category(ies) that will also be deleted.', { count: deleteItem.sub_categories.length }) }}
                        </p>
                    </div>
                </div>
                <div class="flex justify-end gap-3 border-t border-slate-100 dark:border-gray-800 pt-4">
                    <button @click="showDeleteModal = false"
                        class="px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:text-gray-200 dark:hover:bg-gray-800 rounded-xl transition">
                        {{ $t('Cancel') }}
                    </button>
                    <button @click="deleteCategory"
                        class="px-4 py-2 text-sm font-medium text-white bg-rose-600 hover:bg-rose-700 dark:bg-rose-600 dark:hover:bg-rose-500 rounded-xl transition">
                        {{ $t('Delete category') }}
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

const props = defineProps({
    categories: {
        type: Array,
        default: () => []
    }
});

// Search and filters
const search = ref('');

// Pagination
const currentPage = ref(1);
const perPage = ref(10);

// Delete modal
const showDeleteModal = ref(false);
const deleteItem = ref(null);

// Filtered categories
const filteredCategories = computed(() => {
    if (!search.value) return props.categories;
    return props.categories.filter(cat =>
        cat.name.toLowerCase().includes(search.value.toLowerCase())
    );
});

// Total pages
const totalPages = computed(() => {
    return Math.ceil(filteredCategories.value.length / perPage.value) || 1;
});

// Paginated categories
const paginatedCategories = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    const end = start + perPage.value;
    return filteredCategories.value.slice(start, end);
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
    if (filteredCategories.value.length === 0) return 0;
    return (currentPage.value - 1) * perPage.value + 1;
});

const rangeEnd = computed(() => {
    return Math.min(currentPage.value * perPage.value, filteredCategories.value.length);
});

// Reset to page 1 when search changes
watch(search, () => {
    resetPagination();
});

// Confirm delete
const confirmDelete = (category) => {
    deleteItem.value = category;
    showDeleteModal.value = true;
};

const deleteCategory = () => {
    if (deleteItem.value) {
        router.delete(`/dashboard/course/categories/${deleteItem.value.id}`, {
            onSuccess: () => {
                showDeleteModal.value = false;
                deleteItem.value = null;
            },
            onError: (errors) => {
                alert(errors.message || 'Failed to delete category');
            }
        });
    }
};
</script>
