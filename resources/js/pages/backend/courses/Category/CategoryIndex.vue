<template>
    <Head :title="$t('Categories')" />
    <DashboardLayout>
        <section class="space-y-6">
            <Breadcrumbs :items="breadcrumbItems" />
            <PageHero eyebrow="Course Management" :title="$t('Categories')" :description="$t('Manage your course categories.')" />

            <Card padding="p-0">
                <div class="border-b border-slate-200 px-6 py-5 dark:border-gray-800 flex justify-between items-center">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center">
                        <input
                            v-model="search"
                            type="text"
                            :placeholder="$t('Search by name...')"
                            class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm text-slate-700 outline-none transition focus:border-blue-600 focus:ring-2 focus:ring-blue-100 lg:max-w-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder:text-gray-500 dark:focus:border-blue-500 dark:focus:ring-blue-500/20"
                            @input="resetPagination"
                        >
                    </div>

                    <div>
                        <Link
                            href="/dashboard/course/categories/create"
                            class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-blue-900 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700 dark:bg-blue-600 dark:hover:bg-blue-500"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            {{ $t('Create Category') }}
                        </Link>
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
                                <TableCell class="font-medium text-slate-900 dark:text-gray-100">
                                    <input
                                        type="text"
                                        :value="category.name"
                                        :disabled="savingId === category.id"
                                        class="w-full min-w-0 rounded-lg border border-transparent bg-transparent -ml-2 px-2 py-1 text-sm font-semibold text-slate-900 transition hover:border-slate-300 focus:border-blue-600 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:hover:border-gray-500 dark:focus:border-blue-500 dark:focus:bg-gray-800 dark:focus:ring-blue-500/20"
                                        @change="saveCategoryField(category, 'name', $event.target.value.trim())"
                                    >
                                </TableCell>
                                <TableCell>
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-slate-100 dark:bg-gray-800 text-slate-600 dark:text-gray-300">
                                        {{ category.sub_categories?.length || 0 }}
                                    </span>
                                </TableCell>
                                <TableCell>
                                    <select
                                        :value="category.status"
                                        :disabled="savingId === category.id"
                                        :class="[
                                            'w-full max-w-[120px] rounded-lg border border-transparent bg-transparent px-2 py-1 text-sm font-medium transition hover:border-slate-300 focus:border-blue-600 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 disabled:opacity-50 dark:border-gray-600 dark:bg-gray-800 dark:hover:border-gray-500 dark:focus:border-blue-500 dark:focus:bg-gray-800 dark:focus:ring-blue-500/20',
                                            category.status === 'active' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-600 dark:text-gray-400'
                                        ]"
                                        @change="saveCategoryField(category, 'status', $event.target.value)"
                                    >
                                        <option value="active">{{ $t('Active') }}</option>
                                        <option value="inactive">{{ $t('Inactive') }}</option>
                                    </select>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex justify-end gap-2">
                                        <button
                                            type="button"
                                            class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-100 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-400 dark:hover:bg-rose-500/20"
                                            @click="confirmDelete(category)"
                                        >
                                            {{ $t('Delete') }}
                                        </button>
                                    </div>
                                </TableCell>
                            </TableRow>

                            <TableRow v-if="paginatedCategories.length === 0">
                                <TableCell colspan="5" class="py-10 text-center text-slate-500 dark:text-gray-400">
                                    {{ $t('No categories found.') }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <!-- Pagination -->
                <div
                    class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800 dark:bg-gray-800/40">
                    <p class="text-sm text-slate-500 dark:text-gray-400">
                        {{ $t('Showing :from-:to of :total categories', { from: rangeStart, to: rangeEnd, total: filteredCategories.length }) }}
                    </p>

                    <Pagination
                        :current-page="currentPage"
                        :last-page="totalPages"
                        @page-change="goToPage"
                    />
                </div>
            </Card>
        </section>

    </DashboardLayout>
</template>

<script setup>
import axios from 'axios';
import { ref, computed, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { useConfirm } from '@/composables/useConfirm';
import { Breadcrumbs } from '@/components/ui/breadcrumbs';
import { Card } from '@/components/ui/card';
import { PageHero } from '@/components/ui/page-hero';
import { Table, TableHeader, TableBody, TableCell, TableHead, TableRow } from '@/components/ui/table';
import { Pagination } from '@/components/ui/pagination';
import { useI18n } from '@/i18n';
import { useToast } from 'vue-toastification';

const { t } = useI18n();
const { confirm } = useConfirm();
const toast = useToast();
const props = defineProps({
    categories: {
        type: Array,
        default: () => []
    }
});

const breadcrumbItems = [{ label: 'Dashboard', href: '/dashboard' }, { label: 'Categories', current: true }];

// Search and filters
const search = ref('');
const savingId = ref(null);

// Pagination
const currentPage = ref(1);
const perPage = ref(10);

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

async function saveCategoryField(category, field, value) {
    const previous = category[field];

    if (value === previous || savingId.value !== null) {
        return;
    }

    savingId.value = category.id;

    try {
        const response = await axios.put(`/dashboard/course/categories/${category.id}`, {
            name: field === 'name' ? value : category.name,
            status: field === 'status' ? value : category.status,
        });

        Object.assign(category, response.data.data ?? { [field]: value });
        toast.success(t('Category updated.'));
    } catch (error) {
        category[field] = previous;
        console.error('Failed to update category', error);
        toast.error(t(error.response?.data?.message ?? 'Failed to update category. Please try again.'));
    } finally {
        savingId.value = null;
    }
}

const confirmDelete = async (category) => {
    const subCategoryCount = category.sub_categories?.length || 0;
    const message = subCategoryCount > 0
        ? `${t('Are you sure you want to delete :name?', { name: category.name })} ${t('This category has :count sub-category(ies) that will also be deleted.', { count: subCategoryCount })}`
        : t('Are you sure you want to delete :name?', { name: category.name });
    const confirmed = await confirm({
        title: t('Delete category'),
        message,
        confirmText: t('Delete'),
        cancelText: t('Cancel'),
        danger: true,
    });

    if (!confirmed) return;

    router.delete(`/dashboard/course/categories/${category.id}`, {
        onSuccess: () => toast.success(t('Category deleted successfully.')),
        onError: (errors) => toast.error(errors.message || t('Failed to delete category. Please try again.')),
    });
};
</script>
