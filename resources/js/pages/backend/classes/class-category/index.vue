<script setup>
import { ref, computed } from 'vue';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { Breadcrumbs } from '@/components/ui/breadcrumbs';
import { PageHero } from '@/components/ui/page-hero';
import { Card } from '@/components/ui/card';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '@/components/ui/table';
import { ActionMenu } from '@/components/ui/menu';

import CreateModal from './create.vue';
import EditModal from './edit.vue';
import ShowModal from './show.vue';
import DeleteModal from './delete.vue';

const props = defineProps({
    categories: Object,
    classTypes: Array
});

const isCreateOpen = ref(false);
const isEditOpen = ref(false);
const isShowOpen = ref(false);
const isDeleteOpen = ref(false);
const activeItem = ref(null);

// Search & filter state
const search = ref('');
const statusFilter = ref('all');       // 'all' | 'active' | 'inactive'
const classTypeFilter = ref('all');    // 'all' | class_type_id (as string)

const filteredCategories = computed(() => {
    if (!props.categories?.data) return [];

    return props.categories.data.filter(item => {
        const matchesSearch =
            search.value.trim() === '' ||
            item.category_name?.toLowerCase().includes(search.value.toLowerCase()) ||
            item.category_code?.toLowerCase().includes(search.value.toLowerCase());

        const matchesStatus =
            statusFilter.value === 'all' ||
            (statusFilter.value === 'active' && item.is_active) ||
            (statusFilter.value === 'inactive' && !item.is_active);

        const matchesType =
            classTypeFilter.value === 'all' ||
            String(item.class_type_id) === classTypeFilter.value;

        return matchesSearch && matchesStatus && matchesType;
    });
});

const hasActiveFilters = computed(() =>
    search.value.trim() !== '' ||
    statusFilter.value !== 'all' ||
    classTypeFilter.value !== 'all'
);

const clearFilters = () => {
    search.value = '';
    statusFilter.value = 'all';
    classTypeFilter.value = 'all';
};

const handleAction = (action, item) => {
    activeItem.value = item;
    if (action === 'view') isShowOpen.value = true;
    if (action === 'edit') isEditOpen.value = true;
    if (action === 'delete') isDeleteOpen.value = true;
};

const getActions = () => [
    { key: 'view', label: 'View Details' },
    { key: 'edit', label: 'Edit' },
    { key: 'delete', label: 'Delete' }
];
</script>

<template>
    <DashboardLayout>
        <div class="p-8 max-w-6xl mx-auto space-y-6">

            <Breadcrumbs :items="[{ label: 'Dashboard', href: '/dashboard' }, { label: 'Class Categories' }]" />

            <div class="flex justify-between items-end">
                <PageHero
                    title="Class Categories"
                    description="Manage and group courses into active learning structures."
                />
                <button
                    @click="isCreateOpen = true"
                    class="px-5 py-2.5 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-sm transition"
                >
                    + Add New Category
                </button>
            </div>

            <!-- Search & Filter Bar -->
            <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center">

                <!-- Search -->
                <div class="relative flex-1 w-full">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                    </svg>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search by name or code…"
                        class="w-full pl-9 pr-9 py-2 text-sm border border-slate-200 rounded-lg bg-white text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                    />
                    <button
                        v-if="search"
                        @click="search = ''"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition"
                        aria-label="Clear search"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M18 6 6 18M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Class Type dropdown -->
                <div class="relative shrink-0">
                    <select
                        v-model="classTypeFilter"
                        class="appearance-none pl-3 pr-8 py-2 text-sm border border-slate-200 rounded-lg bg-white text-slate-700 font-medium focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition cursor-pointer"
                    >
                        <option value="all">All Types</option>
                        <option
                            v-for="type in classTypes"
                            :key="type.class_type_id"
                            :value="String(type.class_type_id)"
                        >
                            {{ type.type_name }}
                        </option>
                    </select>
                    <svg class="absolute right-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="m6 9 6 6 6-6"/>
                    </svg>
                </div>

                <!-- Status toggle -->
                <div class="flex items-center gap-1 bg-slate-100 rounded-lg p-1 shrink-0">
                    <button
                        v-for="opt in [{ value: 'all', label: 'All' }, { value: 'active', label: 'Active' }, { value: 'inactive', label: 'Inactive' }]"
                        :key="opt.value"
                        @click="statusFilter = opt.value"
                        :class="[
                            'px-3 py-1.5 text-xs font-semibold rounded-md transition',
                            statusFilter === opt.value
                                ? 'bg-white text-slate-900 shadow-sm'
                                : 'text-slate-500 hover:text-slate-700'
                        ]"
                    >
                        {{ opt.label }}
                    </button>
                </div>

                <!-- Clear filters -->
                <button
                    v-if="hasActiveFilters"
                    @click="clearFilters"
                    class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition shrink-0"
                >
                    Clear filters
                </button>
            </div>

            <!-- Result count -->
            <p v-if="hasActiveFilters" class="text-xs text-slate-400 -mt-2">
                Showing {{ filteredCategories.length }} of {{ categories?.data?.length ?? 0 }} results
            </p>

            <Card class="p-0 overflow-hidden">
                <Table>
                    <TableHeader>
                        <TableRow class="bg-slate-50/50">
                            <TableHead class="px-6 py-4">Code</TableHead>
                            <TableHead class="px-6 py-4">Category Name</TableHead>
                            <TableHead class="px-6 py-4">Class Type</TableHead>
                            <TableHead class="px-6 py-4">Status</TableHead>
                            <TableHead class="px-6 py-4 text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow
                            v-for="item in filteredCategories"
                            :key="item.class_category_id"
                            class="hover:bg-slate-50/50 transition"
                        >
                            <TableCell class="px-6 py-4 font-mono text-slate-500 font-bold">{{ item.category_code || '-' }}</TableCell>
                            <TableCell class="px-6 py-4 font-bold text-slate-900">{{ item.category_name }}</TableCell>
                            <TableCell class="px-6 py-4">
                                <span class="bg-indigo-50 text-indigo-700 px-2.5 py-1 rounded-full text-xs font-bold">
                                    {{ item.class_type?.type_name || 'N/A' }}
                                </span>
                            </TableCell>
                            <TableCell class="px-6 py-4">
                                <span
                                    :class="item.is_active ? 'text-emerald-600 bg-emerald-50' : 'text-slate-600 bg-slate-100'"
                                    class="px-2.5 py-1 rounded-full text-xs font-bold"
                                >
                                    {{ item.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </TableCell>
                            <TableCell class="px-6 py-4 text-right">
                                <ActionMenu
                                    :items="getActions()"
                                    @select="(action) => handleAction(action.key, item)"
                                />
                            </TableCell>
                        </TableRow>

                        <!-- Empty state -->
                        <TableRow v-if="filteredCategories.length === 0">
                            <TableCell colspan="5" class="py-12 text-center text-slate-400 text-sm">
                                <span v-if="hasActiveFilters">
                                    No categories match your filters.
                                    <button @click="clearFilters" class="ml-1 text-indigo-600 hover:underline font-semibold">Clear filters</button>
                                </span>
                                <span v-else>No categories found.</span>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </Card>

            <CreateModal :show="isCreateOpen" :classTypes="classTypes" @close="isCreateOpen = false" />
            <EditModal :show="isEditOpen" :classCategory="activeItem" :classTypes="classTypes" @close="isEditOpen = false" />
            <ShowModal :show="isShowOpen" :classCategory="activeItem" @close="isShowOpen = false" />
            <DeleteModal :show="isDeleteOpen" :classCategory="activeItem" @close="isDeleteOpen = false" />
        </div>
    </DashboardLayout>
</template>